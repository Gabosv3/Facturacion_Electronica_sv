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
        Schema::table('eventos_contingencia', function (Blueprint $table) {
            $table->json('evento_json')->nullable()->after('motivo_contingencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventos_contingencia', function (Blueprint $table) {
            $table->dropColumn('evento_json');
        });
    }
};
