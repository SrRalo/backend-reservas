<?php

namespace App\Http\Controllers;

use App\Services\PenalizacionService;
use App\Http\Requests\CreatePenalizacionRequest;
use App\Http\Requests\UpdatePenalizacionRequest;
use App\Http\Requests\ApplyPenaltyRequest;
use App\Http\Requests\PaginationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PenalizacionController extends Controller
{
    private PenalizacionService $penalizacionService;

    public function __construct(PenalizacionService $penalizacionService)
    {
        $this->penalizacionService = $penalizacionService;
        
        // Middleware de autenticación para todas las rutas
        $this->middleware('auth:sanctum');
        
        // Middleware de autorización específico por método
        $this->middleware('permission:view-all-penalizaciones')->only(['index', 'getStatistics']);
        $this->middleware('permission:create-penalizaciones')->only(['store', 'applyPenalty']);
        $this->middleware('permission:update-penalizaciones')->only(['update', 'markAsPaid']);
        $this->middleware('permission:delete-penalizaciones')->only(['destroy']);
        
        // Middleware de roles para métodos críticos
        $this->middleware('role:admin,registrador')->only(['applyPenalty']);
        $this->middleware('role:admin')->only(['destroy']);
    }

    /**
     * Display a listing of penalizaciones with advanced pagination and filters
     * 
     * @OA\Get(
     *     path="/api/penalizaciones",
     *     summary="Obtener lista de penalizaciones",
     *     description="Obtiene una lista paginada de penalizaciones con filtros avanzados",
     *     tags={"Penalizaciones"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=10)
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pendiente", "pagada", "cancelada"})
     *     ),
     *     @OA\Parameter(
     *         name="tipo_penalizacion",
     *         in="query",
     *         description="Filtrar por tipo",
     *         required=false,
     *         @OA\Schema(type="string", enum={"tiempo_excedido", "dano_propiedad", "mal_estacionamiento"})
     *     ),
     *     @OA\Parameter(
     *         name="usuario_id",
     *         in="query",
     *         description="Filtrar por usuario",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_desde",
     *         in="query",
     *         description="Filtrar desde fecha",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_hasta",
     *         in="query",
     *         description="Filtrar hasta fecha",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Búsqueda en descripción y usuario",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo de ordenamiento",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id", "fecha_penalizacion", "monto", "estado", "tipo_penalizacion", "created_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Orden de clasificación",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de penalizaciones obtenida exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Penalizaciones obtenidas exitosamente"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Penalizacion")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="filters_applied", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(PaginationRequest $request): JsonResponse
    {
        try {
            $paginationParams = $request->getPaginationParams();
            $filters = $request->getFilterParams();
            
            $penalizaciones = $this->penalizacionService->getPaginatedPenalizaciones(
                $paginationParams['page'],
                $paginationParams['per_page'],
                $filters,
                $paginationParams['sort_by'],
                $paginationParams['sort_order']
            );
            
            return response()->json([
                'success' => true,
                'data' => $penalizaciones->items(),
                'pagination' => [
                    'current_page' => $penalizaciones->currentPage(),
                    'last_page' => $penalizaciones->lastPage(),
                    'per_page' => $penalizaciones->perPage(),
                    'total' => $penalizaciones->total(),
                    'from' => $penalizaciones->firstItem(),
                    'to' => $penalizaciones->lastItem(),
                    'has_more_pages' => $penalizaciones->hasMorePages()
                ],
                'filters_applied' => $filters,
                'message' => 'Penalizaciones obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo penalizaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las penalizaciones',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created penalization with enhanced validation and business logic
     * 
     * @OA\Post(
     *     path="/api/penalizaciones",
     *     summary="Crear nueva penalización",
     *     description="Crea una nueva penalización con validación avanzada",
     *     tags={"Penalizaciones"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la penalización",
     *         @OA\JsonContent(
     *             required={"ticket_id", "tipo_penalizacion", "descripcion", "monto"},
     *             @OA\Property(property="ticket_id", type="integer", example=1, description="ID del ticket"),
     *             @OA\Property(property="tipo_penalizacion", type="string", enum={"tiempo_excedido", "dano_propiedad", "mal_estacionamiento"}, example="tiempo_excedido", description="Tipo de penalización"),
     *             @OA\Property(property="descripcion", type="string", example="Excedió el tiempo límite por 30 minutos", description="Descripción de la penalización"),
     *             @OA\Property(property="monto", type="number", format="decimal", example=25.50, description="Monto de la penalización"),
     *             @OA\Property(property="estado", type="string", enum={"pendiente", "pagada", "cancelada"}, example="pendiente", description="Estado inicial de la penalización"),
     *             @OA\Property(property="razon_mal_estacionamiento", type="string", enum={"doble_fila", "espacio_discapacitados", "bloqueo_salida", "fuera_de_lineas", "zona_prohibida"}, example="doble_fila", description="Razón específica si es mal estacionamiento")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Penalización creada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Penalización creada exitosamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Penalizacion")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Datos de validación incorrectos"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(CreatePenalizacionRequest $request): JsonResponse
    {
        try {
            // Log de la operación para auditoría
            Log::info('Creando nueva penalización', [
                'user_id' => auth()->id(),
                'data' => $request->validated()
            ]);

            $penalizacion = $this->penalizacionService->createPenalizacion($request->validated());
            
            // Log exitoso
            Log::info('Penalización creada exitosamente', [
                'penalizacion_id' => $penalizacion->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $penalizacion->load(['ticket', 'usuario', 'estacionamiento']),
                'message' => 'Penalización creada exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Error de validación al crear penalización', [
                'user_id' => auth()->id(),
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando penalización', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al crear la penalización',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $penalizacion = $this->penalizacionService->getPenalizacionById($id);
            
            if (!$penalizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penalización no encontrada'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $penalizacion,
                'message' => 'Penalización obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo penalización: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePenalizacionRequest $request, string $id): JsonResponse
    {
        try {
            $penalizacion = $this->penalizacionService->updatePenalizacion($id, $request->validated());
            
            if (!$penalizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penalización no encontrada'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $penalizacion,
                'message' => 'Penalización actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error actualizando penalización: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->penalizacionService->deletePenalizacion($id);
            
            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penalización no encontrada'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Penalización eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando penalización: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Apply penalty to a ticket
     */
    public function applyPenalty(ApplyPenaltyRequest $request): JsonResponse
    {
        try {
            $penalty = $this->penalizacionService->applyPenalty($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $penalty,
                'message' => 'Penalización aplicada exitosamente'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error aplicando penalización: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar la penalización'
            ], 500);
        }
    }

    /**
     * Mark penalty as paid
     */
    public function markAsPaid(string $id): JsonResponse
    {
        try {
            $penalizacion = $this->penalizacionService->markAsPaid($id);
            
            if (!$penalizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penalización no encontrada'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $penalizacion,
                'message' => 'Penalización marcada como pagada'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marcando penalización como pagada: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get penalties statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->penalizacionService->getStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get user penalties
     */
    public function getUserPenalties(Request $request, string $userId): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $filters = array_merge($request->only(['estado', 'tipo_penalizacion']), ['usuario_id' => $userId]);
            
            $penalizaciones = $this->penalizacionService->getPaginatedPenalizaciones($page, $perPage, $filters);
            
            return response()->json([
                'success' => true,
                'data' => $penalizaciones,
                'message' => 'Penalizaciones del usuario obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo penalizaciones del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
