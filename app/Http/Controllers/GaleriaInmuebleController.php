<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasDynamicQuery;
use App\Models\Accesorio;
use App\Models\GaleriaInmueble;
use App\Http\Requests\StoreGaleriaInmuebleRequest;
use App\Http\Requests\UpdateGaleriaInmuebleRequest;
use App\Services\PermissionService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GaleriaInmuebleController extends Controller
{
    use HasDynamicQuery;

    public GaleriaInmueble $model;
    public $rutaVisita = 'GaleriaInmueble';
    public function __construct()
    {
        $this->model = new GaleriaInmueble();
        /*$this->middleware('permission:almacen-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:almacen-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:almacen-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:almacen-delete', ['only' => ['destroy']]);*/
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render($this->rutaVisita . '/Index', array_merge([
            'listado' => $this->model::all(),
        ], PermissionService::getPermissions($this->rutaVisita)));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permiso = strtolower($this->rutaVisita);
        if (!Auth::user()->can($permiso.'-create')) {
            abort(403);
        }
        return Inertia::render($this->rutaVisita . '/CreateUpdate', array_merge([
            'isCreate' => true
        ], PermissionService::getPermissions($permiso)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGaleriaInmuebleRequest $request)
    {
        try {
            $data = $this->model::create($request->all());
            return ResponseService::success('Registro guardado correctamente', $data);
        } catch (\Exception $e) {
            return ResponseService::error('Error al guardar el registro', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GaleriaInmueble $galeriaInmueble)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GaleriaInmueble $galeriaInmueble)
    {
        $permiso = strtolower($this->rutaVisita);
        if (!Auth::user()->can($permiso.'-edit')) {
            abort(403);
        }
        return Inertia::render($this->rutaVisita . '/CreateUpdate', array_merge([
            'isCreate' => false,
            'model' => $galeriaInmueble,
        ], PermissionService::getPermissions($permiso)));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGaleriaInmuebleRequest $request, GaleriaInmueble $galeriaInmueble)
    {
        try {
            $galeriaInmueble->update($request->all());
            return ResponseService::success('Registro actualizado correctamente', $galeriaInmueble);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el registro', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GaleriaInmueble $galeriaInmueble)
    {
        try {
            $galeriaInmueble->delete();
            return ResponseService::success('Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ResponseService::error('Error al eliminar el registro', $e->getMessage());
        }
    }
}
