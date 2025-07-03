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
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('vehiculo_id', 20);
            $table->unsignedBigInteger('estacionamiento_id');
            $table->string('codigo_ticket', 50)->unique();
            $table->timestamp('fecha_entrada');
            $table->timestamp('fecha_salida')->nullable();
            $table->enum('tipo_reserva', ['por_horas', 'mensual'])->default('por_horas');
            $table->enum('estado', ['activo', 'finalizado', 'cancelado', 'pagado'])->default('activo');
            $table->decimal('precio_total', 10, 2)->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('usuario_id')->references('id')->on('usuario_reserva')->onDelete('cascade');
            $table->foreign('vehiculo_id')->references('placa')->on('vehiculos')->onDelete('cascade');
            $table->foreign('estacionamiento_id')->references('id')->on('estacionamientoadmin')->onDelete('cascade');
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
