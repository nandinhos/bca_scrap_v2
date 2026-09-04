<?php

namespace App\Console\Commands;

use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Services\BcaAnalysisService;
use App\Services\BcaDownloadService;
use App\Services\BcaProcessingService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessarRetroativosCommand extends Command
{
    protected $signature = 'bca:retroativo
                            {--de=2026-07-29 : Data de início (Y-m-d)}
                            {--ate=2026-09-03 : Data fim (inclusive, Y-m-d)}
                            {--enviar-emails : Se fornecido, despacha envio de emails. Padrão: suprime emails}';

    protected $description = 'Baixa, processa e analisa BCAs de um intervalo retroativo sem enviar emails por padrão';

    public function handle(
        BcaDownloadService $downloadService,
        BcaProcessingService $processingService,
        BcaAnalysisService $analysisService
    ): int {
        $de = $this->option('de');
        $ate = $this->option('ate');
        $enviarEmails = $this->option('enviar-emails');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $de) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate)) {
            $this->error('Formato de data inválido. Use Y-m-d.');
            return self::FAILURE;
        }

        $periodo = CarbonPeriod::create($de, $ate);
        $this->info("Iniciando verificação de BCAs retroativos de {$de} até {$ate}...");
        $this->line($enviarEmails ? '<fg=yellow>Atenção: Envio de e-mails ATIVADO.</>' : '<fg=cyan>Envio de e-mails SUPRIMIDO (modo somente verificação).</>');
        $this->newLine();

        if (! $enviarEmails) {
            config(['bca.suppress_emails' => true]);
        }

        $bcasComOcorrencias = [];
        $totalOcorrencias = 0;

        foreach ($periodo as $carbon) {
            if ($carbon->isWeekend()) {
                continue;
            }

            $dataStr = $carbon->format('Y-m-d');
            $this->output->write("Verificando {$dataStr}... ");

            // 1. Verificar se BCA já existe e já foi analisado
            $bca = Bca::where('data', $dataStr)->first();

            if (! $bca || ! $bca->processado_em) {
                // Tenta baixar
                $path = $downloadService->baixarBca($dataStr);

                if (! $path) {
                    $this->line('<fg=gray>Nenhum BCA encontrado.</>');
                    continue;
                }

                $cachedUrl = Cache::get("bca:query:{$dataStr}");
                preg_match('/bca_(\d+)_/', basename((string) ($cachedUrl ?? $path)), $m);
                $numero = $m[1] ?? '0';

                $bca = Bca::updateOrCreate(
                    ['data' => $dataStr],
                    ['numero' => $numero, 'url' => $path]
                );

                $processingService->processarPdf($bca);
            }

            if (! $bca->processado_em || ! $bca->texto_completo) {
                $this->line('<fg=red>Falha ao extrair texto do PDF.</>');
                continue;
            }

            // Analisar se ainda não analisado
            if (! $bca->analisado_em) {
                $analysisService->analisar($bca, 'manual', []);
            }

            // Consultar ocorrências
            $ocorrencias = $bca->ocorrencias()->with('efetivo.unidade')->get();

            if ($ocorrencias->isNotEmpty()) {
                $count = $ocorrencias->count();
                $totalOcorrencias += $count;
                $this->line("<fg=green;options=bold>BCA nº {$bca->numero} encontrado: {$count} militar(es) identificado(s)!</>");
                $bcasComOcorrencias[] = $bca;
            } else {
                $this->line("<fg=blue>BCA nº {$bca->numero} processado: 0 militares identificados.</>");
            }
        }

        $this->newLine();
        $this->info("=== RELATÓRIO DE OCORRÊNCIAS NO PERÍODO ({$de} a {$ate}) ===");

        if (empty($bcasComOcorrencias)) {
            $this->info("Nenhuma citação a militares do efetivo encontrada no período.");
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($bcasComOcorrencias as $bca) {
            foreach ($bca->ocorrencias()->with('efetivo.unidade')->get() as $oc) {
                $efetivo = $oc->efetivo;
                $rows[] = [
                    $bca->data->format('d/m/Y'),
                    'BCA nº '.$bca->numero,
                    $efetivo->posto.' '.$efetivo->nome_guerra,
                    $efetivo->saram,
                    $efetivo->unidade?->sigla ?? 'N/A',
                    $oc->tipo_match,
                    $oc->foiEnviado() ? 'SIM' : 'NÃO (Pendente)',
                ];
            }
        }

        $this->table(
            ['Data', 'BCA', 'Militar', 'SARAM', 'Unidade', 'Match', 'E-mail Enviado?'],
            $rows
        );

        $this->info("Total de ocorrências identificadas: {$totalOcorrencias}");

        return self::SUCCESS;
    }
}
