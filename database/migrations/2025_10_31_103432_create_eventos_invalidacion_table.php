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
        Schema::create('eventos_invalidacion', function (Blueprint $table) {
            $table->id();

            // Relación con la factura a invalidar
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');

            // Identificación del evento
            $table->uuid('codigo_invalidacion')->unique();
            $table->integer('version')->default(1);
            $table->string('ambiente', 2);

            // Fecha del evento
            $table->timestamp('fecha_evento');

            // Tipo de invalidación (CAT-026)
            $table->string('tipo_invalidacion', 1);
            $table->text('motivo_invalidacion');

            // Documento que reemplaza (opcional, null si se rescinde operación)
            $table->foreignId('factura_reemplazo_id')->nullable()->constrained('facturas')->onDelete('set null');
            $table->uuid('codigo_generacion_reemplazo')->nullable();

            // Responsable de invalidar (Emisor)
            $table->string('responsable_nombre');
            $table->string('responsable_tipo_doc', 2);
            $table->string('responsable_num_doc', 20);

            // Solicitante de invalidación
            $table->string('solicitante_nombre');
            $table->string('solicitante_tipo_doc', 2);
            $table->string('solicitante_num_doc', 20);

            // JSON del evento firmado
            $table->json('evento_json')->nullable();
            $table->longText('evento_firmado')->nullable();

            // Estado del evento
            $table->enum('estado', ['pendiente', 'firmado', 'enviado', 'procesado', 'error'])->default('pendiente');

            // Respuesta del MH
            $table->json('respuesta_mh')->nullable();
            $table->string('sello_recepcion')->nullable();
            $table->timestamp('fecha_procesamiento')->nullable();

            $table->timestamps();

            // Índices
            $table->index('codigo_invalidacion');
            $table->index('estado');
            $table->index('fecha_evento');
        });

        // Agregar campo a la tabla facturas para indicar si está invalidada
        Schema::table('facturas', function (Blueprint $table) {
            $table->boolean('invalidada')->default(false)->after('estado');
            $table->foreignId('evento_invalidacion_id')->nullable()->constrained('eventos_invalidacion')->onDelete('set null')->after('invalidada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['evento_invalidacion_id']);
            $table->dropColumn(['invalidada', 'evento_invalidacion_id']);
        });

        Schema::dropIfExists('eventos_invalidacion');
    }
};
