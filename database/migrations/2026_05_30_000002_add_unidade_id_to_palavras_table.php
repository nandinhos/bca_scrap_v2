<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('palavras_chaves', function (Blueprint $table) {
            $table->foreignId('unidade_id')
                ->nullable()
                ->constrained('palavras_chaves')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('palavras_chaves', function (Blueprint $table) {
            $table->dropForeign(['unidade_id']);
            $table->dropColumn('unidade_id');
        });
    }
};
