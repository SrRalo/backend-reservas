<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePenalizacionRequest extends FormRequest
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
            'descripcion' => 'sometimes|string|max:500',
            'monto' => 'sometimes|numeric|min:0|max:999999.99',
            'estado' => 'sometimes|in:pendiente,pagada,cancelada',
            'fecha' => 'sometimes|date',
            'motivo' => 'sometimes|string|max:255',
            'fecha_pago' => 'sometimes|date',
            'fecha_cancelacion' => 'sometimes|date'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres',
            'monto.numeric' => 'El monto debe ser un número',
            'monto.min' => 'El monto no puede ser negativo',
            'monto.max' => 'El monto no puede exceder $999,999.99',
            'estado.in' => 'El estado debe ser: pendiente, pagada o cancelada',
            'fecha.date' => 'La fecha debe ser una fecha válida',
            'motivo.max' => 'El motivo no puede exceder 255 caracteres',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida',
            'fecha_cancelacion.date' => 'La fecha de cancelación debe ser una fecha válida'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'descripcion' => 'descripción',
            'monto' => 'monto',
            'estado' => 'estado',
            'fecha' => 'fecha',
            'motivo' => 'motivo',
            'fecha_pago' => 'fecha de pago',
            'fecha_cancelacion' => 'fecha de cancelación'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Asegurar que el monto sea float
        if ($this->has('monto')) {
            $this->merge([
                'monto' => (float) $this->input('monto')
            ]);
        }
    }
}
