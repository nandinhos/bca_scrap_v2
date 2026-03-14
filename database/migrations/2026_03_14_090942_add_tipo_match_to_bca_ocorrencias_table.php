<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bca_ocorrencias', function (Blueprint $table) {
            $table->string('tipo_match')->nullable()->after('efetivo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bca_ocorrencias', function (Blueprint $table) {
            $table->dropColumn('tipo_match');
        });
    }
};
