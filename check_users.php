<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\UsuarioReserva;

echo "=== USUARIOS EXISTENTES ===\n";
$usuarios = UsuarioReserva::all();
foreach ($usuarios as $user) {
    echo "ID: {$user->id}, Nombre: {$user->nombre} {$user->apellido}, Email: {$user->email}, Rol: {$user->role}\n";
}

echo "\n=== USUARIOS POR ROL ===\n";
echo "Admins: " . UsuarioReserva::where('role', 'admin')->count() . "\n";
echo "Registradores: " . UsuarioReserva::where('role', 'registrador')->count() . "\n";
echo "Reservadores: " . UsuarioReserva::where('role', 'reservador')->count() . "\n";
