<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EstacionamientoAdmin;

echo "=== ESTACIONAMIENTOS EXISTENTES ===\n";
$estacionamientos = EstacionamientoAdmin::all();
foreach ($estacionamientos as $est) {
    $usuario = $est->usuario_id ? "Usuario {$est->usuario_id}" : "Sin asignar";
    echo "ID: {$est->id}, Nombre: {$est->nombre}, Estado: {$est->estado}, Propietario: {$usuario}\n";
}

echo "\nTotal estacionamientos: " . count($estacionamientos) . "\n";
echo "Estacionamientos activos: " . EstacionamientoAdmin::where('estado', 'activo')->count() . "\n";
