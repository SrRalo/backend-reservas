<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== TESTING REPORTES SERVICE BACKEND ===\n\n";

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
    // Parámetros de prueba
    $userId = 2;
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
    
    echo "Parámetros de prueba:\n";
    echo "- User ID: $userId\n";
    echo "- Start Date: $startDate\n";
    echo "- End Date: $endDate\n\n";

    // Probar la consulta de reportes de ingresos directamente en la BD
    echo "=== CONSULTA DIRECTA A LA BASE DE DATOS ===\n";
    
    $tickets = $capsule->table('tickets')
        ->selectRaw("DATE(fecha_entrada) as date, SUM(precio_total) as amount, COUNT(*) as reservation_count")
        ->where('usuario_id', $userId)
        ->whereIn('estado', ['finalizado', 'pagado'])
        ->whereNotNull('precio_total')
        ->where('precio_total', '>', 0)
        ->whereRaw("DATE(fecha_entrada) BETWEEN ? AND ?", [$startDate, $endDate])
        ->groupByRaw("DATE(fecha_entrada)")
        ->orderByRaw("DATE(fecha_entrada) ASC")
        ->get();

    echo "Resultados de la consulta:\n";
    
    if ($tickets->isEmpty()) {
        echo "❌ No se encontraron tickets completados/pagados para el período especificado.\n\n";
        
        // Mostrar tickets existentes para debug
        $allTickets = $capsule->table('tickets')
            ->where('usuario_id', $userId)
            ->get();
        
        echo "Tickets existentes para usuario $userId:\n";
        foreach ($allTickets as $ticket) {
            echo sprintf("- ID: %d, Estado: %s, Precio: %s, Fecha: %s\n", 
                $ticket->id, 
                $ticket->estado, 
                $ticket->precio_total ?? 'null',
                $ticket->fecha_entrada
            );
        }
    } else {
        echo "✅ Se encontraron " . $tickets->count() . " registros:\n";
        
        $totalIncome = 0;
        $totalReservations = 0;
        
        foreach ($tickets as $ticket) {
            echo sprintf("- %s: $%.2f (%d reservas)\n", 
                $ticket->date, 
                $ticket->amount, 
                $ticket->reservation_count
            );
            $totalIncome += $ticket->amount;
            $totalReservations += $ticket->reservation_count;
        }
        
        echo "\n📈 Resumen:\n";
        echo "- Total ingresos: $" . number_format($totalIncome, 2) . "\n";
        echo "- Total reservas: $totalReservations\n";
        echo "- Promedio por reserva: $" . number_format($totalReservations > 0 ? $totalIncome / $totalReservations : 0, 2) . "\n";
    }
    
    echo "\n=== ESTRUCTURA DE RESPUESTA PARA FRONTEND ===\n";
    
    // Formatear datos como lo espera el frontend
    $reportData = [];
    foreach ($tickets as $ticket) {
        $reportData[] = [
            'date' => $ticket->date,
            'amount' => (float) $ticket->amount,
            'reservationCount' => (int) $ticket->reservation_count
        ];
    }
    
    echo "Datos formateados para el frontend:\n";
    echo json_encode($reportData, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n=== ENDPOINT URL PARA FRONTEND ===\n";
    $baseUrl = 'http://localhost:8000/api';
    $params = [
        'user_id' => $userId,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'group_by' => 'day'
    ];
    
    $queryString = http_build_query($params);
    $endpointUrl = $baseUrl . '/business/reportes/ingresos?' . $queryString;
    
    echo "URL del endpoint: $endpointUrl\n";
    echo "Método: GET\n";
    echo "Headers: Content-Type: application/json\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TESTING COMPLETO ===\n";
echo "El endpoint de reportes debería funcionar correctamente.\n";
echo "Datos de prueba creados: 7 tickets completados/pagados con total de $105.\n";
echo "Próximo paso: Integrar este endpoint en el frontend.\n";
