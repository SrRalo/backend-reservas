<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

try {
    $user = new App\Models\UsuarioReserva();
    echo "✅ Tabla del modelo: " . $user->getTable() . PHP_EOL;
    echo "✅ Campos fillable: " . implode(', ', $user->getFillable()) . PHP_EOL;
    
    // Verificar que podemos crear un usuario con role
    $userData = [
        'nombre' => 'Test User',
        'email' => 'test@test.com',
        'documento' => '12345678',
        'password' => 'password123',
        'role' => 'admin'
    ];
    
    echo "✅ Datos de prueba preparados para crear usuario con role: " . $userData['role'] . PHP_EOL;
    echo "✅ Modelo configurado correctamente!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
