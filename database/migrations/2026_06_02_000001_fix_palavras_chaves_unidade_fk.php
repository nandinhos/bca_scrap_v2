<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('palavras_chaves', function (Blueprint $table) {
            // Remove a FK errada (auto-referencia criada em 2026_05_30_000002)
            $table->dropForeign(['unidade_id']);

            // Recria apontando para unidades.id
            $table->foreign('unidade_id')
                ->references('id')
                ->on('unidades')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('palavras_chaves', function (Blueprint $table) {
            $table->dropForeign(['unidade_id']);

            $table->foreign('unidade_id')
                ->references('id')
                ->on('palavras_chaves')
                ->cascadeOnDelete();
        });
    }
};
