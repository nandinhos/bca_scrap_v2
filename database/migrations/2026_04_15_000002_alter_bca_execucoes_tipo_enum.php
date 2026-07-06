<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE bca_execucoes DROP CONSTRAINT bca_execucoes_tipo_check');
        DB::statement("ALTER TABLE bca_execucoes ADD CONSTRAINT bca_execucoes_tipo_check CHECK (tipo IN ('automatica', 'manual', 'compilado_sad'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bca_execucoes DROP CONSTRAINT bca_execucoes_tipo_check');
        DB::statement("ALTER TABLE bca_execucoes ADD CONSTRAINT bca_execucoes_tipo_check CHECK (tipo IN ('automatica', 'manual'))");
    }
};
