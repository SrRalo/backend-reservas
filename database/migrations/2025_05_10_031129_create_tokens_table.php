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
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario_reserva')->nullable();
            $table->string('token', 255);
            $table->boolean('estado')->default(true);
            $table->timestamp('tiempo')->useCurrent();
            $table->timestamps();

            $table->foreign('id_usuario_reserva')->references('id')->on('usuario_reserva')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
