<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'ticket_id',
        'usuario_id',
        'monto',
        'metodo_pago',
        'estado',
        'fecha_pago',
        'referencia_pago',
        'datos_pago'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'datos_pago' => 'array'
    ];

    // Relación con ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    // Relación con usuario
    public function usuario()
    {
        return $this->belongsTo(UsuarioReserva::class, 'usuario_id');
    }
}
