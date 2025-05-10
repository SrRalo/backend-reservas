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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id('id_ticket');
            $table->unsignedBigInteger('id_usuario_reserva');
            $table->unsignedBigInteger('id_usuario_registrador');
            $table->string('vehiculo_placa', 20);
            $table->unsignedBigInteger('pagos_id');
            $table->boolean('Excepcion')->default(false);
            $table->timestamps();

            $table->foreign('id_usuario_reserva')->references('id')->on('usuario_reserva')->nullOnDelete();
            $table->foreign('id_usuario_registrador')->references('id')->on('estacionamientoadmin')->nullOnDelete();
            $table->foreign('vehiculo_placa')->references('placa')->on('vehiculo');
            $table->foreign('pagos_id')->references('id')->on('pagos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
