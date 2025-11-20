<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasDynamicQuery;
use App\Models\Accesorio;
use App\Models\Contrato;
use App\Http\Requests\StoreContratoRequest;
use App\Http\Requests\UpdateContratoRequest;
use App\Services\PermissionService;
use App\Services\ResponseService;
use App\Services\Blockchain\RentalContractService;
use App\Services\Blockchain\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ContratoController extends Controller
{
    use HasDynamicQuery;

    public Contrato $model;
    public $rutaVisita = 'Contrato';
    protected $rentalContractService;
    protected $walletService;

    public function __construct(
        RentalContractService $rentalContractService,
        WalletService $walletService
    )
    {
        $this->model = new Contrato();
        $this->rentalContractService = $rentalContractService;
        $this->walletService = $walletService;
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
    public function store(StoreContratoRequest $request)
    {
        // ========================================
        // LOG DE AUDITORÍA - SOLICITUD RECIBIDA
        // ========================================
        Log::info('ContratoController::store - Solicitud de creación de contrato recibida', [
            'request_all' => $request->all(),
            'cliente_aprobado_raw' => $request->cliente_aprobado,
            'cliente_aprobado_type' => gettype($request->cliente_aprobado),
            'timestamp' => now()->toISOString(),
        ]);

        $txHash = null; // Track if blockchain contract was created

        try {
            // ========================================
            // PASO 1: OBTENER USER_ID DEL CLIENTE
            // ========================================
            $userId = $request->user_id;

            if ($request->solicitud_alquiler_id) {
                $solicitud = \App\Models\SolicitudAlquilerModel::find($request->solicitud_alquiler_id);
                if ($solicitud) {
                    // El user_id debe ser del CLIENTE (quien hizo la solicitud), no del propietario
                    $userId = $solicitud->user_id;
                }
            }

            Log::info('ContratoController::store - User ID identificado', [
                'user_id' => $userId,
                'solicitud_alquiler_id' => $request->solicitud_alquiler_id,
            ]);

            // ========================================
            // PASO 2: INICIAR TRANSACCIÓN ATÓMICA
            // ========================================
            Log::info('ContratoController::store - Iniciando transacción atómica');
            DB::beginTransaction();

            // ========================================
            // PASO 3: CREAR CONTRATO EN BASE DE DATOS
            // ========================================
            $data = $this->model::create([
                'user_id' => $userId,
                'inmueble_id' => $request->inmueble_id,
                'solicitud_alquiler_id' => $request->solicitud_alquiler_id,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'monto' => $request->monto,
                'estado' => $request->estado,
                'cliente_aprobado' => false, // SIEMPRE false, solo se aprueba vía /blockchain/contract/approve
                'fecha_pago' => $request->fecha_pago,
                'blockchain_address' => null, // Se asignará después de crear en blockchain
                'condicionales' => json_encode($request->condicionales ?? []),
            ]);

            Log::info('ContratoController::store - Contrato creado en BD', [
                'contrato_id' => $data->id,
                'cliente_aprobado_guardado' => $data->cliente_aprobado,
            ]);

            // ========================================
            // PASO 3.1: ACTUALIZAR ESTADO DE SOLICITUD
            // ========================================
            if ($request->solicitud_alquiler_id) {
                $solicitud = \App\Models\SolicitudAlquilerModel::find($request->solicitud_alquiler_id);
                if ($solicitud) {
                    $solicitud->estado = 'contrato_generado';
                    $solicitud->save();

                    Log::info('ContratoController::store - Estado de solicitud actualizado', [
                        'solicitud_id' => $solicitud->id,
                        'nuevo_estado' => 'contrato_generado',
                    ]);

                    // Cargar relaciones para broadcast
                    $solicitud->load(['inmueble.user', 'user']);

                    // Broadcast evento de cambio de estado
                    broadcast(new \App\Events\RequestStatusChanged(
                        $solicitud->id,
                        $solicitud->inmueble->nombre ?? 'Propiedad',
                        $solicitud->estado,
                        $solicitud->user_id,
                        $solicitud->user_id,
                        $solicitud->inmueble->user_id ?? null
                    ))->toOthers();
                }
            }

            // Cargar relaciones necesarias para blockchain
            $data->load(['user', 'inmueble.user']);

            // ========================================
            // PASO 4: ASIGNAR WALLETS SI NO TIENEN
            // ========================================
            $landlord = $data->inmueble->user; // Propietario
            $tenant = $data->user; // Cliente/Inquilino

            Log::info('ContratoController::store - Verificando wallets', [
                'landlord_id' => $landlord->id,
                'landlord_wallet' => $landlord->wallet_address,
                'tenant_id' => $tenant->id,
                'tenant_wallet' => $tenant->wallet_address,
            ]);

            if (!$landlord->wallet_address) {
                Log::info('ContratoController::store - Asignando wallet a propietario', ['landlord_id' => $landlord->id]);
                $this->walletService->assignWalletToUser($landlord);
                $landlord->refresh();
            }

            if (!$tenant->wallet_address) {
                Log::info('ContratoController::store - Asignando wallet a inquilino', ['tenant_id' => $tenant->id]);
                $this->walletService->assignWalletToUser($tenant);
                $tenant->refresh();
            }

            Log::info('ContratoController::store - Wallets verificados', [
                'landlord_wallet' => $landlord->wallet_address,
                'tenant_wallet' => $tenant->wallet_address,
            ]);

            // ========================================
            // PASO 5: CREAR CONTRATO EN BLOCKCHAIN
            // ========================================
            Log::info('ContratoController::store - Llamando a RentalContractService para crear contrato en blockchain', [
                'contrato_id' => $data->id,
                'landlord_wallet' => $landlord->wallet_address,
                'tenant_wallet' => $tenant->wallet_address,
                'monto' => $data->monto,
            ]);

            $result = $this->rentalContractService->createRentalContract($data, $landlord, $tenant);

            if (!$result['success']) {
                Log::error('ContratoController::store - Creación de contrato blockchain falló, realizando rollback', [
                    'contrato_id' => $data->id,
                    'error' => $result['error'],
                ]);
                DB::rollBack();
                return ResponseService::error('Error al crear contrato en blockchain', $result['error'], 500);
            }

            // Guardar tx_hash para manejo de errores críticos
            $txHash = $result['tx_hash'];
            Log::info('ContratoController::store - Contrato blockchain creado exitosamente', [
                'tx_hash' => $txHash,
                'block_number' => $result['block_number'],
            ]);

            // ========================================
            // PASO 6: ACTUALIZAR BLOCKCHAIN ADDRESS EN BD
            // ========================================
            Log::info('ContratoController::store - Guardando blockchain_address en contrato', [
                'contrato_id' => $data->id,
                'tx_hash' => $txHash,
            ]);

            $data->blockchain_address = $txHash;
            $data->save();

            // ========================================
            // PASO 7: COMMIT SI TODO SALIÓ BIEN
            // ========================================
            Log::info('ContratoController::store - Realizando commit de transacción DB', [
                'contrato_id' => $data->id,
            ]);
            DB::commit();

            Log::info('ContratoController::store - Contrato creado exitosamente (BD + Blockchain)', [
                'contrato_id' => $data->id,
                'tx_hash' => $txHash,
                'block_number' => $result['block_number'],
            ]);

            // Recargar para respuesta completa
            $data->refresh();
            $data->load(['user', 'inmueble.user']);

            return ResponseService::success('Registro guardado correctamente', $data);

        } catch (\Exception $e) {
            // ========================================
            // ROLLBACK DE BASE DE DATOS
            // ========================================
            DB::rollBack();

            // ========================================
            // CASO CRÍTICO: Blockchain exitoso pero BD falló
            // ========================================
            if ($txHash) {
                Log::critical('ContratoController::store - INCONSISTENCIA CRÍTICA: Contrato blockchain exitoso pero BD falló - REQUIERE RECONCILIACIÓN MANUAL', [
                    'tx_hash' => $txHash,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => now()->toISOString(),
                ]);

                return ResponseService::error(
                    'Contrato creado en blockchain pero error al guardar en base de datos. Contacte soporte con este código: ' . $txHash,
                    $e->getMessage(),
                    500
                );
            }

            // Error antes del blockchain - todo bien, solo loguear
            Log::error('ContratoController::store - Error al crear el contrato', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseService::error('Error al guardar el registro', $e->getMessage(), 500);
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
            Log::info('updateEstado called', [
                'contrato_id' => $contrato->id,
                'request_all' => $request->all(),
                'cliente_aprobado_antes' => $contrato->cliente_aprobado,
            ]);
            $contrato->update(['estado' => $request->estado]);
            $contrato->refresh();
            Log::info('updateEstado after update', [
                'contrato_id' => $contrato->id,
                'cliente_aprobado_despues' => $contrato->cliente_aprobado,
            ]);
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
            $contratos = $this->model::where('user_id', $userId)
                ->with(['user', 'inmueble'])
                ->get();
            return ResponseService::success('Contratos obtenidos correctamente', $contratos);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener los contratos', $e->getMessage());
        }
    }
    // dame contrato por propietario
    public function getContratosByPropietarioId($propietarioId)
    {
        try {
            // Los contratos del propietario son aquellos donde el inmueble le pertenece
            $contratos = $this->model::whereHas('inmueble', function ($query) use ($propietarioId) {
                $query->where('user_id', $propietarioId);
            })
            ->with(['user', 'inmueble', 'pagos'])
            ->get();
            return ResponseService::success('Contratos obtenidos correctamente', $contratos);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener los contratos', $e->getMessage());
        }
    }
}
