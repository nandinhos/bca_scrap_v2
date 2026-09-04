<?php

namespace App\Services;

use App\Models\Bca;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BcaProcessingService
{
    /**
     * Extract text from the PDF and update the BCA record.
     * Returns the extracted text or null on failure.
     *
     * Nao ha cache de texto: bcas.texto_completo e a unica fonte de verdade.
     * Este metodo so e chamado por ProcessarBcaJob, logo apos o download, onde
     * reextrair e o comportamento correto — o PDF acabou de chegar.
     */
    public function processarPdf(Bca $bca): ?string
    {
        if (! $bca->url) {
            Log::warning("BCA [{$bca->data}]: no URL to process");

            return null;
        }

        $storagePath = Storage::disk('public')->path($bca->url);

        if (! file_exists($storagePath)) {
            Log::error("BCA [{$bca->data}]: PDF file not found at {$storagePath}");

            return null;
        }

        return $this->extrairTexto($storagePath, $bca);
    }

    private function extrairTexto(string $pdfPath, Bca $bca): ?string
    {
        try {
            $output = [];
            $returnCode = 0;
            $escapedPath = escapeshellarg($pdfPath);
            exec("pdftotext -enc UTF-8 -layout {$escapedPath} -", $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error("BCA [{$bca->data}]: pdftotext failed with code {$returnCode}");

                return null;
            }

            $text = implode("\n", $output);

            // Normalize encoding
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

            if (empty(trim($text))) {
                Log::warning("BCA [{$bca->data}]: extracted text is empty");

                return null;
            }

            // Fonte de verdade unica: a coluna texto_completo.
            $bca->update([
                'texto_completo' => $text,
                'processado_em' => now(),
            ]);

            Log::info("BCA [{$bca->data}]: texto extracted (".strlen($text).' chars)');

            return $text;

        } catch (\Exception $e) {
            Log::error("BCA [{$bca->data}]: texto extraction exception — ".$e->getMessage());

            return null;
        }
    }
}
