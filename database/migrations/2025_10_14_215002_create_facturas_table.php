<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_control')->unique();
            $table->string('codigo_generacion')->unique();
            $table->string('tipo_dte', 2)->default('01');
            $table->date('fecha_emision');
            $table->time('hora_emision');
            $table->string('receptor_tipo_documento', 2);
            $table->string('receptor_num_documento', 20);
            $table->string('receptor_nombre');
            $table->string('receptor_nrc', 20)->nullable();
            $table->string('receptor_actividad', 10)->nullable();
            $table->text('receptor_direccion');
            $table->string('receptor_telefono', 20)->nullable();
            $table->string('receptor_correo')->nullable();
            $table->decimal('total_gravada', 12, 2)->default(0);
            $table->decimal('total_exenta', 12, 2)->default(0);
            $table->decimal('total_no_sujeto', 12, 2)->default(0);
            $table->decimal('total_descuento', 12, 2)->default(0);
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('total_iva', 12, 2)->default(0);
            $table->decimal('total_pagar', 12, 2)->default(0);
            $table->string('total_letras');
            $table->tinyInteger('condicion_operacion')->default(1);
            $table->json('dte_json')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente, firmado, enviado_mh, firmado_sin_enviar, error_firma, rechazado
            $table->text('respuesta_mh')->nullable();
            $table->timestamps();
        });

        Schema::create('factura_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained()->onDelete('cascade');
            $table->integer('num_item');
            $table->string('codigo', 50);
            $table->text('descripcion');
            $table->decimal('cantidad', 10, 2);
            $table->integer('uni_medida')->default(59);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('monto_descuento', 12, 2)->default(0);
            $table->decimal('venta_gravada', 12, 2)->default(0);
            $table->decimal('venta_exenta', 12, 2)->default(0);
            $table->decimal('venta_no_sujeta', 12, 2)->default(0);
            $table->decimal('iva_item', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_items');
        Schema::dropIfExists('facturas');
    }
};