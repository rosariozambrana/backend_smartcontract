<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $solicitudId;
    public $propertyName;
    public $status;
    public $userId;
    public $clienteId;
    public $propietarioId;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $solicitudId,
        string $propertyName,
        string $status,
        ?int $userId = null,
        ?int $clienteId = null,
        ?int $propietarioId = null
    ) {
        $this->solicitudId = $solicitudId;
        $this->propertyName = $propertyName;
        $this->status = $status;
        $this->userId = $userId;
        $this->clienteId = $clienteId;
        $this->propietarioId = $propietarioId;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Agregar canales específicos por usuario
        if ($this->clienteId) {
            $channels[] = new Channel('user.' . $this->clienteId);
        }
        if ($this->propietarioId) {
            $channels[] = new Channel('user.' . $this->propietarioId);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'solicitud_id' => $this->solicitudId,
            'property_name' => $this->propertyName,
            'status' => $this->status,
            'usuario_id' => $this->userId,
            'cliente_id' => $this->clienteId,
            'propietario_id' => $this->propietarioId,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'request-status-changed';
    }
}
