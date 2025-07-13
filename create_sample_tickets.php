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
    echo "=== CREANDO TICKETS DE PRUEBA PARA REPORTES ===\n\n";
    
    // Verificar si ya existen tickets completados
    $completedTickets = $capsule->table('tickets')
        ->whereIn('estado', ['finalizado', 'pagado'])
        ->count();
    
    if ($completedTickets > 0) {
        echo "Ya existen $completedTickets tickets completados.\n";
        echo "¿Desea crear más tickets de prueba? (presione Ctrl+C para cancelar)\n";
        sleep(3);
    }
    
    // Crear tickets de prueba para los últimos 7 días usando vehículos existentes
    $ticketsToCreate = [
        [
            'usuario_id' => 2, // Usuario existente
            'estacionamiento_id' => 1,
            'vehiculo_id' => 'MBD1717', // Usar vehículo existente
            'codigo_ticket' => 'TICKET-' . uniqid(),
            'fecha_entrada' => date('Y-m-d H:i:s', strtotime('-6 days')),
            'fecha_salida' => date('Y-m-d H:i:s', strtotime('-6 days +3 hours')),
            'tipo_reserva' => 'por_horas',
            'precio_total' => 15.00,
            'estado' => 'finalizado',
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-6 days'))
        ],
        [
            'usuario_id' => 2,
            'estacionamiento_id' => 1,
            'vehiculo_id' => 'MBD1717',
            'codigo_ticket' => 'TICKET-' . uniqid(),
            'fecha_entrada' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'fecha_salida' => date('Y-m-d H:i:s', strtotime('-5 days +2 hours')),
            'tipo_reserva' => 'por_horas',
            'precio_total' => 10.00,
            'estado' => 'pagado',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'usuario_id' => 2,
            'estacionamiento_id' => 1,
            'vehiculo_id' => 'MBD1717',
            'codigo_ticket' => 'TICKET-' . uniqid(),
            'fecha_entrada' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'fecha_salida' => date('Y-m-d H:i:s', strtotime('-4 days +4 hours')),
            'tipo_reserva' => 'por_horas',
            'precio_total' => 20.00,
            'estado' => 'finalizado',
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
        ],
        [
            'usuario_id' => 2,
            'estacionamiento_id' => 1,
            'vehiculo_id' => 'MBD1717',
            'codigo_ticket' => 'TICKET-' . uniqid(),
            'fecha_entrada' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'fecha_salida' => date('Y-m-d H:i:s', strtotime('-3 days +1 hour')),
            'tipo_reserva' => 'por_horas',
            'precio_total' => 5.00,
            'estado' => 'pagado',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'usuario_id' => 3, // Usuario del segundo vehículo
            'estacionamiento_id' => 1,
            'vehiculo_id' => 'GBD1818',
            'codigo_ticket' => 'TICKET-' . uniqid(),
            'fecha_entrada' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'fecha_salida' => date('Y-m-d H:i:s', strtotime('-2 days +6 hours')),
            'tipo_reserva' => 'por_horas',
            'precio_total' => 30.00,
            'estado' => 'finalizado',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'usuario_id' => 2,
            'estacionamiento_id' => 1,
            'vehiculo_id' => 'MBD1717',
            'codigo_ticket' => 'TICKET-' . uniqid(),
            'fecha_entrada' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'fecha_salida' => date('Y-m-d H:i:s', strtotime('-1 day +2 hours')),
            'tipo_reserva' => 'por_horas',
            'precio_total' => 10.00,
            'estado' => 'pagado',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'usuario_id' => 3,
            'estacionamiento_id' => 1,
            'vehiculo_id' => 'GBD1818',
            'codigo_ticket' => 'TICKET-' . uniqid(),
            'fecha_entrada' => date('Y-m-d H:i:s'),
            'fecha_salida' => date('Y-m-d H:i:s', strtotime('+3 hours')),
            'tipo_reserva' => 'por_horas',
            'precio_total' => 15.00,
            'estado' => 'finalizado',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    $inserted = 0;
    foreach ($ticketsToCreate as $ticket) {
        try {
            $capsule->table('tickets')->insert($ticket);
            $inserted++;
            echo "✓ Ticket creado: Usuario {$ticket['usuario_id']}, {$ticket['estado']}, \${$ticket['precio_total']}, {$ticket['fecha_entrada']}\n";
        } catch (Exception $e) {
            echo "✗ Error creando ticket: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "Tickets de prueba creados: $inserted\n";
    
    // Mostrar estadísticas actualizadas
    $totalTickets = $capsule->table('tickets')->count();
    $completedNow = $capsule->table('tickets')
        ->whereIn('estado', ['finalizado', 'pagado'])
        ->count();
    
    $totalIncome = $capsule->table('tickets')
        ->whereIn('estado', ['finalizado', 'pagado'])
        ->whereNotNull('precio_total')
        ->sum('precio_total');
    
    echo "Total de tickets en BD: $totalTickets\n";
    echo "Tickets completados/pagados: $completedNow\n";
    echo "Ingresos totales: \$$totalIncome\n";
    
    echo "\nAhora la página de reportes debería mostrar datos reales.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
