<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bca_ocorrencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bca_id')->constrained('bcas')->cascadeOnDelete();
            $table->foreignId('efetivo_id')->constrained('efetivos')->cascadeOnDelete();
            $table->text('snippet')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['bca_id', 'efetivo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bca_ocorrencias');
    }
};
