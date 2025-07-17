<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVehiculoRequest extends FormRequest
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
            'placa' => 'required|string|max:10|unique:vehiculos,placa|regex:/^[A-Z]{3}-\d{3}$/',
            'marca' => 'required|string|max:50',
            'modelo' => 'required|string|max:50',
            'año' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string|max:30',
            'tipo' => 'required|in:sedan,suv,hatchback,coupe,convertible,wagon,pickup,van,motocicleta',
            'usuario_id' => 'required|integer|exists:usuarios_reservas,id',
            'observaciones' => 'sometimes|string|max:500'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'placa.required' => 'La placa es requerida',
            'placa.unique' => 'Ya existe un vehículo con esta placa',
            'placa.regex' => 'La placa debe tener el formato ABC-123',
            'marca.required' => 'La marca es requerida',
            'marca.max' => 'La marca no puede exceder 50 caracteres',
            'modelo.required' => 'El modelo es requerido',
            'modelo.max' => 'El modelo no puede exceder 50 caracteres',
            'año.required' => 'El año es requerido',
            'año.integer' => 'El año debe ser un número entero',
            'año.min' => 'El año no puede ser menor a 1900',
            'año.max' => 'El año no puede ser mayor a ' . (date('Y') + 1),
            'color.required' => 'El color es requerido',
            'color.max' => 'El color no puede exceder 30 caracteres',
            'tipo.required' => 'El tipo de vehículo es requerido',
            'tipo.in' => 'El tipo de vehículo debe ser: sedan, suv, hatchback, coupe, convertible, wagon, pickup, van o motocicleta',
            'usuario_id.required' => 'El usuario es requerido',
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
