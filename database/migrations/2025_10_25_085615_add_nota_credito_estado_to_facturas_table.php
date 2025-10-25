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
            // Campo para registrar el estado cuando una factura tiene nota de crédito asociada
            // Valores posibles: null (sin nota de crédito), 'anulacion', 'ajuste_precio', 'devolucion'
            $table->string('nota_credito_estado', 20)->nullable()->after('tipo_documento');

            // Campo para almacenar el ID de la nota de crédito asociada (si existe)
            $table->unsignedBigInteger('nota_credito_id')->nullable()->after('nota_credito_estado');

            // Índice para búsquedas rápidas
            $table->index('nota_credito_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex(['nota_credito_estado']);
            $table->dropColumn(['nota_credito_estado', 'nota_credito_id']);
        });
    }
};
