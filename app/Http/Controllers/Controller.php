<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Park Pal - Reserva Fácil API",
 *     version="1.0.0",
 *     description="API para sistema de reservas de estacionamiento",
 *     @OA\Contact(
 *         email="admin@parkpal.com",
 *         name="Park Pal Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Usar el token JWT obtenido del login"
 * )
 * 
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints de autenticación y autorización"
 * )
 * 
 * @OA\Tag(
 *     name="Penalizaciones",
 *     description="Gestión de penalizaciones"
 * )
 * 
 * @OA\Tag(
 *     name="Vehículos",
 *     description="Gestión de vehículos"
 * )
 * 
 * @OA\Tag(
 *     name="Tickets",
 *     description="Gestión de tickets de estacionamiento"
 * )
 * 
 * @OA\Tag(
 *     name="Reservas",
 *     description="Gestión de reservas"
 * )
 * 
 * @OA\Tag(
 *     name="Estacionamientos",
 *     description="Gestión de estacionamientos"
 * )
 * 
 * @OA\Tag(
 *     name="Reportes",
 *     description="Reportes y estadísticas"
 * )
 */
abstract class Controller
{
    //
}
