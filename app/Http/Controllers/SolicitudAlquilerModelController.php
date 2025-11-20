<?php

namespace App\Http\Controllers;

use App\Models\SolicitudAlquilerModel;
use App\Models\Inmueble;
use App\Http\Requests\StoreSolicitudAlquilerModelRequest;
use App\Http\Requests\UpdateSolicitudAlquilerModelRequest;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use App\Events\RequestStatusChanged;

class SolicitudAlquilerModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
            // Validar que el inmueble no esté ocupado
            $inmueble = Inmueble::find($request->inmueble_id);

            if (!$inmueble) {
                return ResponseService::error('Inmueble no encontrado', [], 404);
            }

            if ($inmueble->isOcupado) {
                return ResponseService::error('Este inmueble ya está alquilado', [], 400);
            }

        try{
            $solicitudAlquiler = SolicitudAlquilerModel::create([
                'inmueble_id' => $request->inmueble_id,
                'user_id' => $request->user_id,
                'estado' => $request->estado,
                'mensaje' => $request->mensaje,
                'servicios_basicos' => json_encode($request->servicios_basicos ?? []),
            ]);

            // Cargar relaciones para broadcast
            $solicitudAlquiler->load(['inmueble.user', 'user']);

            // Broadcast evento de nueva solicitud
            broadcast(new RequestStatusChanged(
                $solicitudAlquiler->id,
                $solicitudAlquiler->inmueble->nombre ?? 'Propiedad',
                $solicitudAlquiler->estado,
                $solicitudAlquiler->user_id,
                $solicitudAlquiler->user_id,
                $solicitudAlquiler->inmueble->user_id ?? null
            ))->toOthers();

            return ResponseService::success('Solicitud de alquiler creada exitosamente', $solicitudAlquiler, 200);
        } catch (\Exception $e) {
            return ResponseService::error('Error al crear la solicitud de alquiler', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SolicitudAlquilerModel $solicitudAlquilerModel)
    {
        try {
            return ResponseService::success('Solicitud de alquiler obtenida exitosamente', $solicitudAlquilerModel, 200);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener la solicitud de alquiler', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SolicitudAlquilerModel $solicitudAlquilerModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSolicitudAlquilerModelRequest $request, SolicitudAlquilerModel $solicitudAlquilerModel)
    {
        try {
            $solicitudAlquilerModel->update($request->validated());
            return ResponseService::success('Solicitud de alquiler actualizada exitosamente', $solicitudAlquilerModel, 200);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar la solicitud de alquiler', ['error' => $e->getMessage()], 500);
        }
    }
    // actualizar estado de la solicitud
    public function updateEstado(Request $request, SolicitudAlquilerModel $solicitudAlquilerModel)
    {
        try {
            $solicitudAlquilerModel->update(['estado' => $request->estado]);

            // Cargar relaciones para broadcast
            $solicitudAlquilerModel->load(['inmueble.user', 'user']);

            // Broadcast evento de cambio de estado
            broadcast(new RequestStatusChanged(
                $solicitudAlquilerModel->id,
                $solicitudAlquilerModel->inmueble->nombre ?? 'Propiedad',
                $solicitudAlquilerModel->estado,
                $solicitudAlquilerModel->user_id,
                $solicitudAlquilerModel->user_id,
                $solicitudAlquilerModel->inmueble->user_id ?? null
            ))->toOthers();

            return ResponseService::success('Estado de la solicitud de alquiler actualizado exitosamente', $solicitudAlquilerModel, 200);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el estado de la solicitud de alquiler', ['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SolicitudAlquilerModel $solicitudAlquilerModel)
    {
        try {
            $solicitudAlquilerModel->delete();
            return ResponseService::success('Solicitud de alquiler eliminada exitosamente', [], 200);
        } catch (\Exception $e) {
            return ResponseService::error('Error al eliminar la solicitud de alquiler', ['error' => $e->getMessage()], 500);
        }
    }

    // dame solicitudes por usuario
    public function solicitudesPorClienteId($clienteId)
    {
        try {
            $solicitudes = SolicitudAlquilerModel::where('user_id', $clienteId)
                ->with(['inmueble', 'user'])
                ->get();
            return ResponseService::success('Solicitudes de alquiler obtenidas exitosamente', $solicitudes);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener las solicitudes de alquiler', ['error' => $e->getMessage()], 500);
        }
    }
    // dame solicitudes por propietario id
    public function solicitudesPorPropietario($propietarioId)
    {
        try {
            $solicitudes = SolicitudAlquilerModel::whereHas('inmueble', function ($query) use ($propietarioId) {
                $query->where('user_id', $propietarioId);
            })
            ->with(['inmueble', 'user'])
            ->get();
            return ResponseService::success('Solicitudes de alquiler obtenidas exitosamente', $solicitudes);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener las solicitudes de alquiler', ['error' => $e->getMessage()], 500);
        }
    }

    // dame solicitudes de usuario por estado
    public function solicitudesPorUsuarioYEstado($userId, $estado)
    {
        try {
            $solicitudes = SolicitudAlquilerModel::where('user_id', $userId)
                ->where('estado', $estado)
                ->with(['inmueble', 'user'])
                ->get();
            return ResponseService::success('Solicitudes de alquiler obtenidas exitosamente', $solicitudes);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener las solicitudes de alquiler', ['error' => $e->getMessage()], 500);
        }
    }

}
