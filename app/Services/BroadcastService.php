<?php

namespace App\Services;

use App\Events\RequestStatusChanged;
use App\Events\ContractGenerated;
use App\Events\PaymentReceived;
use App\Events\DeviceStatusChanged;

class BroadcastService
{
    /**
     * Broadcast request status change event
     */
    public static function requestStatusChanged(
        int $solicitudId,
        string $propertyName,
        string $status,
        ?int $userId = null,
        ?int $clienteId = null,
        ?int $propietarioId = null
    ): void {
        broadcast(new RequestStatusChanged(
            $solicitudId,
            $propertyName,
            $status,
            $userId,
            $clienteId,
            $propietarioId
        ))->toOthers();
    }

    /**
     * Broadcast contract generated event
     */
    public static function contractGenerated(
        int $solicitudId,
        int $contratoId,
        string $propertyName,
        int $clienteId,
        int $propietarioId
    ): void {
        broadcast(new ContractGenerated(
            $solicitudId,
            $contratoId,
            $propertyName,
            $clienteId,
            $propietarioId
        ))->toOthers();
    }

    /**
     * Broadcast payment received event
     */
    public static function paymentReceived(
        int $contratoId,
        string $propertyName,
        float $amount,
        int $clienteId,
        int $propietarioId
    ): void {
        broadcast(new PaymentReceived(
            $contratoId,
            $propertyName,
            $amount,
            $clienteId,
            $propietarioId
        ))->toOthers();
    }

    /**
     * Broadcast device status changed event
     */
    public static function deviceStatusChanged(
        string $deviceId,
        int $inmuebleId,
        string $status,
        string $action
    ): void {
        broadcast(new DeviceStatusChanged(
            $deviceId,
            $inmuebleId,
            $status,
            $action
        ))->toOthers();
    }
}
