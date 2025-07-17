<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehiculoRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'patente' => 'required|string|max:8|unique:vehiculos,patente',
            'marca' => 'required|string|max:50',
            'modelo' => 'required|string|max:50',
            'tipo' => 'required|in:auto,motocicleta,camioneta',
            'color' => 'required|string|max:30',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'observaciones' => 'nullable|string|max:500',
        ];

        // Si estamos actualizando, excluir el vehículo actual de la validación de patente única
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $vehiculoId = $this->route('vehiculo');
            $rules['patente'] = 'required|string|max:8|unique:vehiculos,patente,' . $vehiculoId;
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patente.required' => 'La patente es obligatoria.',
            'patente.unique' => 'Esta patente ya está registrada.',
            'patente.max' => 'La patente no puede tener más de 8 caracteres.',
            'marca.required' => 'La marca es obligatoria.',
            'marca.max' => 'La marca no puede tener más de 50 caracteres.',
            'modelo.required' => 'El modelo es obligatorio.',
            'modelo.max' => 'El modelo no puede tener más de 50 caracteres.',
            'tipo.required' => 'El tipo de vehículo es obligatorio.',
            'tipo.in' => 'El tipo debe ser: auto, motocicleta o camioneta.',
            'color.required' => 'El color es obligatorio.',
            'color.max' => 'El color no puede tener más de 30 caracteres.',
            'year.integer' => 'El año debe ser un número entero.',
            'year.min' => 'El año debe ser mayor a 1900.',
            'year.max' => 'El año no puede ser mayor al año siguiente.',
            'observaciones.max' => 'Las observaciones no pueden tener más de 500 caracteres.',
        ];
    }
}
