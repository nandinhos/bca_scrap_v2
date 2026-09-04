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

/*
|--------------------------------------------------------------------------
| Criacao do banco de teste
|--------------------------------------------------------------------------
|
| O banco bca_test nao e criado pelo instalador: docker/postgres/init.sql so
| roda na primeira inicializacao do volume, o que nao cobre quem ja tem o
| projeto instalado. Sem o banco, a trava do TestCase aborta a suite inteira.
|
| Criamos sob demanda, uma vez por execucao. Falha em silencio se nao houver
| privilegio — nesse caso a trava do TestCase da a mensagem clara.
|
| As extensoes (unaccent, portuguese_unaccent) nao precisam de tratamento aqui:
| a migration 2026_03_14_000004_create_bcas_table cuida das duas.
|
*/

(function (string $banco): void {
    $host = $_SERVER['DB_HOST'] ?? 'postgres';
    $porta = $_SERVER['DB_PORT'] ?? '5432';
    $usuario = $_SERVER['DB_USERNAME'] ?? 'bca_user';
    $senha = $_SERVER['DB_PASSWORD'] ?? '';

    try {
        // Conecta no banco de manutencao, que sempre existe.
        $pdo = new PDO(
            "pgsql:host={$host};port={$porta};dbname=postgres",
            $usuario,
            $senha,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );

        $existe = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
        $existe->execute([$banco]);

        if ($existe->fetchColumn() === false) {
            // Identificador nao aceita bind e usa aspas duplas, nao $pdo->quote().
            // O nome vem de constante deste bootstrap; a validacao e so uma
            // salvaguarda caso alguem passe a torna-lo configuravel.
            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $banco)) {
                throw new RuntimeException("nome de banco invalido: {$banco}");
            }

            $pdo->exec('CREATE DATABASE "'.$banco.'"');
            fwrite(STDOUT, "Banco de teste [{$banco}] criado.".PHP_EOL);
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "Aviso: nao foi possivel garantir o banco [{$banco}]: {$e->getMessage()}".PHP_EOL);
    }
})($overrides['DB_DATABASE']);
