<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\ReservaService;
use App\Services\TarifaCalculatorService;
use App\Services\EstacionamientoService;
use App\Services\PenalizacionService;
use App\Services\PrecioService;
use App\Services\PagoService;
use App\Repositories\Interfaces\PenalizacionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReservaBusinessController extends Controller
{
    private ReservaService $reservaService;
    private TarifaCalculatorService $tarifaCalculator;
    private EstacionamientoService $estacionamientoService;
    private PenalizacionService $penalizacionService;
    private PagoService $pagoService;
    private PrecioService $precioService;
    private PenalizacionRepositoryInterface $penalizacionRepository;

    public function __construct(
        ReservaService $reservaService,
        TarifaCalculatorService $tarifaCalculator,
        EstacionamientoService $estacionamientoService,
        PenalizacionService $penalizacionService,
        PagoService $pagoService,
        PrecioService $precioService,
        PenalizacionRepositoryInterface $penalizacionRepository
    ) {
        $this->reservaService = $reservaService;
        $this->tarifaCalculator = $tarifaCalculator;
        $this->estacionamientoService = $estacionamientoService;
        $this->penalizacionService = $penalizacionService;
        $this->pagoService = $pagoService;
        $this->precioService = $precioService;
        $this->penalizacionRepository = $penalizacionRepository;
    }

    /**
     * Crear una nueva reserva con lógica de negocio completa
     */
    public function crearReserva(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'usuario_id' => 'required|integer',
                'vehiculo_id' => 'required|string|exists:vehiculos,placa',
                'estacionamiento_id' => 'required|integer',
                'tipo_reserva' => 'required|in:por_horas,mensual',
                'fecha_entrada' => 'sometimes|date', // ✅ Removido after_or_equal:now para permitir fechas futuras
                'fecha_salida_estimada' => 'sometimes|date|after:fecha_entrada', // ✅ Nueva validación
                'horas_estimadas' => 'sometimes|numeric|min:0.5|max:720', // máximo 30 días
                'dias_estimados' => 'sometimes|integer|min:1|max:365'
            ]);

            $resultado = $this->reservaService->crearReserva($validatedData);

            if (!$resultado['success']) {
                return response()->json($resultado, 400);
            }

            return response()->json($resultado, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en crearReserva: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Finalizar reserva con cálculo automático y procesamiento de pago
     */
    public function finalizarReserva(Request $request, int $ticketId): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'metodo_pago' => 'required|string|in:tarjeta,efectivo,transferencia',
                'datos_pago' => 'required_if:metodo_pago,tarjeta|array',
                'datos_pago.numero_tarjeta' => 'required_if:metodo_pago,tarjeta|string',
                'datos_pago.cvv' => 'required_if:metodo_pago,tarjeta|string|size:3',
                'datos_pago.mes_expiracion' => 'required_if:metodo_pago,tarjeta|string|size:2',
                'datos_pago.anio_expiracion' => 'required_if:metodo_pago,tarjeta|string|size:4',
                'monto_manual' => 'sometimes|numeric|min:0'
            ]);

            // 1. Primero finalizar la reserva (calcular tiempo y costo)
            $montoFinal = $validatedData['monto_manual'] ?? null;
            $resultadoReserva = $this->reservaService->finalizarReserva($ticketId, $montoFinal);

            if (!$resultadoReserva['success']) {
                return response()->json($resultadoReserva, 400);
            }

            // 2. Procesar el pago
            $resultadoPago = $this->pagoService->procesarPago($ticketId, $validatedData);

            if (!$resultadoPago['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva finalizada pero error en el pago',
                    'data' => [
                        'reserva' => $resultadoReserva['data'],
                        'pago_error' => $resultadoPago
                    ]
                ], 402); // Payment Required
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'ticket' => $resultadoReserva['data']['ticket'],
                    'pago' => $resultadoPago['data']['pago'],
                    'tiempo_total' => $resultadoReserva['data']['tiempo_total'],
                    'costo_total' => $resultadoPago['data']['monto_total']
                ],
                'message' => 'Reserva finalizada y pago procesado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error finalizando reserva con pago: ' . $e->getMessage(), [
                'ticket_id' => $ticketId,
                'request_data' => $request->all(),
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Cancelar reserva
     */
    public function cancelarReserva(Request $request, int $ticketId): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'motivo' => 'sometimes|string|max:500'
            ]);

            $motivo = $validatedData['motivo'] ?? '';
            $resultado = $this->reservaService->cancelarReserva($ticketId, $motivo);

            if (!$resultado['success']) {
                return response()->json($resultado, 400);
            }

            return response()->json($resultado);

        } catch (\Exception $e) {
            Log::error('Error cancelando reserva: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Calcular precio estimado para una reserva
     */
    public function calcularPrecioEstimado(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'estacionamiento_id' => 'required|integer',
                'tipo_reserva' => 'required|in:por_horas,mensual',
                'horas_estimadas' => 'sometimes|numeric|min:0.5|max:720',
                'dias_estimados' => 'sometimes|integer|min:1|max:365'
            ]);

            $result = $this->precioService->calcularPrecioEstimado(
                $validatedData['estacionamiento_id'],
                $validatedData['tipo_reserva'],
                $validatedData['horas_estimadas'] ?? null,
                $validatedData['dias_estimados'] ?? null
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => 'Precio estimado calculado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error calculando precio estimado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Buscar estacionamientos disponibles
     */
    public function buscarEstacionamientosDisponibles(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'precio_max' => 'sometimes|numeric|min:0',
                'tipo_reserva' => 'sometimes|in:por_horas,mensual',
                'fecha' => 'sometimes|date'
            ]);

            // Por ahora, obtener todos los estacionamientos activos
            $estacionamientos = \App\Models\EstacionamientoAdmin::where('estado', 'activo');

            // Aplicar filtros
            if (isset($validatedData['precio_max'])) {
                if (isset($validatedData['tipo_reserva']) && $validatedData['tipo_reserva'] === 'mensual') {
                    $estacionamientos->where('precio_mensual', '<=', $validatedData['precio_max']);
                } else {
                    $estacionamientos->where('precio_por_hora', '<=', $validatedData['precio_max']);
                }
            }

            $resultados = $estacionamientos->get();

            // Transformar datos para coincidir con lo que espera el frontend/test
            $estacionamientosTransformados = $resultados->map(function($estacionamiento) {
                return [
                    'id' => $estacionamiento->id,
                    'nombre' => $estacionamiento->nombre,
                    'ubicacion' => $estacionamiento->direccion,
                    'plazas_totales' => $estacionamiento->espacios_totales,
                    'precio_hora' => $estacionamiento->precio_por_hora,
                    'precio_mes' => $estacionamiento->precio_mensual,
                    'estado' => $estacionamiento->estado
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'estacionamientos_disponibles' => $estacionamientosTransformados
                ],
                'total' => $resultados->count(),
                'message' => 'Estacionamientos disponibles obtenidos exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error buscando estacionamientos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Aplicar penalización
     */
    public function aplicarPenalizacion(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'ticket_id' => 'required|integer',
                'tipo' => 'required|in:tiempo_excedido,dano_propiedad,mal_estacionamiento',
                'descripcion' => 'sometimes|string|max:500',
                'monto' => 'sometimes|numeric|min:0',
                'razon_mal_estacionamiento' => 'sometimes|in:doble_fila,espacio_discapacitados,bloqueo_salida,fuera_de_lineas,zona_prohibida'
            ]);

            $resultado = null;

            switch ($validatedData['tipo']) {
                case 'tiempo_excedido':
                    $resultado = $this->penalizacionService->aplicarPenalizacionTiempo($validatedData['ticket_id']);
                    break;

                case 'dano_propiedad':
                    $monto = $validatedData['monto'] ?? 100.00;
                    $descripcion = $validatedData['descripcion'] ?? 'Daño a la propiedad';
                    $resultado = $this->penalizacionService->aplicarPenalizacionDano(
                        $validatedData['ticket_id'], 
                        $descripcion, 
                        $monto
                    );
                    break;

                case 'mal_estacionamiento':
                    $razon = $validatedData['razon_mal_estacionamiento'] ?? 'fuera_de_lineas';
                    $resultado = $this->penalizacionService->aplicarPenalizacionMalEstacionamiento(
                        $validatedData['ticket_id'], 
                        $razon
                    );
                    break;
            }

            if (!$resultado || !$resultado['success']) {
                return response()->json($resultado ?? ['success' => false, 'message' => 'Error aplicando penalización'], 400);
            }

            // Si la penalización se aplicó correctamente, guardarla en la base de datos
            if ($resultado['success'] && isset($resultado['penalizacion_aplicada']) && $resultado['penalizacion_aplicada']) {
                // Obtener el ticket para conseguir el usuario_reserva_id
                $ticket = \App\Models\Ticket::find($validatedData['ticket_id']);
                
                $penalizacionData = [
                    'ticket_id' => $validatedData['ticket_id'],
                    'usuario_reserva_id' => $ticket->usuario_id, // Asumiendo que usuario_id es el campo correcto
                    'tipo' => $validatedData['tipo'],
                    'motivo' => $validatedData['descripcion'] ?? $resultado['penalizacion']['descripcion'] ?? '',
                    'monto' => $validatedData['monto'] ?? $resultado['penalizacion']['monto'] ?? 0,
                    'estado' => 'activa',
                    'fecha' => now()
                ];

                $penalizacionCreada = $this->penalizacionRepository->create($penalizacionData);
                
                $resultado['data'] = [
                    'penalizacion' => $penalizacionCreada,
                    'ticket_actualizado' => [
                        'id' => $validatedData['ticket_id'],
                        'estado' => 'con_penalizacion'
                    ]
                ];
            }

            return response()->json($resultado);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error aplicando penalización: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener resumen completo de usuario
     */
    public function getResumenUsuario(int $usuarioId): JsonResponse
    {
        try {
            $resumenReservas = $this->reservaService->getResumenUsuario($usuarioId);
            $resumenPenalizaciones = $this->penalizacionService->getResumenPenalizacionesUsuario($usuarioId);

            if (!$resumenReservas['success'] || !$resumenPenalizaciones['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error obteniendo resumen del usuario'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reservas' => $resumenReservas['data'],
                    'penalizaciones' => $resumenPenalizaciones['data']
                ],
                'message' => 'Resumen del usuario obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo resumen de usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener reporte de ocupación de estacionamiento
     */
    public function getReporteOcupacion(int $estacionamientoId): JsonResponse
    {
        try {
            $reporte = $this->estacionamientoService->generarReporteOcupacion($estacionamientoId);

            if (isset($reporte['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $reporte['error']
                ], 404);
            }

            // Asegurar que el reporte tiene la estructura esperada por los tests
            $reporteCompleto = [
                'estacionamiento' => $reporte['estacionamiento'] ?? ['id' => $estacionamientoId],
                'ocupacion_actual' => $reporte['ocupacion_actual'] ?? 0,
                'reservas_activas' => $reporte['reservas_activas'] ?? 0,
                'ingresos_estimados' => $reporte['ingresos_estimados'] ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $reporteCompleto,
                'message' => 'Reporte de ocupación generado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando reporte de ocupación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener historial de pagos de un usuario
     */
    public function getHistorialPagos(Request $request, int $usuarioId): JsonResponse
    {
        try {
            $filtros = $request->validate([
                'fecha_desde' => 'sometimes|date',
                'fecha_hasta' => 'sometimes|date|after_or_equal:fecha_desde',
                'estado' => 'sometimes|string|in:pendiente,exitoso,fallido,reembolsado'
            ]);

            $historial = $this->pagoService->obtenerHistorialPagos($usuarioId, $filtros);

            if (!$historial['success']) {
                return response()->json($historial, 400);
            }

            return response()->json([
                'success' => true,
                'data' => $historial['data'],
                'message' => 'Historial de pagos obtenido exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros de filtro inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error obteniendo historial de pagos: ' . $e->getMessage(), [
                'usuario_id' => $usuarioId,
                'filtros' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Reembolsar un pago
     */
    public function reembolsarPago(Request $request, int $pagoId): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'motivo' => 'required|string|max:500'
            ]);

            $resultado = $this->pagoService->reembolsarPago($pagoId, $validatedData['motivo']);

            if (!$resultado['success']) {
                return response()->json($resultado, 400);
            }

            return response()->json([
                'success' => true,
                'data' => $resultado['data'],
                'message' => 'Reembolso procesado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error procesando reembolso: ' . $e->getMessage(), [
                'pago_id' => $pagoId,
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Procesar pago manual (para efectivo o transferencia)
     */
    public function procesarPagoManual(Request $request, int $ticketId): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'metodo_pago' => 'required|string|in:efectivo,transferencia',
                'monto_recibido' => 'required|numeric|min:0',
                'referencia' => 'sometimes|string|max:100',
                'notas' => 'sometimes|string|max:500'
            ]);

            $datosPago = [
                'metodo_pago' => $validatedData['metodo_pago'],
                'datos_pago' => [
                    'monto_recibido' => $validatedData['monto_recibido'],
                    'referencia' => $validatedData['referencia'] ?? null,
                    'notas' => $validatedData['notas'] ?? null,
                    'procesado_manualmente' => true
                ]
            ];

            $resultado = $this->pagoService->procesarPago($ticketId, $datosPago);

            if (!$resultado['success']) {
                return response()->json($resultado, 400);
            }

            return response()->json([
                'success' => true,
                'data' => $resultado['data'],
                'message' => 'Pago manual procesado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error procesando pago manual: ' . $e->getMessage(), [
                'ticket_id' => $ticketId,
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
