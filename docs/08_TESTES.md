# 08 - Estratégia de Testes (Pest PHP)

## 🎯 Metas de Cobertura (Revisadas)

| Camada | Meta | Justificativa |
|--------|------|---------------|
| **Services** | ≥90% | Lógica de negócio crítica — falha aqui impacta o usuário diretamente |
| **Jobs** | ≥85% | Processamento assíncrono — falha aqui é silenciosa e difícil de detectar |
| **Commands** | ≥80% | Automação do sistema |
| **Models/Repositories** | ≥80% | Acesso a dados e queries complexas |
| **Livewire Components** | ≥60% | UI reativa — complexidade de teste inerente ao framework |
| **Geral** | ≥80% | Meta global (Services/Jobs puxam a média para cima) |

> **Por que 60% para Livewire?**
> Testes de componentes Livewire requerem setup complexo. A lógica crítica de negócio vive nos Services/Jobs — testar estes com 90%+ é mais valioso que aumentar cobertura de componentes UI.

---

## ⚙️ Configuração do Pest PHP

```bash
# Instalar Pest e plugins
composer require pestphp/pest pestphp/pest-plugin-laravel --dev
./vendor/bin/pest --init
```

```php
// tests/Pest.php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

// Helpers globais
function criarBcaFake(array $atributos = []): \App\Models\Bca
{
    return \App\Models\Bca::factory()->create($atributos);
}

function criarEfetivoFake(array $atributos = []): \App\Models\Efetivo
{
    return \App\Models\Efetivo::factory()->create($atributos);
}
```

```xml
<!-- phpunit.xml -->
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <report>
            <html outputDirectory="coverage"/>
        </report>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="pgsql"/>
        <env name="DB_DATABASE" value="bca_db_test"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="MAIL_MAILER" value="array"/>
    </php>
</phpunit>
```

---

## 🧪 Testes de Services

```php
// tests/Unit/Services/BcaDownloadServiceTest.php
use App\Services\BcaDownloadService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

describe('BcaDownloadService', function () {

    it('retorna do cache quando BCA já foi baixado', function () {
        Cache::put('bca_arquivo_14-03-2026', ['arquivo' => 'bcas/047.pdf', 'fonte' => 'cache']);

        $resultado = app(BcaDownloadService::class)->buscarBca('14-03-2026');

        expect($resultado)
            ->toBeArray()
            ->and($resultado['fonte'])->toBe('cache');

        Http::assertNothingSent();  // Nenhuma requisição HTTP feita
    });

    it('busca via API CENDOC quando não há cache', function () {
        Http::fake([
            '*cendoc*' => Http::response(file_get_contents(base_path('tests/fixtures/bca_047.pdf'))),
        ]);

        $resultado = app(BcaDownloadService::class)->buscarBca('14-03-2026');

        expect($resultado)
            ->toBeArray()
            ->and($resultado['fonte'])->toBe('cendoc_api');
    });

    it('retorna null quando BCA não é encontrado em nenhuma fonte', function () {
        Http::fake(['*' => Http::response('Not Found', 404)]);

        $resultado = app(BcaDownloadService::class)->buscarBca('01-01-2026');

        expect($resultado)->toBeNull();
    });

    it('usa chunk de no máximo 10 requests em busca paralela', function () {
        $requestsFeitos = 0;

        Http::fake(function ($request) use (&$requestsFeitos) {
            $requestsFeitos++;
            return Http::response('', 404);
        });

        // Garantir que não dispara mais de 10 requests simultâneos
        // (verificar via logs ou instrumentação)
        app(BcaDownloadService::class)->buscarBca('01-01-2026');

        // Em chunks de 10, deve parar ao encontrar ou esgotar
        expect($requestsFeitos)->toBeLessThanOrEqual(366);
    });

});
```

```php
// tests/Unit/Services/BcaProcessingServiceTest.php
use App\Services\BcaProcessingService;

describe('BcaProcessingService', function () {

    it('extrai texto de PDF com pdftotext', function () {
        // Criar PDF fake para teste
        $pdfPath = 'bcas/teste.pdf';
        Storage::fake('local')->put($pdfPath, file_get_contents(base_path('tests/fixtures/bca_teste.pdf')));

        $texto = app(BcaProcessingService::class)->extrairTexto($pdfPath);

        expect($texto)
            ->toBeString()
            ->not->toBeEmpty()
            ->toContain('BOLETIM');
    });

    it('armazena texto extraído no cache Redis', function () {
        Cache::shouldReceive('remember')->once()->andReturn('texto do bca');

        $resultado = app(BcaProcessingService::class)->extrairTexto('bcas/047.pdf');

        expect($resultado)->toBe('texto do bca');
    });

    it('lança exceção quando pdftotext falha', function () {
        Storage::fake()->put('bcas/corrompido.pdf', 'arquivo corrompido');

        expect(fn () => app(BcaProcessingService::class)->extrairTexto('bcas/corrompido.pdf'))
            ->toThrow(\RuntimeException::class, 'Falha ao extrair texto');
    });

});
```

---

## 🧪 Testes de Jobs

```php
// tests/Feature/Jobs/ProcessarBcaJobTest.php
use App\Jobs\AnalisarEfetivoJob;
use App\Jobs\ProcessarBcaJob;
use App\Models\Bca;
use App\Services\BcaProcessingService;
use Illuminate\Support\Facades\Queue;

describe('ProcessarBcaJob', function () {

    it('extrai texto e atualiza status para processado', function () {
        $bca = Bca::factory()->create(['status' => 'pendente']);

        $mock = mock(BcaProcessingService::class)
            ->expect(extrairTexto: fn () => 'BOLETIM 047 TRANSFERÊNCIA FERNANDO');

        app()->instance(BcaProcessingService::class, $mock);

        (new ProcessarBcaJob($bca))->handle(app(BcaProcessingService::class));

        $bca->refresh();
        expect($bca->status)->toBe('processado')
            ->and($bca->conteudo_texto)->toContain('FERNANDO')
            ->and($bca->processado_em)->not->toBeNull();
    });

    it('encadeia AnalisarEfetivoJob após processar', function () {
        Queue::fake();
        $bca = Bca::factory()->create(['status' => 'pendente']);

        $mock = mock(BcaProcessingService::class)
            ->expect(extrairTexto: fn () => 'texto do bca');

        (new ProcessarBcaJob($bca))->handle($mock);

        Queue::assertPushed(AnalisarEfetivoJob::class, fn ($job) => $job->bca->id === $bca->id);
    });

    it('marca status como erro quando falha', function () {
        $bca = Bca::factory()->create(['status' => 'pendente']);
        $job = new ProcessarBcaJob($bca);

        $job->failed(new \Exception('PDF corrompido'));

        $bca->refresh();
        expect($bca->status)->toBe('erro')
            ->and($bca->erro_mensagem)->toContain('PDF corrompido');
    });

});
```

---

## 🧪 Testes de Componentes Livewire

```php
// tests/Feature/Livewire/BuscaBcaTest.php
use App\Http\Livewire\Busca\BuscaBca;
use App\Services\BcaDownloadService;
use Livewire\Livewire;

describe('BuscaBca (Livewire)', function () {

    it('renderiza com data de hoje pré-preenchida', function () {
        Livewire::test(BuscaBca::class)
            ->assertSet('dataSelecionada', now()->format('d/m/Y'))
            ->assertSee('Buscar BCA');
    });

    it('valida data obrigatória', function () {
        Livewire::test(BuscaBca::class)
            ->set('dataSelecionada', '')
            ->call('buscar')
            ->assertHasErrors(['dataSelecionada' => 'required']);
    });

    it('valida formato da data', function () {
        Livewire::test(BuscaBca::class)
            ->set('dataSelecionada', '2026-03-14')  // Formato errado
            ->call('buscar')
            ->assertHasErrors(['dataSelecionada']);
    });

    it('exibe mensagem quando BCA não é encontrado', function () {
        $mock = mock(BcaDownloadService::class)
            ->expect(buscarBca: fn () => null);
        app()->instance(BcaDownloadService::class, $mock);

        Livewire::test(BuscaBca::class)
            ->set('dataSelecionada', '14/03/2026')
            ->call('buscar')
            ->assertSet('erro', fn ($e) => str_contains($e, 'não encontrado'));
    });

    it('define bcaId quando BCA é encontrado', function () {
        $mock = mock(BcaDownloadService::class)
            ->expect(buscarBca: fn () => ['arquivo' => 'bcas/047.pdf', 'fonte' => 'cache']);
        app()->instance(BcaDownloadService::class, $mock);

        Livewire::test(BuscaBca::class)
            ->set('dataSelecionada', '14/03/2026')
            ->call('buscar')
            ->assertSet('bcaId', fn ($id) => $id > 0)
            ->assertDispatched('bca-carregado');
    });

});
```

---

## 🧪 Testes de Integração

```php
// tests/Feature/Integration/BuscaCompletaTest.php
use App\Jobs\AnalisarEfetivoJob;
use App\Jobs\ProcessarBcaJob;
use App\Models\Efetivo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

describe('Fluxo completo de busca BCA', function () {

    it('processa BCA e encontra efetivo', function () {
        Queue::fake();
        Mail::fake();

        // Criar efetivo que aparece no texto do BCA
        $efetivo = Efetivo::factory()->create([
            'nome_completo' => 'FERNANDO EXEMPLO DA SILVA',
            'ativo' => true,
        ]);

        $textoComEfetivo = "TRANSFERÊNCIA DO 1S FERNANDO EXEMPLO DA SILVA PARA O GAC-PAC";

        // Simular BCA já baixado
        $bca = \App\Models\Bca::factory()->create([
            'conteudo_texto' => $textoComEfetivo,
            'status' => 'processado',
        ]);

        // Executar análise de efetivo (sync para teste)
        (new AnalisarEfetivoJob($bca))->handle(
            app(\App\Services\EfetivoAnalysisService::class)
        );

        // Verificar que ocorrência foi registrada
        $this->assertDatabaseHas('bca_ocorrencias', [
            'efetivo_id' => $efetivo->id,
            'bca_id'     => $bca->id,
        ]);

        // Verificar que email foi enfileirado
        Queue::assertPushed(\App\Jobs\EnviarEmailNotificacaoJob::class);
    });

});
```

---

## 🚀 Executar Testes

```bash
# Todos os testes
php artisan test
./vendor/bin/pest  # Alternativa direta

# Com cobertura de código
php artisan test --coverage
php artisan test --coverage --min=80  # Falha se abaixo de 80%

# Gerar relatório HTML de cobertura
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage-html coverage/

# Filtros
php artisan test --filter BuscaBcaTest
php artisan test tests/Feature/Livewire/
php artisan test tests/Unit/Services/

# Paralelo (mais rápido)
php artisan test --parallel

# Parar no primeiro erro
php artisan test --stop-on-failure

# Verbose (detalhado)
php artisan test --verbose
```

---

## 📁 Estrutura dos Testes

```
tests/
├── Pest.php                          # Config global + helpers
├── TestCase.php                      # Base class
├── Unit/
│   └── Services/
│       ├── BcaDownloadServiceTest.php
│       ├── BcaProcessingServiceTest.php
│       └── EfetivoAnalysisServiceTest.php
├── Feature/
│   ├── Jobs/
│   │   ├── BaixarBcaJobTest.php
│   │   ├── ProcessarBcaJobTest.php
│   │   └── EnviarEmailJobTest.php
│   ├── Livewire/
│   │   ├── BuscaBcaTest.php
│   │   ├── ListagemEfetivoTest.php
│   │   └── GestorPalavrasTest.php
│   ├── Integration/
│   │   └── BuscaCompletaTest.php
│   └── Commands/
│       └── BuscaAutomaticaCommandTest.php
└── fixtures/
    ├── bca_047.pdf                   # PDF de teste
    └── bca_teste.pdf
```

---

**Última atualização**: 14/03/2026
