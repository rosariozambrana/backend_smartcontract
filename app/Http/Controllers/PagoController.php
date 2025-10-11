<?php

namespace App\Http\Controllers;

use App\Models\Accesorio;
use App\Models\Pago;
use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\UpdatePagoRequest;
use App\Services\PermissionService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PagoController extends Controller
{
    public Pago $model;
    public $rutaVisita = 'Pago';
    public function __construct()
    {
        $this->model = new Pago();
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
    public function store(StorePagoRequest $request)
    {
        try {
            $data = $this->model::create([
                'contrato_id' => $request->contrato_id,
                'monto' => $request->monto,
                'fecha_pago' => $request->fecha_pago,
                'estado' => $request->estado,
                'blockchain_id' => $request->blockchain_id,
                'historial_acciones' => json_encode($request->historial_acciones ?? []),
            ]);
            return ResponseService::success('Registro guardado correctamente', $data);
        } catch (\Exception $e) {
            return ResponseService::error('Error al guardar el registro', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pago $pago)
    {
        try{
            return ResponseService::success('Registro encontrado', $pago);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener el registro', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pago $pago)
    {
        $permiso = strtolower($this->rutaVisita);
        if (!Auth::user()->can($permiso.'-edit')) {
            abort(403);
        }
        return Inertia::render($this->rutaVisita . '/CreateUpdate', array_merge([
            'isCreate' => false,
            'model' => $pago,
        ], PermissionService::getPermissions($permiso)));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePagoRequest $request, Pago $pago)
    {
        try {
            $pago->update($request->all());
            return ResponseService::success('Registro actualizado correctamente', $pago);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el registro', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pago $pago)
    {
        try {
            $pago->delete();
            return ResponseService::success('Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ResponseService::error('Error al eliminar el registro', $e->getMessage());
        }
    }

    public function getPagosContrato($contratoId)
    {
        try {
            $pagos = Pago::where('contrato_id', $contratoId)->get();
            return ResponseService::success('Pagos obtenidos correctamente', $pagos);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener los pagos', $e->getMessage());
        }
    }


    public function getPagosContratoCliente($userId)
    {
        try {
            $pagos = Pago::whereHas('contratos', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->get();
            return ResponseService::success('Pagos obtenidos correctamente', $pagos);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener los pagos', $e->getMessage());
        }
    }

    public function getPagosPendientesCliente($userId)
    {
        try {
            $pagos = Pago::whereHas('contratos', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->where('estado', 'pendiente')->get();
            return ResponseService::success('Pagos pendientes obtenidos correctamente', $pagos);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener los pagos pendientes', $e->getMessage());
        }
    }
    public function getPagosCompletadosCliente($userId)
    {
        try {
            $pagos = Pago::whereHas('contratos', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->where('estado', 'aprobado')->get();
            return ResponseService::success('Pagos completados obtenidos correctamente', $pagos);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener los pagos completados', $e->getMessage());
        }
    }
    public function updateEstado(Request $request, Pago $pago)
    {
        try {
            $estado = $request->input('estado');
            if (!in_array($estado, ['pendiente', 'pagado', 'cancelado'])) {
                return ResponseService::error('Estado no válido', '', 400);
            }
            $pago->estado = $estado;
            $pago->save();
            return ResponseService::success('Estado actualizado correctamente', $pago);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el estado', $e->getMessage());
        }
    }
    public function updateBlockchain(Request $request, Pago $pago)
    {
        try {
            $blockchainData = $request->input('blockchain_id');
            if (empty($blockchainData)) {
                return ResponseService::error('Datos de blockchain no válidos', '', 400);
            }
            $pago->blockchain_id = $blockchainData;
            $pago->save();
            return ResponseService::success('Datos de blockchain actualizados correctamente', $pago);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar los datos de blockchain', $e->getMessage());
        }
    }
}
