<?php

return [
    // CENDOC intranet base (must include trailing slash)
    'base_url' => env('BCA_BASE_URL', 'http://www.cendoc.intraer/sisbca/consulta_bca/'),
    // ICEA intranet base (fallback)
    'icea_url' => env('BCA_ICEA_URL', 'http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/'),
    'search_chunk_size' => (int) env('BCA_SEARCH_CHUNK_SIZE', 10),
    'search_timeout' => (int) env('BCA_SEARCH_TIMEOUT', 10),
    'search_retry' => (int) env('BCA_SEARCH_RETRY', 2),
    'max_pdf_size_mb' => (int) env('BCA_MAX_PDF_SIZE_MB', 50),
    // SAD email para envio do compilado
    'sad_email' => env('BCA_SAD_EMAIL', 'sad.gacpac@fab.mil.br'),
];
