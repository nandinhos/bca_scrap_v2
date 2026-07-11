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
                            {--de=            : Data de inicio do intervalo (Y-m-d). Omitir para todos.}
                            {--ate=            : Data fim do intervalo (inclusive, Y-m-d). Default: hoje.}
                            {--redownload     : Reset completo + re-download do PDF (ignora texto_completo armazenado)}
                            {--force          : Em prod, pula confirmacao interativa}';

    protected $description = 'Re-analisa BCAs processados em um intervalo de datas, limpando ocorrencias antigas e re-executando a analise.';

    public function handle(BcaAnalysisService $service): int
    {
        $ate = $this->option('ate') ?: now()->format('Y-m-d');
        $de = $this->option('de');
        $redownload = $this->option('redownload');

        // Guard prod: exigir --force ou rodar em dev
        if (app()->environment('production') && ! $this->option('force') && ! $this->confirm("Re-analisar BCAs em PRODUCAO ate {$ate}? Isso deleta ocorrencias existentes.", false)) {
            $this->error('Cancelado. Use --force em prod para pular a confirmacao.');

            return self::FAILURE;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate)) {
            $this->error("Data invalida (--ate): {$ate}. Use o formato Y-m-d.");

            return self::FAILURE;
        }

        if ($de !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $de)) {
            $this->error("Data invalida (--de): {$de}. Use o formato Y-m-d.");

            return self::FAILURE;
        }

        // Emails sao sempre suprimidos neste comando
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
            $this->info("Nenhum BCA encontrado para o intervalo especificado.");

            return self::SUCCESS;
        }

        $intervalo = $de ? "de {$de} ate {$ate}" : "ate {$ate}";
        $this->info("Encontrados {$bcas->count()} BCA(s) para re-analise ({$intervalo}).");
        $this->newLine();

        $totalEncontrados = 0;

        foreach ($bcas as $bca) {
            $dataStr = $bca->data->format('Y-m-d');

            // 1. Limpar ocorrencias anteriores
            $deletados = $bca->ocorrencias()->delete();

            // 2. Limpar cache Redis
            Cache::forget("bca:query:{$dataStr}");
            Cache::forget("bca:texto:{$dataStr}");
            Cache::forget("bca:analise:{$dataStr}");

            if ($redownload) {
                // 3a. Reset completo
                $bca->update([
                    'url' => null,
                    'texto_completo' => null,
                    'processado_em' => null,
                    'analisado_em' => null,
                    'keywords_encontradas' => null,
                ]);

                // 4a. Despachar pipeline completo
                config(['bca.suppress_emails' => true]);
                BaixarBcaJob::dispatch($dataStr, []);

                $this->line("BCA {$dataStr}: reset completo - BaixarBcaJob despachado  [{$deletados} ocorrencia(s) removidas]");
            } else {
                // 3b. Reset apenas analise
                $bca->update(['analisado_em' => null, 'keywords_encontradas' => null]);
                config(['bca.suppress_emails' => true]);

                // 4b. Re-analisar com texto armazenado
                $count = $service->analisar($bca, 'manual', []);
                $totalEncontrados += $count;

                $this->line(sprintf(
                    'BCA %s: %d militar(es) encontrado(s)  [%d ocorrencia(s) removidas]',
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
            $this->info("Re-analise concluida. Total: {$totalEncontrados} militar(es) encontrado(s) em {$bcas->count()} BCA(s).");
            $this->warn('Emails suprimidos. Use a interface BuscaBca para enviar notificacoes manualmente.');
        }

        return self::SUCCESS;
    }
}