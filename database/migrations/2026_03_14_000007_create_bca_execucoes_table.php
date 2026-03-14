<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bca_execucoes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['automatica', 'manual'])->default('automatica');
            $table->timestamp('data_execucao');
            $table->enum('status', ['sucesso', 'falha', 'sem_bca'])->default('sem_bca');
            $table->text('mensagem')->nullable();
            $table->integer('registros_processados')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('tipo');
            $table->index('data_execucao');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bca_execucoes');
    }
};
