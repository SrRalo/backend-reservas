<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\UsuarioReserva;
use App\Models\EstacionamientoAdmin;

echo "=== ESTADO DE DATOS ===\n";
echo 'Usuarios registradores: ' . UsuarioReserva::where('role', 'registrador')->count() . "\n";
echo 'Estacionamientos totales: ' . EstacionamientoAdmin::count() . "\n"; 
echo 'Estacionamientos sin asignar: ' . EstacionamientoAdmin::whereNull('usuario_id')->count() . "\n";

// Si no hay registradores, crear uno
if (UsuarioReserva::where('role', 'registrador')->count() === 0) {
    echo "\nCreando usuario registrador de prueba...\n";
    $registrador = UsuarioReserva::create([
        'nombre' => 'Juan',
        'apellido' => 'Registrador',
        'email' => 'juan.registrador@test.com',
        'documento' => '12345678',
        'telefono' => '555-1234',
        'role' => 'registrador',
        'estado' => 'activo'
    ]);
    echo "Registrador creado con ID: " . $registrador->id . "\n";
}

// Asignar estacionamientos sin usuario_id al primer registrador
$registrador = UsuarioReserva::where('role', 'registrador')->first();
$estacionamientos = EstacionamientoAdmin::whereNull('usuario_id')->get();

echo "\nAsignando " . count($estacionamientos) . " estacionamientos al registrador ID: " . $registrador->id . "\n";

foreach ($estacionamientos as $estacionamiento) {
    $estacionamiento->usuario_id = $registrador->id;
    $estacionamiento->save();
    echo "- Asignado: " . $estacionamiento->nombre . "\n";
}

echo "\n=== ASIGNACIÓN COMPLETADA ===\n";
