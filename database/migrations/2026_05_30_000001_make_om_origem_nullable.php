<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('efetivos', function (Blueprint $table) {
            $table->string('om_origem')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('efetivos', function (Blueprint $table) {
            $table->string('om_origem')->nullable(false)->change();
        });
    }
};
