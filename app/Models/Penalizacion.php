<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalizacion extends Model
{
    protected $table = 'penalizaciones';

    protected $fillable = [
        'motivo',
        'tipo',
        'fecha',
        'usuario_reserva_id', 
        'ticket_id',
        'estado',
        'monto'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto' => 'decimal:2'
    ];

    public function usuarioReserva(): BelongsTo
    {
        return $this->belongsTo(UsuarioReserva::class, 'usuario_reserva_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
