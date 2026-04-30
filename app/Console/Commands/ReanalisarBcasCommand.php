<?php

namespace App\Console\Commands;

use App\Jobs\BaixarBcaJob;
use App\Models\Bca;
use App\Services\BcaAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ReanalisarBcasCommand extends Command
{
    protected $signature = 'bca:reanalisar
                            {--de=            : Data de início do intervalo (Y-m-d). Omitir para todos.}
                            {--ate=2026-04-29 : Data fim do intervalo (inclusive, Y-m-d)}
                            {--redownload     : Reset completo + re-download do PDF (ignora texto_completo armazenado)}';

    protected $description = 'Re-analisa BCAs processados em um intervalo de datas, limpando ocorrências antigas e re-executando a análise.';

    public function handle(BcaAnalysisService $service): int
    {
        $ate = $this->option('ate');
        $de = $this->option('de');
        $redownload = $this->option('redownload');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate)) {
            $this->error("Data inválida (--ate): {$ate}. Use o formato Y-m-d.");

            return self::FAILURE;
        }

        if ($de !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $de)) {
            $this->error("Data inválida (--de): {$de}. Use o formato Y-m-d.");

            return self::FAILURE;
        }

        // Emails são sempre suprimidos neste comando
        config(['bca.suppress_emails' => true]);

        $query = Bca::whereNotNull('analisado_em')->orderBy('data');

        if (! $redownload) {
            $query->whereNotNull('texto_completo');
        }

        if ($de) {
            $query->where('data', '>=', $de);
        }

        $query->where('data', '<=', $ate);

        $bcas = $query->get();

        if ($bcas->isEmpty()) {
            $this->info('Nenhum BCA encontrado para o intervalo especificado.');

            return self::SUCCESS;
        }

        $intervalo = $de ? "de {$de} até {$ate}" : "até {$ate}";
        $this->info("Encontrados {$bcas->count()} BCA(s) para re-análise ({$intervalo}).");
        $this->newLine();

        $totalEncontrados = 0;

        foreach ($bcas as $bca) {
            $dataStr = $bca->data->format('Y-m-d');

            // 1. Limpar ocorrências anteriores
            $deletados = $bca->ocorrencias()->delete();

            // 2. Limpar cache Redis
            Cache::forget("bca:query:{$dataStr}");
            Cache::forget("bca:texto:{$dataStr}");
            Cache::forget("bca:analise:{$dataStr}");

            if ($redownload) {
                // 3a. Reset completo: apagar texto e timestamps
                $bca->update([
                    'url'                  => null,
                    'texto_completo'       => null,
                    'processado_em'        => null,
                    'analisado_em'         => null,
                    'keywords_encontradas' => null,
                ]);

                // 4a. Despachar pipeline completo com supressão de email
                BaixarBcaJob::dispatch($dataStr, [], true);

                $this->line("BCA {$dataStr}: reset completo — BaixarBcaJob despachado  [{$deletados} ocorrência(s) removidas]");
            } else {
                // 3b. Reset apenas análise
                $bca->update(['analisado_em' => null, 'keywords_encontradas' => null]);

                // 4b. Re-analisar com texto armazenado
                $count = $service->analisar($bca, 'manual');
                $totalEncontrados += $count;

                $this->line(sprintf(
                    'BCA %s: %d militar(es) encontrado(s)  [%d ocorrência(s) removidas]',
                    $dataStr,
                    $count,
                    $deletados
                ));
            }
        }

        $this->newLine();

        if ($redownload) {
            $this->info("{$bcas->count()} job(s) despachado(s). Acompanhe: docker compose exec php php artisan queue:work");
            $this->warn('Emails suprimidos. Envie manualmente pela interface BuscaBca.');
        } else {
            $this->info("Re-análise concluída. Total: {$totalEncontrados} militar(es) encontrado(s) em {$bcas->count()} BCA(s).");
            $this->warn('Emails suprimidos. Use a interface BuscaBca para enviar notificações manualmente.');
        }

        return self::SUCCESS;
    }
}
