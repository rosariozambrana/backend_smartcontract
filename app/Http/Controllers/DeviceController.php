<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Inmueble;
use App\Models\InmuebleDevice;
use App\Services\BroadcastService;
use App\Services\ResponseService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Register a new device (for ESP32)
     */
    public function registerDevice(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|string',
                'type' => 'required|in:chapa,luz',
                'macAddress' => 'required|string',
            ]);

            $device = Device::updateOrCreate(
                ['id' => $request->id],
                [
                    'type' => $request->type,
                    'macAddress' => $request->macAddress,
                    'status' => 'inactivo',
                ]
            );

            return ResponseService::success('Dispositivo registrado exitosamente', $device, 201);
        } catch (\Exception $e) {
            return ResponseService::error('Error al registrar dispositivo', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Control a device for a specific property
     */
    public function controlDevice(Request $request, $inmuebleId)
    {
        try {
            $request->validate([
                'deviceId' => 'required|string',
                'action' => 'required|in:activar,desactivar',
            ]);

            // Verify device belongs to property
            $relacion = InmuebleDevice::where('inmueble_id', $inmuebleId)
                ->where('device_id', $request->deviceId)
                ->first();

            if (!$relacion) {
                return ResponseService::error('Dispositivo no asignado a este inmueble', [], 403);
            }

            // Get device to determine type
            $device = Device::find($request->deviceId);
            if (!$device) {
                return ResponseService::error('Dispositivo no encontrado', [], 404);
            }

            // Determine new status based on device type and action
            $nuevoEstado = match([$device->type, $request->action]) {
                ['chapa', 'activar'] => 'abierta',
                ['chapa', 'desactivar'] => 'cerrada',
                ['luz', 'activar'] => 'encendida',
                ['luz', 'desactivar'] => 'apagada',
                default => 'inactivo',
            };

            // Update relation status
            $relacion->update([
                'status' => $nuevoEstado,
                'last_updated' => now(),
            ]);

            // Update device status
            $device->update([
                'status' => in_array($nuevoEstado, ['abierta', 'encendida']) ? 'activo' : 'inactivo'
            ]);

            // Broadcast event
            BroadcastService::deviceStatusChanged(
                $request->deviceId,
                $inmuebleId,
                $nuevoEstado,
                $request->action
            );

            return ResponseService::success('Dispositivo controlado exitosamente', [
                'success' => true,
                'status' => $nuevoEstado
            ]);
        } catch (\Exception $e) {
            return ResponseService::error('Error al controlar dispositivo', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all devices for a property
     */
    public function getDevicesByProperty($inmuebleId)
    {
        try {
            $inmueble = Inmueble::with(['devices' => function($query) {
                $query->withPivot('role', 'status', 'fecha_asignacion', 'last_updated');
            }])->findOrFail($inmuebleId);

            return ResponseService::success('Dispositivos obtenidos exitosamente', $inmueble->devices);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener dispositivos', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign a device to a property
     */
    public function assignDeviceToProperty(Request $request, $inmuebleId)
    {
        try {
            $request->validate([
                'deviceId' => 'required|string',
                'role' => 'required|in:chapa,luz',
                'status' => 'nullable|string',
            ]);

            // Verify property exists
            $inmueble = Inmueble::findOrFail($inmuebleId);

            // Verify device exists
            $device = Device::findOrFail($request->deviceId);

            // Verify role matches device type
            if ($device->type !== $request->role) {
                return ResponseService::error('El rol no coincide con el tipo de dispositivo', [], 400);
            }

            // Create or update relation
            $relacion = InmuebleDevice::updateOrCreate(
                [
                    'inmueble_id' => $inmuebleId,
                    'device_id' => $request->deviceId
                ],
                [
                    'role' => $request->role,
                    'status' => $request->status ?? 'inactivo',
                    'fecha_asignacion' => now(),
                    'last_updated' => now(),
                ]
            );

            return ResponseService::success('Dispositivo asignado exitosamente', $relacion, 201);
        } catch (\Exception $e) {
            return ResponseService::error('Error al asignar dispositivo', ['error' => $e->getMessage()], 500);
        }
    }
}
