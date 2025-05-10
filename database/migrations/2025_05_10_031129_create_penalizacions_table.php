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
            $table->id('ID');
            $table->string('Motivo', 100);
            $table->timestamp('Fecha')->useCurrent();
            $table->unsignedBigInteger('usuario_reserva_id');
            $table->unsignedBigInteger('ticket_id_ticket');
            $table->boolean('Estado')->default(false);
            $table->timestamps();

            $table->foreign('usuario_reserva_id')->references('id')->on('usuario_reserva');
            $table->foreign('ticket_id_ticket')->references('id_ticket')->on('ticket');
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
