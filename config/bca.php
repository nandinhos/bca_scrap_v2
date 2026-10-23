<?php

$defaultBaseUrl = 'http://www.cendoc.intraer/sisbca/consulta_bca/';
$defaultIceaUrl = 'http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/';

return [
    // Fontes públicas da intranet. O operador pode substituí-las pelo .env.
    // O operador "?:" também trata uma variável presente, porém vazia.
    'base_url' => env('BCA_BASE_URL') ?: $defaultBaseUrl,
    'icea_url' => env('BCA_ICEA_URL') ?: $defaultIceaUrl,
    'search_chunk_size' => (int) env('BCA_SEARCH_CHUNK_SIZE', 10),
    'search_timeout' => (int) env('BCA_SEARCH_TIMEOUT', 10),
    'search_retry' => (int) env('BCA_SEARCH_RETRY', 2),
    'max_pdf_size_mb' => (int) env('BCA_MAX_PDF_SIZE_MB', 50),
    // SAD email para envio do compilado (obrigatório configurar por OM via BCA_SAD_EMAIL)
    'sad_email' => env('BCA_SAD_EMAIL'),
    // Token do monitor externo para /health e /metrics (header X-Health-Token)
    'health_token' => env('HEALTH_TOKEN'),
    // Feriados FAB recorrentes (mes-dia), incluindo 23/10 Dia do Aviador
    'feriados_md' => ['01-01', '04-21', '05-01', '09-07', '10-12', '10-23', '11-02', '11-15', '12-25'],
    // Feriados moveis por ano (Carnaval, Sexta-feira Santa, Corpus Christi) — revisar anualmente
    'feriados' => [
        '2026-02-16', '2026-02-17', '2026-04-03', '2026-06-04',
        '2027-02-08', '2027-02-09', '2027-03-26', '2027-05-27',
    ],
];
