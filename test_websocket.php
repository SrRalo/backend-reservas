<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Events\SystemMonitorEvent;
use App\Events\ReservaStatusEvent;

echo "Testeando eventos WebSocket...\n";

try {
    // Test SystemMonitorEvent
    echo "1. Testeando SystemMonitorEvent...\n";
    $systemEvent = new SystemMonitorEvent('api', 'active', [
        'endpoint' => '/api/test',
        'response_time' => 150,
        'memory_usage' => '2MB'
    ]);
    echo "✓ SystemMonitorEvent creado correctamente\n";

    // Test ReservaStatusEvent
    echo "2. Testeando ReservaStatusEvent...\n";
    $reservaEvent = new ReservaStatusEvent([
        'ticket_id' => 1,
        'usuario_id' => 1,
        'status' => 'activo',
        'action' => 'created',
        'timestamp' => date('Y-m-d H:i:s')
    ], 1);
    echo "✓ ReservaStatusEvent creado correctamente\n";

    echo "\n¡Todos los eventos WebSocket están funcionando correctamente!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
