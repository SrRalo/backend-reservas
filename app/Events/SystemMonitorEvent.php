<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemMonitorEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $service;
    public $status;
    public $data;
    public $timestamp;

    public function __construct($service, $status, $data = [])
    {
        $this->service = $service;
        $this->status = $status;
        $this->data = $data;
        $this->timestamp = now();
    }

    public function broadcastOn()
    {
        return new Channel('system-monitor');
    }

    public function broadcastAs()
    {
        return 'system.monitor';
    }

    public function broadcastWith()
    {
        return [
            'service' => $this->service,
            'status' => $this->status,
            'data' => $this->data,
            'timestamp' => $this->timestamp->toISOString(),
        ];
    }
}
