<?php

/*
|--------------------------------------------------------------------------
| Isolamento do ambiente de teste
|--------------------------------------------------------------------------
|
| Os containers definem DB_DATABASE, QUEUE_CONNECTION, MAIL_MAILER, APP_ENV etc
| no environment do processo, e o PHP CLI publica essas variaveis em $_SERVER.
| O Env do Laravel consulta $_SERVER ANTES de $_ENV/putenv, entao os <env> do
| phpunit.xml — mesmo com force="true" — nunca vencem: a suite acabava rodando
| contra o banco de PRODUCAO (bca_db), com fila em banco e SMTP real.
|
| Por isso os overrides precisam ser aplicados aqui, antes de o Laravel bootar.
|
*/

$overrides = [
    'APP_ENV' => 'testing',
    'DB_DATABASE' => 'bca_test',
    'CACHE_STORE' => 'array',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'BCRYPT_ROUNDS' => '4',
    'TELESCOPE_ENABLED' => 'false',
];

foreach ($overrides as $chave => $valor) {
    $_SERVER[$chave] = $valor;
    $_ENV[$chave] = $valor;
    putenv("{$chave}={$valor}");
}

require __DIR__.'/../vendor/autoload.php';
