<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BcaDownloadService
{
    private string $baseUrl;

    private int $chunkSize;

    private int $timeout;

    private int $retry;

    private int $maxPdfSizeMb;

    public function __construct()
    {
        $this->baseUrl = config('bca.base_url', 'http://www.cendoc.intraer/sisbca/consulta_bca/');
        $this->chunkSize = config('bca.search_chunk_size', 10);
        $this->timeout = config('bca.search_timeout', 10);
        $this->retry = config('bca.search_retry', 2);
        $this->maxPdfSizeMb = config('bca.max_pdf_size_mb', 50);
    }

    /**
     * Try to find and download the BCA for a given date (Y-m-d format).
     * Returns the storage path if found, null otherwise.
     */
    public function baixarBca(string $data): ?string
    {
        $cacheKey = "bca:query:{$data}";
        $cached = Cache::get($cacheKey);

        if ($cached === 'nao_encontrado') {
            Log::info("BCA [{$data}]: cache hit (não encontrado), forçando retry");
            Cache::forget($cacheKey);
        } elseif ($cached) {
            Log::info("BCA [{$data}]: cache hit (URL: {$cached})");

            return $this->downloadFromUrl($cached, $data);
        }

        $url = $this->buscarUrlBca($data);

        if ($url === null) {
            Cache::put($cacheKey, 'nao_encontrado', now()->addHours(24));

            return null;
        }

        Cache::put($cacheKey, $url, now()->addHours(24));

        return $this->downloadFromUrl($url, $data);
    }

    /**
     * Discover and return the download URL for a given date.
     *
     * Strategy:
     *  1. POST CENDOC → get BCA number + exact download href (fast, single request)
     *  2. Try CENDOC download with that href (no .pdf in bca param — that's how CENDOC works)
     *  3. Fallback: try ICEA with same number (one request)
     *  4. Last resort: brute-force ICEA 1-366 only if CENDOC POST failed entirely
     */
    public function buscarUrlBca(string $data): ?string
    {
        $carbon = Carbon::parse($data);
        $dia = $carbon->format('d');
        $mes = $carbon->format('m');
        $ano = $carbon->year;
        $iceaBase = config('bca.icea_url', 'http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/');

        // 1. CENDOC POST → numero + download href
        [$numero, $cendocHref] = $this->consultarCendoc($dia, $mes, $ano);

        if ($numero && $cendocHref) {
            // 2. Try CENDOC download (href from their own response, no .pdf extension)
            $cendocUrl = $this->baseUrl.$cendocHref;
            Log::info("BCA [{$data}]: CENDOC number {$numero}, trying {$cendocUrl}");

            if ($this->checkUrlExists($cendocUrl)) {
                return $cendocUrl;
            }

            // 3. CENDOC unavailable — try ICEA with the known number (one request)
            $iceaUrl = $iceaBase."bca_{$numero}_{$dia}-{$mes}-{$ano}.pdf";
            Log::info("BCA [{$data}]: CENDOC unreachable, trying ICEA: {$iceaUrl}");

            if ($this->checkUrlExists($iceaUrl)) {
                return $iceaUrl;
            }

            Log::warning("BCA [{$data}]: both CENDOC and ICEA failed for number {$numero}");

            return null;
        }

        // 4. CENDOC POST failed — brute-force ICEA as last resort
        Log::info("BCA [{$data}]: CENDOC POST failed, brute-forcing ICEA 1-366...");
        for ($i = 1; $i <= 366; $i++) {
            $url = $iceaBase."bca_{$i}_{$dia}-{$mes}-{$ano}.pdf";

            if ($this->checkUrlExists($url)) {
                Log::info("BCA [{$data}]: Found at ICEA number {$i}");

                return $url;
            }

            if ($i % 50 === 0) {
                Log::debug("BCA [{$data}]: ICEA brute force at {$i}/366");
            }
        }

        return null;
    }

    /**
     * POST to CENDOC and return [numero, downloadHref] or [null, null].
     * CENDOC href format: "download.php?ano=2026&bca=bca_48_13-03-2026" (no .pdf)
     */
    private function consultarCendoc(string $dia, string $mes, int $ano): array
    {
        try {
            $response = Http::asForm()
                ->timeout($this->timeout)
                ->post($this->baseUrl.'busca_bca_data.php', [
                    'dia_bca_ost' => $dia,
                    'mes_bca_ost' => $mes,
                    'ano_bca_ost' => $ano,
                    'pesquisar' => 'Pesquisar',
                ]);

            if ($response->successful()) {
                $body = $response->body();

                // Extract number: "BCA nº.: 48 de ..."
                preg_match('/BCA nº\.:\s*(\d+)/', $body, $nMatch);

                // Extract href: href='download.php?ano=2026&bca=bca_48_13-03-2026'
                preg_match("/href='(download\.php[^']+)'/", $body, $hMatch);

                if (! empty($nMatch[1]) && ! empty($hMatch[1])) {
                    return [$nMatch[1], $hMatch[1]];
                }
            }
        } catch (\Exception $e) {
            Log::warning('BCA CENDOC POST failed: '.$e->getMessage());
        }

        return [null, null];
    }

    private function checkUrlExists(string $url): bool
    {
        try {
            $response = Http::timeout(5)->head($url);

            if ($response->status() === 405) {
                $response = Http::timeout(10)->get($url);
            }

            if (! $response->successful()) {
                return false;
            }

            $ct = strtolower($response->header('Content-Type') ?? '');

            // CENDOC returns "application/save", ICEA returns "application/pdf"
            return str_contains($ct, 'pdf')
                || str_contains($ct, 'octet-stream')
                || str_contains($ct, 'save');
        } catch (\Exception) {
            return false;
        }
    }

    private function downloadFromUrl(string $url, string $data): ?string
    {
        try {
            $response = Http::timeout(60)->get($url);

            if (! $response->successful() || strlen($response->body()) < 1000) {
                return null;
            }

            $body = $response->body();
            $maxBytes = $this->maxPdfSizeMb * 1024 * 1024;

            if (strlen($body) > $maxBytes) {
                Log::warning("BCA [{$data}]: PDF too large (".strlen($body).' bytes)');

                return null;
            }

            $path = "bcas/{$data}.pdf";
            Storage::disk('public')->put($path, $body);

            return $path;
        } catch (\Exception $e) {
            Log::error("BCA [{$data}]: download failed — ".$e->getMessage());

            return null;
        }
    }
}
