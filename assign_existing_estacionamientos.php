<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

use App\Models\EstacionamientoAdmin;
use App\Models\UsuarioReserva;

try {
    echo "=== ASIGNANDO ESTACIONAMIENTOS EXISTENTES A USUARIOS REGISTRADORES ===\n";
    
    // Obtener usuarios registradores
    $registradores = UsuarioReserva::where('role', 'registrador')->get();
    echo "Registradores encontrados: " . count($registradores) . "\n";
    
    if (count($registradores) === 0) {
        echo "No hay usuarios registradores. Creando uno de prueba...\n";
        $registrador = UsuarioReserva::create([
            'nombre' => 'Juan',
            'apellido' => 'Registrador',
            'email' => 'juan.registrador@test.com',
            'documento' => '12345678',
            'telefono' => '555-1234',
            'role' => 'registrador',
            'estado' => 'activo'
        ]);
        $registradores = collect([$registrador]);
        echo "Registrador creado con ID: " . $registrador->id . "\n";
    }
    
    // Obtener estacionamientos sin usuario asignado
    $estacionamientos = EstacionamientoAdmin::whereNull('usuario_id')->get();
    echo "Estacionamientos sin asignar: " . count($estacionamientos) . "\n";
    
    if (count($estacionamientos) === 0) {
        echo "No hay estacionamientos sin asignar.\n";
        exit(0);
    }
    
    // Asignar estacionamientos de forma round-robin
    $registradorIndex = 0;
    foreach ($estacionamientos as $estacionamiento) {
        $registrador = $registradores[$registradorIndex % count($registradores)];
        
        $estacionamiento->usuario_id = $registrador->id;
        $estacionamiento->save();
        
        echo "Asignado estacionamiento '{$estacionamiento->nombre}' (ID: {$estacionamiento->id}) al registrador '{$registrador->nombre} {$registrador->apellido}' (ID: {$registrador->id})\n";
        
        $registradorIndex++;
    }
    
    echo "\n=== ASIGNACIÓN COMPLETADA ===\n";
    echo "Total estacionamientos asignados: " . count($estacionamientos) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
