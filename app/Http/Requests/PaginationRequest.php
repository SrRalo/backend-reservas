<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort_by' => 'sometimes|string|max:50',
            'sort_order' => 'sometimes|in:asc,desc',
            'search' => 'sometimes|string|max:255',
            
            // Filtros específicos para penalizaciones
            'estado' => 'sometimes|in:pendiente,pagada,cancelada',
            'tipo_penalizacion' => 'sometimes|in:tiempo_excedido,dano_propiedad,mal_estacionamiento',
            'usuario_id' => 'sometimes|integer|exists:usuarios_reservas,id',
            'fecha_desde' => 'sometimes|date',
            'fecha_hasta' => 'sometimes|date|after_or_equal:fecha_desde',
            'monto_min' => 'sometimes|numeric|min:0',
            'monto_max' => 'sometimes|numeric|min:0|gte:monto_min',
            
            // Filtros específicos para vehículos
            'marca' => 'sometimes|string|max:50',
            'modelo' => 'sometimes|string|max:50',
            'tipo' => 'sometimes|in:sedan,suv,hatchback,coupe,convertible,wagon,pickup,van,motocicleta',
            'color' => 'sometimes|string|max:30',
            'año_desde' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'año_hasta' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1) . '|gte:año_desde',
            'disponible' => 'sometimes|in:si,no',
            
            // Filtros específicos para tickets
            'estacionamiento_id' => 'sometimes|integer|exists:estacionamientos_admin,id',
            'vehiculo_id' => 'sometimes|integer|exists:vehiculos,id',
            'estado_ticket' => 'sometimes|in:activo,finalizado,cancelado'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'La página debe ser un número entero',
            'page.min' => 'La página debe ser mayor a 0',
            'per_page.integer' => 'Los elementos por página deben ser un número entero',
            'per_page.min' => 'Debe mostrar al menos 1 elemento por página',
            'per_page.max' => 'No se pueden mostrar más de 100 elementos por página',
            'sort_order.in' => 'El orden debe ser "asc" o "desc"',
            'search.max' => 'La búsqueda no puede exceder 255 caracteres',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde',
            'monto_max.gte' => 'El monto máximo debe ser mayor o igual al monto mínimo',
            'año_hasta.gte' => 'El año hasta debe ser mayor o igual al año desde'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Establecer valores por defecto
        $this->mergeIfMissing([
            'page' => 1,
            'per_page' => 10,
            'sort_order' => 'desc'
        ]);

        // Limpiar espacios en blanco en campos de texto
        $textFields = ['search', 'marca', 'modelo', 'color'];
        foreach ($textFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => trim($this->input($field))
                ]);
            }
        }
    }

    /**
     * Get processed pagination parameters
     */
    public function getPaginationParams(): array
    {
        return [
            'page' => $this->input('page', 1),
            'per_page' => $this->input('per_page', 10),
            'sort_by' => $this->input('sort_by'),
            'sort_order' => $this->input('sort_order', 'desc'),
            'search' => $this->input('search')
        ];
    }

    /**
     * Get processed filter parameters
     */
    public function getFilterParams(): array
    {
        $filters = [];
        
        // Filtros comunes
        $commonFilters = ['usuario_id', 'fecha_desde', 'fecha_hasta'];
        foreach ($commonFilters as $filter) {
            if ($this->has($filter)) {
                $filters[$filter] = $this->input($filter);
            }
        }

        // Filtros específicos para penalizaciones
        $penalizacionFilters = ['estado', 'tipo_penalizacion', 'monto_min', 'monto_max'];
        foreach ($penalizacionFilters as $filter) {
            if ($this->has($filter)) {
                $filters[$filter] = $this->input($filter);
            }
        }

        // Filtros específicos para vehículos
        $vehiculoFilters = ['marca', 'modelo', 'tipo', 'color', 'año_desde', 'año_hasta', 'disponible'];
        foreach ($vehiculoFilters as $filter) {
            if ($this->has($filter)) {
                $filters[$filter] = $this->input($filter);
            }
        }

        // Filtros específicos para tickets
        $ticketFilters = ['estacionamiento_id', 'vehiculo_id', 'estado_ticket'];
        foreach ($ticketFilters as $filter) {
            if ($this->has($filter)) {
                $filters[$filter] = $this->input($filter);
            }
        }

        return $filters;
    }
}
