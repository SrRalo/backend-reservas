<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Renombrar la tabla usuario_reserva a usuarios
        Schema::rename('usuario_reserva', 'usuarios');
        
        // 2. Agregar el campo role a la tabla usuarios
        Schema::table('usuarios', function (Blueprint $table) {
            $table->enum('role', ['admin', 'registrador', 'reservador'])
                  ->default('reservador')
                  ->after('email')
                  ->comment('Rol del usuario en el sistema');
        });
        
        // 3. Actualizar roles existentes basados en email (migración de datos)
        DB::statement("
            UPDATE usuarios 
            SET role = CASE 
                WHEN LOWER(email) LIKE '%admin%' OR LOWER(email) LIKE '%administrador%' THEN 'admin'
                WHEN LOWER(email) LIKE '%registrador%' OR LOWER(email) LIKE '%owner%' OR LOWER(email) LIKE '%propietario%' OR LOWER(email) LIKE '%dueño%' THEN 'registrador'
                ELSE 'reservador'
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Eliminar el campo role
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        
        // 2. Renombrar la tabla usuarios de vuelta a usuario_reserva
        Schema::rename('usuarios', 'usuario_reserva');
    }
};
