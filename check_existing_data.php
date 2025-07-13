<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Configurar la conexión a la base de datos
$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'pgsql',
    'host'      => '127.0.0.1',
    'port'      => '5432',
    'database'  => 'mibase',
    'username'  => 'root123',
    'password'  => 'root123',
    'charset'   => 'utf8',
    'prefix'    => '',
    'schema'    => 'public',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    echo "=== VERIFICACIÓN DE VEHÍCULOS Y TICKETS EXISTENTES ===\n\n";
    
    // Verificar vehículos existentes
    $vehiculos = $capsule->table('vehiculos')->get();
    echo "Vehículos existentes: " . count($vehiculos) . "\n";
    
    if (count($vehiculos) > 0) {
        echo "Primeros vehículos:\n";
        foreach ($vehiculos->take(5) as $vehiculo) {
            echo "- Placa: {$vehiculo->placa}, Tipo: {$vehiculo->tipo}, Usuario: {$vehiculo->usuario_id}\n";
        }
    } else {
        echo "No hay vehículos registrados.\n";
    }
    
    // Verificar tickets existentes para ver qué vehiculo_id usan
    $tickets = $capsule->table('tickets')->get();
    echo "\nTickets existentes: " . count($tickets) . "\n";
    
    if (count($tickets) > 0) {
        echo "Vehículos en tickets:\n";
        foreach ($tickets as $ticket) {
            echo "- Ticket ID: {$ticket->id}, Vehiculo ID: {$ticket->vehiculo_id}, Usuario: {$ticket->usuario_id}\n";
        }
    }
    
    // Verificar usuarios
    $usuarios = $capsule->table('usuario_reserva')->get();
    echo "\nUsuarios existentes: " . count($usuarios) . "\n";
    
    if (count($usuarios) > 0) {
        echo "Primeros usuarios:\n";
        foreach ($usuarios->take(3) as $usuario) {
            echo "- ID: {$usuario->id}, Nombre: {$usuario->nombre}, Email: {$usuario->email}\n";
        }
    }
    
    // Verificar estacionamientos
    $estacionamientos = $capsule->table('estacionamientoadmin')->get();
    echo "\nEstacionamientos existentes: " . count($estacionamientos) . "\n";
    
    if (count($estacionamientos) > 0) {
        echo "Primeros estacionamientos:\n";
        foreach ($estacionamientos->take(3) as $est) {
            echo "- ID: {$est->id}, Nombre: {$est->nombre}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
