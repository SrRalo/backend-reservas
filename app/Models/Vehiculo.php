<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';
    
    // Especificar que la clave primaria es 'placa' y no es auto-incremental
    protected $primaryKey = 'placa';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'usuario_id',
        'placa',
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
