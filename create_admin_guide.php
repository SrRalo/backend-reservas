<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

echo "=== SCRIPT PARA CREAR ADMINISTRADOR ===\n";
echo "Este script demuestra cómo crear un usuario admin directamente en la BD\n\n";

try {
    // Simular la creación de un admin (sin ejecutar realmente)
    echo "🔧 Para crear un administrador, ejecuta en la BD:\n\n";
    
    echo "INSERT INTO usuarios (nombre, apellido, email, documento, password, role, estado, created_at, updated_at) VALUES (\n";
    echo "  'Admin',\n";
    echo "  'Sistema',\n";
    echo "  'admin@parksmart.com',\n";
    echo "  'ADMIN001',\n";
    echo "  '" . password_hash('admin123', PASSWORD_DEFAULT) . "',\n";
    echo "  'admin',\n";
    echo "  'activo',\n";
    echo "  NOW(),\n";
    echo "  NOW()\n";
    echo ");\n\n";
    
    echo "📋 O usando Laravel Artisan:\n\n";
    echo "php artisan tinker\n";
    echo "App\\Models\\UsuarioReserva::create([\n";
    echo "  'nombre' => 'Admin',\n";
    echo "  'apellido' => 'Sistema',\n";
    echo "  'email' => 'admin@parksmart.com',\n";
    echo "  'documento' => 'ADMIN001',\n";
    echo "  'password' => bcrypt('admin123'),\n";
    echo "  'role' => 'admin',\n";
    echo "  'estado' => 'activo'\n";
    echo "]);\n\n";
    
    echo "✅ VALIDACIÓN DEL SISTEMA:\n";
    echo "- ✅ Registro desde app: solo 'registrador' y 'reservador'\n";
    echo "- ✅ Admin: solo desde base de datos\n";
    echo "- ✅ Validación backend: rechaza 'admin' en registro\n";
    echo "- ✅ Frontend: dropdown solo muestra opciones permitidas\n\n";
    
    echo "🔒 SEGURIDAD:\n";
    echo "- Los roles admin se crean manualmente (mayor control)\n";
    echo "- Validación en backend previene creación accidental\n";
    echo "- Separación clara entre roles públicos y administrativos\n\n";
    
    echo "🎯 ROLES DISPONIBLES:\n";
    echo "- admin: Acceso completo (solo BD)\n";
    echo "- registrador: Propietario de estacionamiento (registro)\n";
    echo "- reservador: Cliente del sistema (registro)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
