<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

echo "=== DIAGNÓSTICO DE TICKETS ===\n";

try {
    // Verificar si hay tickets en la BD
    $ticketsCount = App\Models\Ticket::count();
    echo "📊 Tickets en BD: $ticketsCount\n";
    
    if ($ticketsCount > 0) {
        echo "\n🎫 Primeros 5 tickets:\n";
        $tickets = App\Models\Ticket::with(['usuario', 'vehiculo', 'plaza'])
                                  ->take(5)
                                  ->get();
        
        foreach ($tickets as $ticket) {
            echo "- ID: {$ticket->id}\n";
            echo "  Estado: {$ticket->estado}\n";
            echo "  Usuario: " . ($ticket->usuario ? $ticket->usuario->nombre : 'N/A') . "\n";
            echo "  Vehículo: " . ($ticket->vehiculo ? $ticket->vehiculo->placa : 'N/A') . "\n";
            echo "  Plaza: " . ($ticket->plaza ? $ticket->plaza->numero : 'N/A') . "\n";
            echo "  Creado: {$ticket->created_at}\n";
            echo "  ---\n";
        }
    } else {
        echo "\n❌ No hay tickets en la base de datos\n";
        echo "💡 Necesitas crear tickets de prueba\n";
    }
    
    // Verificar estructura de tabla
    echo "\n🔍 Estructura de tickets:\n";
    $columns = DB::select("DESCRIBE tickets");
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
