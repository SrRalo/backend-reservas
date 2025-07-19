<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\UsuarioReserva;
use App\Models\EstacionamientoAdmin;

echo "=== CREANDO SEGUNDO REGISTRADOR Y ESTACIONAMIENTO ===\n";

// Crear segundo registrador
$registrador2 = UsuarioReserva::create([
    'nombre' => 'Maria',
    'apellido' => 'Registradora',
    'email' => 'maria.registradora2@test.com',
    'documento' => '87654322',
    'telefono' => '555-5679',
    'password' => bcrypt('password123'),
    'role' => 'registrador',
    'estado' => 'activo'
]);
echo "Segundo registrador creado con ID: " . $registrador2->id . "\n";

// Crear estacionamiento para el segundo registrador
$estacionamiento2 = EstacionamientoAdmin::create([
    'usuario_id' => $registrador2->id,
    'nombre' => 'Plaza Norte',
    'email' => 'plaza.norte2@test.com',
    'direccion' => 'Calle Norte 123',
    'espacios_totales' => 50,
    'espacios_disponibles' => 50,
    'precio_por_hora' => 8.00,
    'precio_mensual' => 200.00,
    'estado' => 'activo'
]);
echo "Segundo estacionamiento creado: " . $estacionamiento2->nombre . " (ID: " . $estacionamiento2->id . ")\n";

echo "\n=== ESTADO ACTUAL ===\n";
$registradores = UsuarioReserva::where('role', 'registrador')->get();

foreach ($registradores as $reg) {
    echo "Registrador: {$reg->nombre} {$reg->apellido} (ID: {$reg->id})\n";
    $estacionamientos = EstacionamientoAdmin::where('usuario_id', $reg->id)->get();
    echo "  Estacionamientos: " . count($estacionamientos) . "\n";
    foreach ($estacionamientos as $est) {
        echo "  - {$est->nombre}\n";
    }
    echo "\n";
}
