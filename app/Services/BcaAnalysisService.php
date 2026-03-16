<?php

namespace App\Services;

use App\Events\MilitarEncontradoEvent;
use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use App\Models\PalavraChave;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BcaAnalysisService
{
    /**
     * Analyze the BCA text and find matching efetivos and keywords.
     * Returns count of new occurrences found.
     */
    public function analisar(Bca $bca, string $tipo = 'automatica', array $keywordsToSearch = []): int
    {
        $data = $bca->data->format('Y-m-d');
        $cacheKey = "bca:analise:{$data}";

        $textoBca = Cache::get("bca:texto:{$data}") ?? $bca->texto_completo;

        if (! $textoBca) {
            Log::warning("BCA [{$data}]: no text available for analysis");

            return 0;
        }

        $count = 0;
        $keywordsEncontradas = [];

        // 1. Search efetivos
        $efetivos = Efetivo::ativo()->get();
        foreach ($efetivos as $efetivo) {
            $matchedTerm = $this->encontraNoBca($efetivo, $textoBca);

            if ($matchedTerm) {
                // User wants highlight specifically on FULL NAME in the preview
                $snippet = $this->gerarSnippet($efetivo, $textoBca, $matchedTerm);

                // Determine total quantity (max mentions to avoid double counting name+saram on same line)
                $textoMaiusc = strtoupper($textoBca);
                $countSaram = mb_substr_count($textoMaiusc, strtoupper($efetivo->saram)) +
                             mb_substr_count($textoMaiusc, strtoupper($efetivo->getSaramHifenado()));
                $countNome = mb_substr_count($textoMaiusc, strtoupper($efetivo->nome_completo));

                $quantidade = max($countSaram, $countNome);
                if ($quantidade === 0) {
                    $quantidade = 1;
                }

                // Determine match type for the UI badges
                $tipoMatch = 'NOME';

                if ($matchedTerm === $efetivo->saram || $matchedTerm === $efetivo->getSaramHifenado()) {
                    $tipoMatch = 'SARAM';
                    // Check if name also appears to show "SARAM + NOME"
                    if (mb_stripos($textoBca, $efetivo->nome_completo) !== false) {
                        $tipoMatch = 'SARAM + NOME';
                    }
                }

                $ocorrencia = BcaOcorrencia::updateOrCreate(
                    ['bca_id' => $bca->id, 'efetivo_id' => $efetivo->id],
                    ['snippet' => $snippet, 'tipo_match' => $tipoMatch, 'quantidade' => $quantidade]
                );

                if ($ocorrencia->wasRecentlyCreated || ! $ocorrencia->foiEnviado()) {
                    if ($ocorrencia->wasRecentlyCreated) {
                        $count++;
                    }
                    event(new MilitarEncontradoEvent($ocorrencia));
                }
            }
        }

        // 2. Search keywords (either provided or active in DB)
        $keywords = ! empty($keywordsToSearch)
            ? PalavraChave::whereIn('palavra', $keywordsToSearch)->get()
            : PalavraChave::ativa()->get();

        foreach ($keywords as $kw) {
            $kwCount = mb_substr_count(strtoupper($textoBca), strtoupper($kw->palavra));
            if ($kwCount > 0) {
                $keywordsEncontradas[$kw->palavra] = $kwCount;
            }
        }

        // 3. Log execution
        $mensagem = null;
        if (! empty($keywordsEncontradas)) {
            $mensagem = json_encode(['keywords_encontradas' => $keywordsEncontradas]);
        }

        BcaExecucao::create([
            'tipo' => $tipo,
            'data_execucao' => now(),
            'status' => 'sucesso',
            'mensagem' => $mensagem,
            'registros_processados' => $count,
        ]);

        Cache::put($cacheKey, ['count' => $count, 'keywords' => $keywordsEncontradas], now()->addHour());

        Log::info("BCA [{$data}]: analysis done — {$count} new occurrences, ".count($keywordsEncontradas).' keywords');

        $bca->update(['analisado_em' => now()]);

        return $count;
    }

    public function encontraNoBca(Efetivo $efetivo, string $textoBca): ?string
    {
        $textoUpper = strtoupper($textoBca);
        $saram = strtoupper($efetivo->saram);
        $saramHifenado = strtoupper($efetivo->getSaramHifenado());
        $nomeCompleto = strtoupper($efetivo->nome_completo);

        // 1. SARAM direct match (PRIORITY)
        if (str_contains($textoUpper, $saram)) {
            return $efetivo->saram;
        }

        // 2. SARAM with hyphen variant (PRIORITY)
        if (str_contains($textoUpper, $saramHifenado)) {
            return $efetivo->getSaramHifenado();
        }

        // 3. Strict Full Name match (ONLY IF SARAM NOT FOUND)
        if (mb_stripos($textoBca, $efetivo->nome_completo) !== false) {
            return $efetivo->nome_completo;
        }

        return null;
    }

    private function gerarSnippet(Efetivo $efetivo, string $textoBca, string $matchedTerm): string
    {
        $lines = explode("\n", $textoBca);
        $nomeCompleto = $efetivo->nome_completo;

        $matchedLines = [];

        // We look for the matchedTerm to find the right lines
        foreach ($lines as $i => $line) {
            if (mb_stripos($line, $matchedTerm) !== false) {
                // Include surrounding context (1 line before/after)
                $start = max(0, $i - 1);
                $end = min(count($lines) - 1, $i + 1);
                for ($j = $start; $j <= $end; $j++) {
                    $matchedLines[$j] = trim($lines[$j]);
                }
            }
        }

        $snippet = implode("\n", array_filter(array_values($matchedLines)));

        // HIGHLIGHT: User specifically requested highlight ONLY over the full name
        // (If SARAM matched but name is also in the snippet, highlight the name)
        $highlightBase = mb_stripos($snippet, $nomeCompleto) !== false ? $nomeCompleto : $matchedTerm;

        // Escape HTML before applying highlight to prevent XSS
        $snippet = e($snippet);

        $snippet = preg_replace(
            '/'.preg_quote($highlightBase, '/').'/i',
            '<mark style="background:#00ff00;color:#000;font-weight:bold;padding:0 2px;border-radius:2px">$0</mark>',
            $snippet
        );

        return mb_substr($snippet, 0, 1000);
    }
}
