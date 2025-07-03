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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('usuario_id');
            $table->decimal('monto', 10, 2);
            $table->enum('metodo_pago', ['tarjeta_credito', 'tarjeta_debito', 'efectivo', 'transferencia']);
            $table->enum('estado', ['pendiente', 'completado', 'fallido', 'reembolsado'])->default('pendiente');
            $table->timestamp('fecha_pago')->nullable();
            $table->string('referencia_pago')->nullable();
            $table->json('datos_pago')->nullable();
            $table->timestamps();

            // Foreign keys se agregarán después que existan las tablas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
