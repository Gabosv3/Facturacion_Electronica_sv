<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normativa de Cumplimiento DTE 2.0 (MH): el objeto "documento" del evento
     * de invalidación ahora exige los campos "telefono" y "correo" del receptor,
     * obligatorios para poder transmitir el evento (antes no eran requeridos).
     */
    public function up(): void
    {
        Schema::table('eventos_invalidacion', function (Blueprint $table) {
            $table->string('receptor_correo')->nullable()->after('solicitante_num_doc');
            $table->string('receptor_telefono', 20)->nullable()->after('receptor_correo');
        });
    }

    public function down(): void
    {
        Schema::table('eventos_invalidacion', function (Blueprint $table) {
            $table->dropColumn(['receptor_correo', 'receptor_telefono']);
        });
    }
};
