<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Por si quedó un resto de un intento anterior fallido
        Schema::dropIfExists('cotizacion_items');
        Schema::dropIfExists('cotizaciones');

        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero_cotizacion')->unique();
            $table->date('fecha');

            // Datos del cliente (sin validación fiscal estricta, es informativo)
            $table->string('cliente_nombre');
            $table->string('cliente_documento')->nullable();
            $table->string('cliente_telefono')->nullable();
            $table->string('cliente_correo')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total_descuento', 12, 2)->default(0);
            $table->decimal('total_iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente, aprobada, rechazada, convertida
            $table->foreignId('factura_id')->nullable()->constrained('facturas')->onDelete('set null');

            $table->timestamps();
        });

        Schema::create('cotizacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            $table->integer('num_item');
            $table->string('codigo', 50)->nullable();
            $table->text('descripcion');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('monto_descuento', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
        Schema::dropIfExists('cotizaciones');
    }
};
