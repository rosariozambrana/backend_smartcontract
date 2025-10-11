<?php

namespace App\Http\Controllers;

use App\Models\Accesorio;
use App\Models\TipoInmueble;
use App\Http\Requests\StoreTipoInmuebleRequest;
use App\Http\Requests\UpdateTipoInmuebleRequest;
use App\Services\PermissionService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TipoInmuebleController extends Controller
{
    public TipoInmueble $model;
    public $rutaVisita = 'TipoInmueble';
    public function __construct()
    {
        $this->model = new TipoInmueble();
        /*$this->middleware('permission:almacen-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:almacen-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:almacen-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:almacen-delete', ['only' => ['destroy']]);*/
    }

    public function query(Request $request)
    {
        try {
            $queryStr = $request->get('query', '');
            $perPage = $request->get('perPage', 10);
            $page = $request->get('page', 1);
            $attributes = $request->get('attributes', ['id']); // Atributos por defecto

            // Obtener los atributos del modelo
            $modelAttributes = $this->model->getFillable();
            $modelAttributes[] = 'created_at';
            $modelAttributes[] = 'updated_at';

            // Validación de atributos
            foreach ($attributes as $attribute) {
                if (!in_array($attribute, $modelAttributes)) {
                    return ResponseService::error('Atributo no permitido: ' . $attribute, '', 400);
                }
            }

            $query = $this->model::query();

            if (!empty($queryStr)) {
                $query->where(function ($q) use ($attributes, $queryStr) {
                    foreach ($attributes as $i => $attribute) {
                        if (in_array($attribute, ['created_at', 'updated_at'])) {
                            $method = $i === 0 ? 'whereDate' : 'orWhereDate';
                            $q->$method($attribute, $queryStr);
                        } else {
                            $method = $i === 0 ? 'where' : 'orWhere';
                            $q->$method($attribute, 'LIKE', '%' . $queryStr . '%');
                        }
                    }
                });
            }

            $response = $query->orderBy('id', 'ASC')->get();
            // calcular el total de registros
            $total = $response->count();

            return ResponseService::success(
                "{$total} datos encontrados con {$queryStr}",
                $response
            );
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), '', $e->getCode());
        }
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
    public function store(StoreTipoInmuebleRequest $request)
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
    public function show(TipoInmueble $tipoInmueble)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoInmueble $tipoInmueble)
    {
        $permiso = strtolower($this->rutaVisita);
        if (!Auth::user()->can($permiso.'-edit')) {
            abort(403);
        }
        return Inertia::render($this->rutaVisita . '/CreateUpdate', array_merge([
            'isCreate' => false,
            'model' => $tipoInmueble,
        ], PermissionService::getPermissions($permiso)));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTipoInmuebleRequest $request, TipoInmueble $tipoInmueble)
    {
        try {
            $tipoInmueble->update($request->all());
            return ResponseService::success('Registro actualizado correctamente', $tipoInmueble);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el registro', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoInmueble $tipoInmueble)
    {
        try {
            $tipoInmueble->delete();
            return ResponseService::success('Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ResponseService::error('Error al eliminar el registro', $e->getMessage());
        }
    }
}
