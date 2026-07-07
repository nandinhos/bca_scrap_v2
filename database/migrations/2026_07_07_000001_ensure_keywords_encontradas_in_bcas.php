<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bcas', 'keywords_encontradas')) {
            Schema::table('bcas', function (Blueprint $table) {
                $table->json('keywords_encontradas')->nullable()->after('analisado_em');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bcas', 'keywords_encontradas')) {
            Schema::table('bcas', function (Blueprint $table) {
                $table->dropColumn('keywords_encontradas');
            });
        }
    }
};
