# 06 - Sistema de Filas e Jobs (Laravel Horizon)

## 🏗️ Visão Geral

```
Usuário clica "Buscar"
        ↓
Componente Livewire (retorna em <100ms)
        ↓ dispatch()
┌───────────────────────────────────────────┐
│  QUEUE (Redis) — Fila assíncrona          │
│                                           │
│  default:   BaixarBcaJob                  │
│             ProcessarBcaJob               │
│             AnalisarEfetivoJob            │
│                                           │
│  emails:    EnviarEmailNotificacaoJob     │
└───────────────────────────────────────────┘
        ↓ Horizon workers processam
Resultado salvo no banco — usuário recebe notificação
```

---

## 🔧 Jobs Completos

### BaixarBcaJob

```php
// app/Jobs/BaixarBcaJob.php
namespace App\Jobs;

use App\Models\Bca;
use App\Services\BcaDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BaixarBcaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;          // Tentar 3 vezes antes de mover para failed
    public int $timeout = 120;      // 2 minutos por tentativa
    public int $backoff = 30;       // Aguardar 30s entre tentativas

    public function __construct(
        public readonly Bca $bca
    ) {}

    public function handle(BcaDownloadService $service): void
    {
        $this->bca->update(['status' => 'baixando']);

        $resultado = $service->buscarBca(
            $this->bca->data_publicacao->format('d-m-Y')
        );

        if (!$resultado) {
            $this->bca->update(['status' => 'erro', 'erro_mensagem' => 'BCA não encontrado']);
            return;
        }

        $this->bca->update([
            'arquivo_pdf' => $resultado['arquivo'],
            'fonte'       => $resultado['fonte'],
            'status'      => 'pendente',  // Pronto para processar
        ]);

        // Disparar próximo job em cadeia
        ProcessarBcaJob::dispatch($this->bca)
            ->onQueue('default')
            ->delay(now()->addSeconds(5)); // Pequeno delay para garantir arquivo gravado
    }

    public function failed(\Throwable $exception): void
    {
        $this->bca->update([
            'status'         => 'erro',
            'erro_mensagem'  => "BaixarBcaJob falhou: {$exception->getMessage()}",
        ]);
    }
}
```

### ProcessarBcaJob

```php
// app/Jobs/ProcessarBcaJob.php
namespace App\Jobs;

use App\Models\Bca;
use App\Services\BcaProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessarBcaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly Bca $bca
    ) {}

    public function handle(BcaProcessingService $service): void
    {
        $this->bca->update(['status' => 'processando']);

        $texto = $service->extrairTexto($this->bca->arquivo_pdf);

        $this->bca->update([
            'conteudo_texto' => $texto,
            'status'         => 'processado',
            'processado_em'  => now(),
        ]);

        // Encadear análise de efetivo
        AnalisarEfetivoJob::dispatch($this->bca)->onQueue('default');
    }

    public function failed(\Throwable $exception): void
    {
        $this->bca->update([
            'status'        => 'erro',
            'erro_mensagem' => "ProcessarBcaJob falhou: {$exception->getMessage()}",
        ]);
    }
}
```

### AnalisarEfetivoJob

```php
// app/Jobs/AnalisarEfetivoJob.php
namespace App\Jobs;

use App\Events\BcaProcessadoEvent;
use App\Events\MilitarEncontradoEvent;
use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Models\BcaOcorrencia;
use App\Services\EfetivoAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalisarEfetivoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly Bca $bca
    ) {}

    public function handle(EfetivoAnalysisService $service): void
    {
        $inicio = microtime(true);
        $encontrados = [];

        $efetivosComMencao = $service->analisarEfetivosNoBca($this->bca);

        foreach ($efetivosComMencao as $efetivo) {
            $ocorrencia = BcaOcorrencia::firstOrCreate(
                ['efetivo_id' => $efetivo->id, 'bca_id' => $this->bca->id],
                ['snippet' => $efetivo->snippet]
            );

            $encontrados[] = $efetivo;

            // Disparar evento — listener vai enviar email
            event(new MilitarEncontradoEvent($efetivo, $this->bca, $efetivo->snippet));
        }

        // Registrar execução
        BcaExecucao::create([
            'bca_id'                  => $this->bca->id,
            'data_busca'              => $this->bca->data_publicacao,
            'resultado'               => 'sucesso',
            'militares_analisados'    => $service->totalAnalisados,
            'militares_encontrados'   => count($encontrados),
            'tempo_execucao_segundos' => round(microtime(true) - $inicio, 2),
        ]);

        event(new BcaProcessadoEvent($this->bca, collect($encontrados)));
    }
}
```

### EnviarEmailNotificacaoJob

```php
// app/Jobs/EnviarEmailNotificacaoJob.php
namespace App\Jobs;

use App\Mail\MencaoBcaMail;
use App\Models\BcaEmail;
use App\Models\Bca;
use App\Models\Efetivo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarEmailNotificacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;
    public int $backoff = 60;  // 1 min entre retentativas de email

    public function __construct(
        public readonly Efetivo $efetivo,
        public readonly Bca $bca,
        public readonly string $snippet = ''
    ) {}

    public function handle(): void
    {
        // Buscar emails do efetivo (pode ter múltiplos)
        $emails = $this->efetivo->emails->pluck('email')->push($this->efetivo->email)->filter()->unique();

        foreach ($emails as $emailDestino) {
            $registro = BcaEmail::create([
                'efetivo_id'    => $this->efetivo->id,
                'bca_id'        => $this->bca->id,
                'email_destino' => $emailDestino,
                'status'        => 'pendente',
            ]);

            try {
                Mail::to($emailDestino)->send(
                    new MencaoBcaMail($this->efetivo, $this->bca, $this->snippet)
                );

                $registro->update(['status' => 'enviado', 'enviado_em' => now()]);
            } catch (\Exception $e) {
                $registro->update([
                    'status'          => 'falhou',
                    'erro_mensagem'   => $e->getMessage(),
                    'tentativas'      => $registro->tentativas + 1,
                ]);
                throw $e;  // Relança para Horizon registrar como falha
            }
        }
    }
}
```

---

## ⏰ Scheduler (Busca Automática)

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\BuscaBcaAutomaticaCommand;
use App\Console\Commands\LimparBcasAntigosCommand;

// Buscar BCA todos os dias úteis às 07:00 e 12:00
Schedule::command(BuscaBcaAutomaticaCommand::class)
    ->weekdays()
    ->at('07:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/bca_automatica.log'));

Schedule::command(BuscaBcaAutomaticaCommand::class)
    ->weekdays()
    ->at('12:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/bca_automatica.log'));

// Limpar BCAs antigos aos domingos às 02:00
Schedule::command(LimparBcasAntigosCommand::class, ['--keep=90'])
    ->sundays()
    ->at('02:00');

// Verificar emails com falha e reenviar — diariamente às 09:00
Schedule::command('bca:reenviar-emails-falhos')
    ->dailyAt('09:00');
```

```bash
# Para o scheduler funcionar em produção, adicionar ao crontab:
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Horizon — Dashboard e Configuração

```php
// config/horizon.php (resumido — ver exemplos/horizon.php.example para completo)
return [
    'environments' => [
        'production' => [
            'supervisor-default' => [
                'connection'  => 'redis',
                'queue'       => ['default', 'emails'],
                'balance'     => 'auto',
                'maxProcesses' => 4,
                'minProcesses' => 1,
                'tries'       => 3,
                'timeout'     => 120,
            ],
        ],
        'local' => [
            'supervisor-default' => [
                'maxProcesses' => 2,
                'queue'        => ['default', 'emails'],
            ],
        ],
    ],
];
```

**Acessar dashboard**: `http://localhost:8080/horizon`

---

## 🔧 Supervisor em Produção

```ini
; /etc/supervisor/conf.d/horizon.conf
[program:horizon]
process_name=%(program_name)s
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
# Aplicar configuração
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start horizon
sudo supervisorctl status
```

---

## 🚨 Troubleshooting de Jobs

### Jobs não processam

```bash
php artisan horizon:status          # Ver status
php artisan queue:failed            # Listar jobs com falha
php artisan queue:failed --queue=emails  # Filtrar por fila

# Ver detalhes do job falho (ID do job)
php artisan queue:forget 5

# Reprocessar todos os falhos
php artisan queue:retry all

# Reiniciar Horizon
php artisan horizon:terminate
php artisan horizon
```

### Monitorar em tempo real

```bash
# Ver jobs sendo processados
watch -n 2 "php artisan horizon:list"

# Logs do Horizon
tail -f storage/logs/horizon.log
```

---

**Próximo documento**: [07 - Docker e Infraestrutura](07_DOCKER_INFRAESTRUTURA.md)

**Última atualização**: 14/03/2026
