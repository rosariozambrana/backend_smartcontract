<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deviceId;
    public $inmuebleId;
    public $status;
    public $action;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $deviceId,
        int $inmuebleId,
        string $status,
        string $action
    ) {
        $this->deviceId = $deviceId;
        $this->inmuebleId = $inmuebleId;
        $this->status = $status;
        $this->action = $action;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('inmueble.' . $this->inmuebleId),
            new Channel('devices'), // Canal global para dispositivos
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'deviceId' => $this->deviceId,
            'inmuebleId' => $this->inmuebleId,
            'status' => $this->status,
            'action' => $this->action,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'device-status-changed';
    }
}
