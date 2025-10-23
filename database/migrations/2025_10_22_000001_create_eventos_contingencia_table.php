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
        Schema::create('eventos_contingencia', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_evento', 100)->unique(); // UUID del evento
            $table->string('nit_emisor', 20);
            $table->integer('tipo_contingencia'); // 1-5 según catálogo MH
            $table->text('motivo_contingencia'); // Descripción del motivo
            $table->dateTime('fecha_hora_inicio'); // Inicio de contingencia
            $table->dateTime('fecha_hora_fin')->nullable(); // Fin de contingencia
            $table->integer('cantidad_dte')->default(0); // Cantidad de DTEs en contingencia
            $table->string('estado', 50)->default('activo'); // activo, cerrado, enviado, procesado
            $table->text('respuesta_mh')->nullable(); // Respuesta del MH al enviar evento
            $table->string('codigo_lote', 100)->nullable(); // Código de lote retornado por MH
            $table->json('detalle_lote')->nullable(); // Detalle del procesamiento del lote
            $table->timestamps();

            $table->index('nit_emisor');
            $table->index('estado');
            $table->index('fecha_hora_inicio');
        });

        // Agregar campos de contingencia a la tabla facturas
        Schema::table('facturas', function (Blueprint $table) {
            $table->boolean('en_contingencia')->default(false)->after('estado');
            $table->foreignId('evento_contingencia_id')->nullable()->after('en_contingencia')
                ->constrained('eventos_contingencia')->nullOnDelete();
            $table->string('estado_lote', 50)->nullable()->after('evento_contingencia_id'); // RECIBIDO, PROCESADO, ERROR

            $table->index('en_contingencia');
            $table->index('evento_contingencia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['evento_contingencia_id']);
            $table->dropColumn(['en_contingencia', 'evento_contingencia_id', 'estado_lote']);
        });

        Schema::dropIfExists('eventos_contingencia');
    }
};
