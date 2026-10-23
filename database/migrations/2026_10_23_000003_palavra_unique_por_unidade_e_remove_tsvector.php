<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Palavra-chave e por OM: a mesma palavra pode existir em unidades distintas
        Schema::table('palavras_chaves', function (Blueprint $table) {
            $table->dropUnique(['palavra']);
            $table->unique(['palavra', 'unidade_id']);
        });

        // texto_tsvector gerado nao e usado por nenhuma busca (matching e textual
        // sobre texto_completo); manter a coluna so custa disco e escrita.
        if (Schema::hasColumn('bcas', 'texto_tsvector')) {
            DB::statement('ALTER TABLE bcas DROP COLUMN IF EXISTS texto_tsvector');
        }
    }

    public function down(): void
    {
        Schema::table('palavras_chaves', function (Blueprint $table) {
            $table->dropUnique(['palavra', 'unidade_id']);
            $table->unique(['palavra']);
        });
    }
};
