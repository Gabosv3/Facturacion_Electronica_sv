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
            // Hacer campos del receptor nullable para soportar "Clientes Varios"
            $table->string('receptor_tipo_documento', 2)->nullable()->change();
            $table->string('receptor_num_documento', 20)->nullable()->change();
            $table->text('receptor_direccion')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Revertir los campos a NOT NULL
            $table->string('receptor_tipo_documento', 2)->nullable(false)->change();
            $table->string('receptor_num_documento', 20)->nullable(false)->change();
            $table->text('receptor_direccion')->nullable(false)->change();
        });
    }
};
