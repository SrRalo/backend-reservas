<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\UsuarioReserva;
use App\Models\EstacionamientoAdmin;

echo "=== ESTADO ACTUAL DE DATOS ===\n";
$registradores = UsuarioReserva::where('role', 'registrador')->get();

foreach ($registradores as $reg) {
    echo "Registrador: {$reg->nombre} {$reg->apellido} (ID: {$reg->id})\n";
    $estacionamientos = EstacionamientoAdmin::where('usuario_id', $reg->id)->get();
    echo "  Estacionamientos: " . count($estacionamientos) . "\n";
    foreach ($estacionamientos as $est) {
        echo "  - {$est->nombre} (ID: {$est->id})\n";
    }
    echo "\n";
}

// Si algún registrador no tiene estacionamientos, crear uno
foreach ($registradores as $reg) {
    $count = EstacionamientoAdmin::where('usuario_id', $reg->id)->count();
    if ($count === 0) {
        $estacionamiento = EstacionamientoAdmin::create([
            'usuario_id' => $reg->id,
            'nombre' => 'Plaza de ' . $reg->nombre,
            'email' => strtolower($reg->nombre) . '.plaza@test.com',
            'direccion' => 'Calle ' . $reg->nombre . ' 123',
            'espacios_totales' => 30,
            'espacios_disponibles' => 30,
            'precio_por_hora' => 5.00,
            'precio_mensual' => 150.00,
            'estado' => 'activo'
        ]);
        echo "Creado estacionamiento para {$reg->nombre}: {$estacionamiento->nombre}\n";
    }
}
