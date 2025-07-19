<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\VehiculoRequest;
use App\Models\Vehiculo;
use App\Services\VehiculoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Vehiculos",
 *     description="Operaciones relacionadas con la gestión de vehículos"
 * )
 */
class VehiculoController extends BaseApiController
{
    private VehiculoService $vehiculoService;

    public function __construct(VehiculoService $vehiculoService)
    {
        parent::__construct();
        $this->vehiculoService = $vehiculoService;
    }

    protected function applyRoleMiddleware(): void
    {
        $this->middleware('role:admin,registrador,reservador');
    }

    /**
     * @OA\Get(
     *     path="/api/vehiculos",
     *     summary="Obtener lista de vehículos",
     *     tags={"Vehiculos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Término de búsqueda",
     *         required=false,
     *         @OA\Schema(type="string", example="ABC123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de vehículos obtenida exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $filters = [];
        
        // Solo admin puede ver todos los vehículos
        if (Auth::user()->role !== 'admin') {
            $filters['usuario_id'] = Auth::id();
        }

        return $this->indexResponse(
            $this->vehiculoService,
            $filters,
            10,
            'Vehículos obtenidos exitosamente'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/vehiculos/{id}",
     *     summary="Obtener un vehículo específico",
     *     tags={"Vehiculos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del vehículo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehículo obtenido exitosamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehículo no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        return $this->showResponse(
            $this->vehiculoService,
            $id,
            'Vehículo obtenido exitosamente'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/vehiculos",
     *     summary="Crear un nuevo vehículo",
     *     tags={"Vehiculos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patente", "marca", "modelo", "tipo", "color"},
     *             @OA\Property(property="patente", type="string", example="ABC123"),
     *             @OA\Property(property="marca", type="string", example="Toyota"),
     *             @OA\Property(property="modelo", type="string", example="Corolla"),
     *             @OA\Property(property="tipo", type="string", enum={"auto", "motocicleta", "camioneta"}, example="auto"),
     *             @OA\Property(property="color", type="string", example="Blanco"),
     *             @OA\Property(property="year", type="integer", example=2022),
     *             @OA\Property(property="observaciones", type="string", example="Vehículo en excelente estado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehículo creado exitosamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Prohibido")
     *         )
     *     )
     * )
     */
    public function store(VehiculoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['usuario_id'] = Auth::id();
        $data['estado'] = 'activo';

        return $this->storeResponse(
            $this->vehiculoService,
            $data,
            'Vehículo creado exitosamente'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/vehiculos/{id}",
     *     summary="Actualizar un vehículo",
     *     tags={"Vehiculos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del vehículo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="patente", type="string", example="ABC123"),
     *             @OA\Property(property="marca", type="string", example="Toyota"),
     *             @OA\Property(property="modelo", type="string", example="Corolla"),
     *             @OA\Property(property="tipo", type="string", enum={"auto", "motocicleta", "camioneta"}, example="auto"),
     *             @OA\Property(property="color", type="string", example="Blanco"),
     *             @OA\Property(property="year", type="integer", example=2022),
     *             @OA\Property(property="observaciones", type="string", example="Vehículo en excelente estado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehículo actualizado exitosamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehículo no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Prohibido")
     *         )
     *     )
     * )
     */
    public function update(VehiculoRequest $request, int $id): JsonResponse
    {
        $vehiculo = Vehiculo::findOrFail($id);
        
        // Verificar que el usuario sea el propietario del vehículo o sea admin
        $user = Auth::user();
        if ($user->role !== 'admin' && $vehiculo->usuario_id !== $user->id) {
            return response()->json(['message' => 'No tienes permisos para actualizar este vehículo'], 403);
        }

        $data = $request->validated();
        
        return $this->updateResponse(
            $this->vehiculoService,
            $id,
            $data,
            'Vehículo actualizado exitosamente'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/vehiculos/{id}",
     *     summary="Eliminar un vehículo",
     *     tags={"Vehiculos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del vehículo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehículo eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehículo eliminado exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehículo no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Prohibido")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $vehiculo = Vehiculo::findOrFail($id);
        
        // Verificar que el usuario sea el propietario del vehículo o sea admin
        $user = Auth::user();
        if ($user->role !== 'admin' && $vehiculo->usuario_id !== $user->id) {
            return response()->json(['message' => 'No tienes permisos para eliminar este vehículo'], 403);
        }

        return $this->destroyResponse(
            $this->vehiculoService,
            $id,
            'Vehículo eliminado exitosamente'
        );
    }
}