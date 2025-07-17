<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePenalizacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Implementar lógica de autorización según roles
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ticket_id' => 'required|integer|exists:tickets,id',
            'usuario_id' => 'required|integer|exists:usuario_reserva,id',
            'tipo_penalizacion' => 'required|in:tiempo_excedido,dano_propiedad,mal_estacionamiento',
            'descripcion' => 'required|string|max:500',
            'monto' => 'sometimes|numeric|min:0|max:999999.99',
            'estado' => 'sometimes|in:pendiente,pagada,cancelada',
            'fecha' => 'sometimes|date',
            'motivo' => 'sometimes|string|max:255',
            'razon_mal_estacionamiento' => 'sometimes|in:doble_fila,espacio_discapacitados,bloqueo_salida,fuera_de_lineas,zona_prohibida'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'ticket_id.required' => 'El ticket es requerido',
            'ticket_id.exists' => 'El ticket especificado no existe',
            'usuario_id.required' => 'El usuario es requerido',
            'usuario_id.exists' => 'El usuario especificado no existe',
            'tipo_penalizacion.required' => 'El tipo de penalización es requerido',
            'tipo_penalizacion.in' => 'El tipo de penalización debe ser: tiempo_excedido, dano_propiedad o mal_estacionamiento',
            'descripcion.required' => 'La descripción es requerida',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres',
            'monto.numeric' => 'El monto debe ser un número',
            'monto.min' => 'El monto no puede ser negativo',
            'monto.max' => 'El monto no puede exceder $999,999.99',
            'estado.in' => 'El estado debe ser: pendiente, pagada o cancelada',
            'fecha.date' => 'La fecha debe ser una fecha válida',
            'motivo.max' => 'El motivo no puede exceder 255 caracteres',
            'razon_mal_estacionamiento.in' => 'La razón debe ser: doble_fila, espacio_discapacitados, bloqueo_salida, fuera_de_lineas o zona_prohibida'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'ticket_id' => 'ticket',
            'usuario_id' => 'usuario',
            'tipo_penalizacion' => 'tipo de penalización',
            'descripcion' => 'descripción',
            'monto' => 'monto',
            'estado' => 'estado',
            'fecha' => 'fecha',
            'motivo' => 'motivo',
            'razon_mal_estacionamiento' => 'razón de mal estacionamiento'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir tipo_penalizacion a formato correcto si viene diferente
        if ($this->has('tipo')) {
            $this->merge([
                'tipo_penalizacion' => $this->input('tipo')
            ]);
        }

        // Asegurar que el monto sea float
        if ($this->has('monto')) {
            $this->merge([
                'monto' => (float) $this->input('monto')
            ]);
        }
    }
}
