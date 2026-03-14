<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('efetivos', function (Blueprint $table) {
            $table->id();
            $table->string('saram', 8)->unique();
            $table->string('nome_guerra', 50);
            $table->string('nome_completo', 200);
            $table->string('posto', 20);
            $table->string('especialidade', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('om_origem', 50)->default('GAC-PAC');
            $table->boolean('ativo')->default(true);
            $table->boolean('oculto')->default(false);
            $table->timestamps();
        });

        // Ensure the portuguese_unaccent FTS config exists (idempotent)
        DB::statement("CREATE EXTENSION IF NOT EXISTS unaccent");
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_ts_config WHERE cfgname = 'portuguese_unaccent'
                ) THEN
                    CREATE TEXT SEARCH CONFIGURATION portuguese_unaccent (COPY = portuguese);
                END IF;
            END
            \$\$
        ");

        // Add generated tsvector column for FTS
        DB::statement("
            ALTER TABLE efetivos
            ADD COLUMN nome_tsvector tsvector
            GENERATED ALWAYS AS (to_tsvector('portuguese_unaccent', nome_completo)) STORED
        ");

        DB::statement("CREATE INDEX idx_efetivos_nome_tsvector ON efetivos USING GIN(nome_tsvector)");
        DB::statement("CREATE INDEX idx_efetivos_ativo ON efetivos(ativo)");
        DB::statement("CREATE INDEX idx_efetivos_saram ON efetivos(saram)");
    }

    public function down(): void
    {
        Schema::dropIfExists('efetivos');
    }
};
