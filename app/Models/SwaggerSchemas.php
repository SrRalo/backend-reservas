<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Penalizacion",
 *     type="object",
 *     title="Penalización",
 *     description="Modelo de penalización",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="ID único de la penalización"),
 *     @OA\Property(property="ticket_id", type="integer", example=1, description="ID del ticket asociado"),
 *     @OA\Property(property="usuario_id", type="integer", example=1, description="ID del usuario penalizado"),
 *     @OA\Property(property="tipo_penalizacion", type="string", enum={"tiempo_excedido", "dano_propiedad", "mal_estacionamiento"}, example="tiempo_excedido", description="Tipo de penalización"),
 *     @OA\Property(property="descripcion", type="string", example="Excedió el tiempo límite por 30 minutos", description="Descripción detallada"),
 *     @OA\Property(property="monto", type="number", format="decimal", example=25.50, description="Monto de la penalización"),
 *     @OA\Property(property="estado", type="string", enum={"pendiente", "pagada", "cancelada"}, example="pendiente", description="Estado actual"),
 *     @OA\Property(property="fecha_penalizacion", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de aplicación"),
 *     @OA\Property(property="fecha_pago", type="string", format="date-time", nullable=true, example="2024-01-20T14:15:00Z", description="Fecha de pago"),
 *     @OA\Property(property="razon_mal_estacionamiento", type="string", enum={"doble_fila", "espacio_discapacitados", "bloqueo_salida", "fuera_de_lineas", "zona_prohibida"}, nullable=true, example="doble_fila", description="Razón específica para mal estacionamiento"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de creación"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de actualización"),
 *     @OA\Property(property="ticket", ref="#/components/schemas/Ticket", description="Ticket asociado"),
 *     @OA\Property(property="usuario", ref="#/components/schemas/Usuario", description="Usuario penalizado"),
 *     @OA\Property(property="estacionamiento", ref="#/components/schemas/Estacionamiento", description="Estacionamiento donde ocurrió")
 * )
 * 
 * @OA\Schema(
 *     schema="Vehiculo",
 *     type="object",
 *     title="Vehículo",
 *     description="Modelo de vehículo",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="ID único del vehículo"),
 *     @OA\Property(property="placa", type="string", example="ABC-123", description="Placa del vehículo"),
 *     @OA\Property(property="marca", type="string", example="Toyota", description="Marca del vehículo"),
 *     @OA\Property(property="modelo", type="string", example="Corolla", description="Modelo del vehículo"),
 *     @OA\Property(property="año", type="integer", example=2020, description="Año del vehículo"),
 *     @OA\Property(property="color", type="string", example="Blanco", description="Color del vehículo"),
 *     @OA\Property(property="tipo", type="string", enum={"sedan", "suv", "hatchback", "coupe", "convertible", "wagon", "pickup", "van", "motocicleta"}, example="sedan", description="Tipo de vehículo"),
 *     @OA\Property(property="usuario_id", type="integer", example=1, description="ID del propietario"),
 *     @OA\Property(property="observaciones", type="string", nullable=true, example="Vehículo en excelente estado", description="Observaciones adicionales"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de creación"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de actualización"),
 *     @OA\Property(property="usuario", ref="#/components/schemas/Usuario", description="Propietario del vehículo"),
 *     @OA\Property(property="reservas", type="array", @OA\Items(ref="#/components/schemas/Reserva"), description="Reservas asociadas"),
 *     @OA\Property(property="tickets", type="array", @OA\Items(ref="#/components/schemas/Ticket"), description="Tickets asociados")
 * )
 * 
 * @OA\Schema(
 *     schema="Usuario",
 *     type="object",
 *     title="Usuario",
 *     description="Modelo de usuario",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="ID único del usuario"),
 *     @OA\Property(property="nombre", type="string", example="Juan Pérez", description="Nombre completo"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com", description="Correo electrónico"),
 *     @OA\Property(property="telefono", type="string", example="+1234567890", description="Número de teléfono"),
 *     @OA\Property(property="role", type="string", enum={"admin", "registrador", "reservador"}, example="reservador", description="Rol del usuario"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de creación"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de actualización")
 * )
 * 
 * @OA\Schema(
 *     schema="Ticket",
 *     type="object",
 *     title="Ticket",
 *     description="Modelo de ticket de estacionamiento",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="ID único del ticket"),
 *     @OA\Property(property="usuario_id", type="integer", example=1, description="ID del usuario"),
 *     @OA\Property(property="vehiculo_id", type="integer", example=1, description="ID del vehículo"),
 *     @OA\Property(property="estacionamiento_id", type="integer", example=1, description="ID del estacionamiento"),
 *     @OA\Property(property="fecha_entrada", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de entrada"),
 *     @OA\Property(property="fecha_salida", type="string", format="date-time", nullable=true, example="2024-01-15T12:30:00Z", description="Fecha de salida"),
 *     @OA\Property(property="estado", type="string", enum={"activo", "finalizado", "cancelado"}, example="activo", description="Estado del ticket"),
 *     @OA\Property(property="monto_total", type="number", format="decimal", example=15.50, description="Monto total"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de creación"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de actualización")
 * )
 * 
 * @OA\Schema(
 *     schema="Estacionamiento",
 *     type="object",
 *     title="Estacionamiento",
 *     description="Modelo de estacionamiento",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="ID único del estacionamiento"),
 *     @OA\Property(property="nombre", type="string", example="Estacionamiento Centro", description="Nombre del estacionamiento"),
 *     @OA\Property(property="direccion", type="string", example="Calle 123, Ciudad", description="Dirección física"),
 *     @OA\Property(property="tarifa_por_hora", type="number", format="decimal", example=5.00, description="Tarifa por hora"),
 *     @OA\Property(property="capacidad_total", type="integer", example=100, description="Capacidad total de vehículos"),
 *     @OA\Property(property="espacios_disponibles", type="integer", example=25, description="Espacios disponibles"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de creación"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de actualización")
 * )
 * 
 * @OA\Schema(
 *     schema="Reserva",
 *     type="object",
 *     title="Reserva",
 *     description="Modelo de reserva",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="ID único de la reserva"),
 *     @OA\Property(property="usuario_id", type="integer", example=1, description="ID del usuario"),
 *     @OA\Property(property="vehiculo_id", type="integer", example=1, description="ID del vehículo"),
 *     @OA\Property(property="estacionamiento_id", type="integer", example=1, description="ID del estacionamiento"),
 *     @OA\Property(property="fecha_inicio", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de inicio"),
 *     @OA\Property(property="fecha_fin", type="string", format="date-time", example="2024-01-15T12:30:00Z", description="Fecha de fin"),
 *     @OA\Property(property="estado", type="string", enum={"pendiente", "activa", "finalizada", "cancelada"}, example="activa", description="Estado de la reserva"),
 *     @OA\Property(property="monto_total", type="number", format="decimal", example=10.00, description="Monto total"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de creación"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Fecha de actualización")
 * )
 * 
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     title="Metadatos de Paginación",
 *     description="Información de paginación",
 *     @OA\Property(property="current_page", type="integer", example=1, description="Página actual"),
 *     @OA\Property(property="last_page", type="integer", example=5, description="Última página"),
 *     @OA\Property(property="per_page", type="integer", example=10, description="Elementos por página"),
 *     @OA\Property(property="total", type="integer", example=47, description="Total de elementos"),
 *     @OA\Property(property="from", type="integer", example=1, description="Elemento inicial"),
 *     @OA\Property(property="to", type="integer", example=10, description="Elemento final"),
 *     @OA\Property(property="has_more_pages", type="boolean", example=true, description="Indica si hay más páginas")
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Respuesta de Error",
 *     description="Formato estándar de respuesta de error",
 *     @OA\Property(property="success", type="boolean", example=false, description="Indica si la operación fue exitosa"),
 *     @OA\Property(property="message", type="string", example="Error en la operación", description="Mensaje de error"),
 *     @OA\Property(property="error", type="string", nullable=true, example="Detalles técnicos del error", description="Información técnica del error (solo en desarrollo)")
 * )
 * 
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     title="Respuesta de Éxito",
 *     description="Formato estándar de respuesta exitosa",
 *     @OA\Property(property="success", type="boolean", example=true, description="Indica si la operación fue exitosa"),
 *     @OA\Property(property="message", type="string", example="Operación completada exitosamente", description="Mensaje de éxito"),
 *     @OA\Property(property="data", type="object", description="Datos de respuesta")
 * )
 */
class SwaggerSchemas extends Model
{
    // Esta clase solo sirve para contener los esquemas de Swagger
    // No se usa en el código real
}
