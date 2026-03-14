<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bcas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20);
            $table->date('data')->unique();
            $table->string('url', 500)->nullable();
            $table->longText('texto_completo')->nullable();
            $table->timestamp('processado_em')->nullable();
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

        DB::statement("
            ALTER TABLE bcas
            ADD COLUMN texto_tsvector tsvector
            GENERATED ALWAYS AS (to_tsvector('portuguese_unaccent', COALESCE(texto_completo, ''))) STORED
        ");

        DB::statement("CREATE INDEX idx_bcas_texto_tsvector ON bcas USING GIN(texto_tsvector)");
        DB::statement("CREATE INDEX idx_bcas_data ON bcas(data)");
    }

    public function down(): void
    {
        Schema::dropIfExists('bcas');
    }
};
