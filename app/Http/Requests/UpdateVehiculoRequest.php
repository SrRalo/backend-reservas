<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehiculoRequest extends FormRequest
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
        $vehiculoId = $this->route('vehiculo');
        
        return [
            'placa' => [
                'sometimes',
                'string',
                'max:10',
                'regex:/^[A-Z]{3}-\d{3}$/',
                Rule::unique('vehiculos', 'placa')->ignore($vehiculoId)
            ],
            'marca' => 'sometimes|string|max:50',
            'modelo' => 'sometimes|string|max:50',
            'año' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'sometimes|string|max:30',
            'tipo' => 'sometimes|in:sedan,suv,hatchback,coupe,convertible,wagon,pickup,van,motocicleta',
            'usuario_id' => 'sometimes|integer|exists:usuarios_reservas,id',
            'observaciones' => 'sometimes|string|max:500'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'placa.unique' => 'Ya existe un vehículo con esta placa',
            'placa.regex' => 'La placa debe tener el formato ABC-123',
            'placa.max' => 'La placa no puede exceder 10 caracteres',
            'marca.max' => 'La marca no puede exceder 50 caracteres',
            'modelo.max' => 'El modelo no puede exceder 50 caracteres',
            'año.integer' => 'El año debe ser un número entero',
            'año.min' => 'El año no puede ser menor a 1900',
            'año.max' => 'El año no puede ser mayor a ' . (date('Y') + 1),
            'color.max' => 'El color no puede exceder 30 caracteres',
            'tipo.in' => 'El tipo de vehículo debe ser: sedan, suv, hatchback, coupe, convertible, wagon, pickup, van o motocicleta',
            'usuario_id.exists' => 'El usuario especificado no existe',
            'observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'placa' => 'placa',
            'marca' => 'marca',
            'modelo' => 'modelo',
            'año' => 'año',
            'color' => 'color',
            'tipo' => 'tipo de vehículo',
            'usuario_id' => 'usuario',
            'observaciones' => 'observaciones'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir placa a mayúsculas
        if ($this->has('placa')) {
            $this->merge([
                'placa' => strtoupper($this->input('placa'))
            ]);
        }

        // Convertir año a entero
        if ($this->has('año')) {
            $this->merge([
                'año' => (int) $this->input('año')
            ]);
        }

        // Limpiar espacios en blanco
        $fields = ['marca', 'modelo', 'color', 'observaciones'];
        foreach ($fields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => trim($this->input($field))
                ]);
            }
        }
    }
}
