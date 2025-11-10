<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contratoId;
    public $propertyName;
    public $amount;
    public $clienteId;
    public $propietarioId;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $contratoId,
        string $propertyName,
        float $amount,
        int $clienteId,
        int $propietarioId
    ) {
        $this->contratoId = $contratoId;
        $this->propertyName = $propertyName;
        $this->amount = $amount;
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
            'contrato_id' => $this->contratoId,
            'property_name' => $this->propertyName,
            'amount' => $this->amount,
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
        return 'payment-received';
    }
}
