<?php

namespace App\Http\Controllers;

use App\Models\Accesorio;
use App\Models\Contrato;
use App\Http\Requests\StoreContratoRequest;
use App\Http\Requests\UpdateContratoRequest;
use App\Services\PermissionService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContratoController extends Controller
{
    public Contrato $model;
    public $rutaVisita = 'Contrato';
    public function __construct()
    {
        $this->model = new Contrato();
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

            // Validar que los atributos estén en la lista de atributos permitidos
            foreach ($attributes as $attribute) {
                if (!in_array($attribute, $modelAttributes)) {
                    return ResponseService::error('Atributo no permitido: ' . $attribute, '', 400);
                }
            }

            // Construir la consulta dinámica
            $query = $this->model::query();
            $first = true;
            foreach ($attributes as $attribute) {
                if ($first) {
                    if (in_array($attribute, ['created_at', 'updated_at'])) {
                        $query->whereDate($attribute, $queryStr);
                    } else {
                        $query->where($attribute, 'LIKE', '%' . $queryStr . '%');
                    }
                    $first = false;
                } else {
                    if (in_array($attribute, ['created_at', 'updated_at'])) {
                        $query->orWhereDate($attribute, $queryStr);
                    } else {
                        $query->orWhere($attribute, 'LIKE', '%' . $queryStr . '%');
                    }
                }
            }

            //$response = $query->orderBy('id', 'ASC')->paginate($perPage, ['*'], 'page', $page);
            $response = $query->orderBy('id', 'ASC')->get();
            $cantidad = count($response);
            $str = strval($cantidad);
            return ResponseService::success("$str datos encontrados con $queryStr", $response);
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
    public function store(StoreContratoRequest $request)
    {
        try {
            $data = $this->model::create([
                'user_id' => $request->user_id,
                'inmueble_id' => $request->inmueble_id,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'monto' => $request->monto,
                'estado' => $request->estado,
                'cliente_aprobado' => $request->cliente_aprobado,
                'fecha_pago' => $request->fecha_pago,
                'blockchain_address' => $request->blockchain_address,
                'condicionales' => json_encode($request->condicionales ?? []),
            ]);
            return ResponseService::success('Registro guardado correctamente', $data);
        } catch (\Exception $e) {
            return ResponseService::error('Error al guardar el registro', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Contrato $contrato)
    {
        try{
            return ResponseService::success('Registro encontrado', $contrato);
        } catch (\Exception $e) {
            return ResponseService::error('Error al mostrar el registro', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contrato $contrato)
    {
        $permiso = strtolower($this->rutaVisita);
        if (!Auth::user()->can($permiso.'-edit')) {
            abort(403);
        }
        return Inertia::render($this->rutaVisita . '/CreateUpdate', array_merge([
            'isCreate' => false,
            'model' => $contrato,
        ], PermissionService::getPermissions($permiso)));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContratoRequest $request, Contrato $contrato)
    {
        try {
            $contrato->update($request->all());
            return ResponseService::success('Registro actualizado correctamente', $contrato);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el registro', $e->getMessage());
        }
    }
    // actualizar estado del contrato
    public function updateEstado(Request $request, Contrato $contrato)
    {
        try {
            $contrato->update(['estado' => $request->estado]);
            return ResponseService::success('Estado del contrato actualizado correctamente', $contrato);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el estado del contrato', $e->getMessage());
        }
    }
    // actualizar contrato cliente aprobado
    public function updateClienteAprobado(Request $request, Contrato $contrato)
    {
        try {
            $contrato->update(['cliente_aprobado' => $request->cliente_aprobado]);
            return ResponseService::success('Contrato cliente aprobado actualizado correctamente', $contrato);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el contrato cliente aprobado', $e->getMessage());
        }
    }
    // actualizar fecha de pago del contrato
    public function updateFechaPago(Request $request, Contrato $contrato)
    {
        try {
            $contrato->update(['fecha_pago' => $request->fecha_pago]);
            return ResponseService::success('Fecha de pago del contrato actualizada correctamente', $contrato);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar la fecha de pago del contrato', $e->getMessage());
        }
    }
    // actualizar blockchain del contrato
    public function updateBlockchain(Request $request, Contrato $contrato)
    {
        try {
            $contrato->update(['blockchain_address' => $request->blockchain_address]);
            return ResponseService::success('Blockchain del contrato actualizado correctamente', $contrato);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar la blockchain del contrato', $e->getMessage());
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contrato $contrato)
    {
        try {
            $contrato->delete();
            return ResponseService::success('Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ResponseService::error('Error al eliminar el registro', $e->getMessage());
        }
    }
    // dame contrato por usuario
    public function getContratosByClienteId($userId)
    {
        try {
            $contratos = $this->model::where('user_id', $userId)->get();
            return ResponseService::success('Contratos obtenidos correctamente', $contratos);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener los contratos', $e->getMessage());
        }
    }
    // dame contrato por propietario
    public function getContratosByPropietarioId($propietarioId)
    {
        try {
            $contratos = $this->model::where('user_id', $propietarioId)->get();
            return ResponseService::success('Contratos obtenidos correctamente', $contratos);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener los contratos', $e->getMessage());
        }
    }
}
