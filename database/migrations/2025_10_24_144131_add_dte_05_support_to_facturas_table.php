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
            // Campos para documento relacionado (requerido para DTE-05 Nota de Crédito)
            $table->string('documento_relacionado_tipo_dte', 2)->nullable()->after('tipo_dte');
            $table->string('documento_relacionado_numero_control')->nullable()->after('documento_relacionado_tipo_dte');
            $table->string('documento_relacionado_codigo_generacion', 36)->nullable()->after('documento_relacionado_numero_control');
            $table->date('documento_relacionado_fecha_emision')->nullable()->after('documento_relacionado_codigo_generacion');

            // Motivo de la nota de crédito (CAT-026)
            $table->string('tipo_documento', 2)->nullable()->after('documento_relacionado_fecha_emision');

            // Actualizar tipo_dte para aceptar '05'
            $table->string('tipo_dte', 2)->default('01')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'documento_relacionado_tipo_dte',
                'documento_relacionado_numero_control',
                'documento_relacionado_codigo_generacion',
                'documento_relacionado_fecha_emision',
                'tipo_documento',
            ]);
        });
    }
};
