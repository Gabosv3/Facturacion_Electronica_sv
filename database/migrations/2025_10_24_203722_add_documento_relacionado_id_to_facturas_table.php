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
        Schema::table('facturas', function (Blueprint $table) {
            // Agregar foreign key al documento relacionado
            $table->unsignedBigInteger('documento_relacionado_id')->nullable()->after('tipo_dte');

            // Agregar índice para mejor rendimiento
            $table->index('documento_relacionado_id');

            // Opcional: agregar foreign key constraint
            // $table->foreign('documento_relacionado_id')->references('id')->on('facturas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex(['documento_relacionado_id']);
            $table->dropColumn('documento_relacionado_id');
        });
    }
};
