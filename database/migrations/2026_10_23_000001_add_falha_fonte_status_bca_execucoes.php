<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Postgres: enum do Laravel vira CHECK constraint
        DB::statement('ALTER TABLE bca_execucoes DROP CONSTRAINT IF EXISTS bca_execucoes_status_check');
        DB::statement("ALTER TABLE bca_execucoes ADD CONSTRAINT bca_execucoes_status_check CHECK (status IN ('sucesso', 'falha', 'sem_bca', 'falha_fonte'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bca_execucoes DROP CONSTRAINT IF EXISTS bca_execucoes_status_check');
        DB::statement("ALTER TABLE bca_execucoes ADD CONSTRAINT bca_execucoes_status_check CHECK (status IN ('sucesso', 'falha', 'sem_bca'))");
    }
};
