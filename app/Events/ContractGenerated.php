<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $solicitudId;
    public $contratoId;
    public $propertyName;
    public $clienteId;
    public $propietarioId;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $solicitudId,
        int $contratoId,
        string $propertyName,
        int $clienteId,
        int $propietarioId
    ) {
        $this->solicitudId = $solicitudId;
        $this->contratoId = $contratoId;
        $this->propertyName = $propertyName;
        $this->clienteId = $clienteId;
        $this->propietarioId = $propietarioId;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('rentals'),
            new Channel('user.' . $this->clienteId),
            new Channel('user.' . $this->propietarioId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'solicitud_id' => $this->solicitudId,
            'contrato_id' => $this->contratoId,
            'property_name' => $this->propertyName,
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
        return 'contract-generated';
    }
}
