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
        Schema::table('estacionamientoadmin', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_id')->nullable()->after('id');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->index('usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estacionamientoadmin', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropIndex(['usuario_id']);
            $table->dropColumn('usuario_id');
        });
    }
};
