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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->string('placa', 20)->primary();
            $table->unsignedBigInteger('id_usuario_reserva')->nullable();
            $table->string('tipo', 50)->nullable();
            $table->dateTime('hora_entrada')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario_reserva')->references('id')->on('usuario_reserva')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
