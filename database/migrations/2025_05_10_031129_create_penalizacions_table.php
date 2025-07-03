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
        Schema::create('penalizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('motivo', 255);
            $table->enum('tipo', ['tiempo_excedido', 'dano_propiedad', 'mal_estacionamiento'])->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->unsignedBigInteger('usuario_reserva_id');
            $table->unsignedBigInteger('ticket_id');
            $table->enum('estado', ['activa', 'pagada', 'cancelada'])->default('activa');
            $table->decimal('monto', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('usuario_reserva_id')->references('id')->on('usuario_reserva')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalizacions');
    }
};
