<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservaRequest extends FormRequest
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
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'espacio_id' => 'required|exists:espacios,id',
            'fecha_inicio' => 'required|date|after:now',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'observaciones' => 'nullable|string|max:500',
        ];

        // Reglas adicionales para actualizaciones
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['estado'] = [
                'sometimes',
                Rule::in(['pendiente', 'confirmada', 'activa', 'completada', 'cancelada'])
            ];
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
            'vehiculo_id.required' => 'El vehículo es obligatorio.',
            'vehiculo_id.exists' => 'El vehículo seleccionado no existe.',
            'espacio_id.required' => 'El espacio es obligatorio.',
            'espacio_id.exists' => 'El espacio seleccionado no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.after' => 'La fecha de inicio debe ser posterior a la fecha actual.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'estado.in' => 'El estado debe ser: pendiente, confirmada, activa, completada o cancelada.',
            'observaciones.max' => 'Las observaciones no pueden tener más de 500 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'vehiculo_id' => 'vehículo',
            'espacio_id' => 'espacio',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'observaciones' => 'observaciones',
        ];
    }
}
