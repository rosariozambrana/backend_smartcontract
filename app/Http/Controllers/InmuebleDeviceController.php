<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Inmueble;
use App\Models\InmuebleDevice;
use App\Http\Requests\StoreInmuebleDeviceRequest;
use App\Http\Requests\UpdateInmuebleDeviceRequest;
use Illuminate\Support\Facades\Request;

class InmuebleDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($inmuebleId)
    {
        $inmueble = Inmueble::with('devices')->findOrFail($inmuebleId);
        return response()->json($inmueble->devices);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInmuebleDeviceRequest $request, $inmuebleId)
    {
        $request->validate([
            'device_id' => 'required|string',
            'role' => 'sometimes|string'
        ]);

        $inmueble = Inmueble::findOrFail($inmuebleId);
        $device = Device::findOrFail($request->device_id);

        // Asignar con datos adicionales en la tabla pivote
        $inmueble->devices()->attach($device->device_id, [
            'role' => $request->role ?? 'asignado'
        ]);

        return response()->json(['message' => 'Dispositivo asignado correctamente']);
    }

    // Controlar dispositivo (abrir/cerrar, encender/apagar)
    public function controlDevice(Request $request, $inmuebleId)
    {
        $request->validate([
            'device_id' => 'required|string',
            'action' => 'required|in:abrir,cerrar,encender,apagar'
        ]);

        // Verificar si el dispositivo pertenece al inmueble
        $inmueble = Inmueble::findOrFail($inmuebleId);
        $device = $inmueble->devices()->where('device_id', $request->device_id)->first();

        if (!$device) {
            return response()->json(['error' => 'Dispositivo no asignado a este inmueble'], 403);
        }

        // Actualizar estado del dispositivo
        $newStatus = match ($request->action) {
            'abrir' => 'abierta',
            'cerrar' => 'cerrada',
            'encender' => 'encendida',
            'apagar' => 'apagada',
        };

        $device->update(['status' => $newStatus]);

        // Aquí podrías enviar un comando al ESP32 (ej: mediante WebSockets o HTTP)
        // Ejemplo con HTTP (ajusta la IP del ESP32):
        // Http::post("http://ESP32_IP/control", ['action' => $request->action]);

        return response()->json([
            'message' => 'Comando enviado',
            'device_status' => $newStatus
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(InmuebleDevice $inmuebleDevice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InmuebleDevice $inmuebleDevice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInmuebleDeviceRequest $request, InmuebleDevice $inmuebleDevice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InmuebleDevice $inmuebleDevice)
    {
        //
    }
}
