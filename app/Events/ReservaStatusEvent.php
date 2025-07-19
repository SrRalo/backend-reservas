<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservaStatusEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reserva;
    public $action;
    public $user;

    public function __construct($reserva, $action, $user = null)
    {
        $this->reserva = $reserva;
        $this->action = $action; // 'created', 'updated', 'cancelled', 'completed'
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return [
            new Channel('reservas'),
            new Channel('admin-dashboard'),
        ];
    }

    public function broadcastAs()
    {
        return 'reserva.status';
    }
}
