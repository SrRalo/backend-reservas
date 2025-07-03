<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'usuario_id',
        'placa',
        'tipo',
        'marca',
        'modelo',
        'color',
        'estado'
    ];

    protected $attributes = [
        'estado' => 'activo',
    ];

    // Relación con UsuarioReserva
    public function usuario()
    {
        return $this->belongsTo(UsuarioReserva::class, 'usuario_id');
    }
}
