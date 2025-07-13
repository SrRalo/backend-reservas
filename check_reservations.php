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
    echo "=== VERIFICACIÓN DE TICKETS/RESERVAS ===\n\n";
    
    // Contar tickets por estado
    $tickets = $capsule->table('tickets')->get();
    echo "Total de tickets: " . count($tickets) . "\n";
    
    if (count($tickets) > 0) {
        $estados = $capsule->table('tickets')
            ->selectRaw('estado, COUNT(*) as count')
            ->groupBy('estado')
            ->get();
        
        echo "\nTickets por estado:\n";
        foreach ($estados as $estado) {
            echo "- {$estado->estado}: {$estado->count}\n";
        }
        
        echo "\nPrimeros 5 tickets:\n";
        $firstTickets = $capsule->table('tickets')->limit(5)->get();
        foreach ($firstTickets as $ticket) {
            echo "ID: {$ticket->id}, Estado: {$ticket->estado}, Usuario: {$ticket->usuario_id}, Precio: {$ticket->precio_total}\n";
        }
        
        // Buscar tickets completados/pagados
        $completedTickets = $capsule->table('tickets')
            ->whereIn('estado', ['finalizado', 'pagado'])
            ->get();
        
        echo "\nTickets completados/pagados: " . count($completedTickets) . "\n";
        
        if (count($completedTickets) > 0) {
            echo "Primeros tickets completados:\n";
            foreach ($completedTickets->take(3) as $ticket) {
                echo "ID: {$ticket->id}, Estado: {$ticket->estado}, Precio: {$ticket->precio_total}, Fecha entrada: {$ticket->fecha_entrada}\n";
            }
        }
    } else {
        echo "No hay tickets en la base de datos.\n";
        
        // Crear algunos tickets de prueba para mostrar datos en los reportes
        echo "\nCreando tickets de prueba...\n";
        
        $now = date('Y-m-d H:i:s');
        $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
        $twoDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));
        
        $sampleTickets = [
            [
                'usuario_id' => 1,
                'estacionamiento_id' => 1,
                'vehiculo_id' => 'ABC123',
                'fecha_entrada' => $twoDaysAgo,
                'fecha_salida' => date('Y-m-d H:i:s', strtotime($twoDaysAgo . ' +3 hours')),
                'precio_total' => 15.00,
                'estado' => 'finalizado',
                'created_at' => $twoDaysAgo,
                'updated_at' => $twoDaysAgo
            ],
            [
                'usuario_id' => 1,
                'estacionamiento_id' => 1,
                'vehiculo_id' => 'ABC123',
                'fecha_entrada' => $yesterday,
                'fecha_salida' => date('Y-m-d H:i:s', strtotime($yesterday . ' +2 hours')),
                'precio_total' => 10.00,
                'estado' => 'pagado',
                'created_at' => $yesterday,
                'updated_at' => $yesterday
            ],
            [
                'usuario_id' => 1,
                'estacionamiento_id' => 1,
                'vehiculo_id' => 'ABC123',
                'fecha_entrada' => $now,
                'fecha_salida' => date('Y-m-d H:i:s', strtotime($now . ' +4 hours')),
                'precio_total' => 20.00,
                'estado' => 'finalizado',
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        
        foreach ($sampleTickets as $ticket) {
            $capsule->table('tickets')->insert($ticket);
        }
        
        echo "Se han creado 3 tickets de prueba con estado finalizado/pagado.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
