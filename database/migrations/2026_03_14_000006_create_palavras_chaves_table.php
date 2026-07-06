<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('palavras_chaves', function (Blueprint $table) {
            $table->id();
            $table->string('palavra', 100)->unique();
            $table->char('cor', 6)->default('FFFFFF');
            $table->boolean('ativa')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('palavras_chaves');
    }
};
