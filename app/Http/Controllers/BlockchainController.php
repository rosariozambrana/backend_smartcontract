<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Blockchain\BlockchainService;
use App\Services\Blockchain\RentalContractService;
use App\Services\Blockchain\WalletService;
use App\Services\ResponseService;
use App\Models\Contrato;
use App\Models\User;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class BlockchainController extends Controller
{
    protected $blockchainService;
    protected $rentalContractService;
    protected $walletService;

    public function __construct(
        BlockchainService $blockchainService,
        RentalContractService $rentalContractService,
        WalletService $walletService
    ) {
        $this->blockchainService = $blockchainService;
        $this->rentalContractService = $rentalContractService;
        $this->walletService = $walletService;
    }

    /**
     * Check blockchain connection status
     * GET /api/app/blockchain/status
     */
    public function status()
    {
        $status = $this->blockchainService->checkConnection();
        return ResponseService::success('Blockchain status retrieved', $status);
    }

    /**
     * Get wallet balance
     * GET /api/app/blockchain/balance/{userId}
     */
    public function getBalance($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $balance = $this->walletService->getUserBalance($user);

            if (!$balance['success']) {
                return ResponseService::error($balance['error'], '', 400);
            }

            return ResponseService::success('Balance retrieved', $balance);

        } catch (Exception $e) {
            return ResponseService::error('Failed to get balance', $e->getMessage(), 500);
        }
    }

    /**
     * Assign wallet to user
     * POST /api/app/blockchain/wallet/assign/{userId}
     */
    public function assignWallet($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $result = $this->walletService->assignWalletToUser($user);

            if (!$result['success']) {
                return ResponseService::error($result['error'], '', 400);
            }

            return ResponseService::success('Wallet assigned successfully', [
                'user_id' => $user->id,
                'wallet_address' => $result['address']
            ]);

        } catch (Exception $e) {
            return ResponseService::error('Failed to assign wallet', $e->getMessage(), 500);
        }
    }

    /**
     * Create rental contract on blockchain
     * POST /api/app/blockchain/contract/create
     *
     * Request body:
     * {
     *   "contrato_id": 1
     * }
     */
    public function createContract(Request $request)
    {
        $request->validate([
            'contrato_id' => 'required|integer|exists:contratos,id'
        ]);

        // ========================================
        // LOG DE AUDITORÍA - SOLICITUD RECIBIDA
        // ========================================
        Log::info('Solicitud de creación de contrato blockchain recibida', [
            'contrato_id' => $request->contrato_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        $txHash = null; // Track if blockchain contract was created

        try {
            $contrato = Contrato::with(['user', 'inmueble.user'])->findOrFail($request->contrato_id);

            // ========================================
            // PASO 1: VALIDAR TODO ANTES DE BLOCKCHAIN
            // ========================================
            Log::info('Iniciando validaciones de creación de contrato', [
                'contrato_id' => $contrato->id,
            ]);

            // Get landlord (propietario) and tenant (cliente)
            $landlord = $contrato->inmueble->user; // Owner of the property
            $tenant = $contrato->user; // Tenant

            // VALIDACIÓN #1: Ensure both have wallets
            if (!$landlord->wallet_address) {
                Log::info('Asignando wallet a propietario', ['landlord_id' => $landlord->id]);
                $this->walletService->assignWalletToUser($landlord);
                $landlord->refresh();
            }

            if (!$tenant->wallet_address) {
                Log::info('Asignando wallet a inquilino', ['tenant_id' => $tenant->id]);
                $this->walletService->assignWalletToUser($tenant);
                $tenant->refresh();
            }
            Log::info('Validación #1 pasada: Ambos usuarios tienen wallets', [
                'landlord_wallet' => $landlord->wallet_address,
                'tenant_wallet' => $tenant->wallet_address,
            ]);

            // VALIDACIÓN #2: Verify contract doesn't already exist on blockchain
            if ($contrato->blockchain_address) {
                Log::warning('Validación fallida: Contrato ya existe en blockchain', [
                    'contrato_id' => $contrato->id,
                    'blockchain_address_existente' => $contrato->blockchain_address,
                ]);
                return ResponseService::error('El contrato ya existe en blockchain', '', 400);
            }
            Log::info('Validación #2 pasada: Contrato no existe en blockchain');

            // ========================================
            // PASO 2: INICIAR TRANSACCIÓN ATÓMICA
            // ========================================
            Log::info('Iniciando transacción atómica de base de datos', [
                'contrato_id' => $contrato->id,
            ]);
            \DB::beginTransaction();

            // ========================================
            // PASO 3: CREAR CONTRATO EN BLOCKCHAIN
            // ========================================
            Log::info('Llamando a RentalContractService para crear contrato en blockchain', [
                'contrato_id' => $contrato->id,
                'landlord_wallet' => $landlord->wallet_address,
                'tenant_wallet' => $tenant->wallet_address,
                'monto' => $contrato->monto,
            ]);

            $result = $this->rentalContractService->createRentalContract($contrato, $landlord, $tenant);

            if (!$result['success']) {
                Log::error('Creación de contrato blockchain falló, realizando rollback', [
                    'contrato_id' => $contrato->id,
                    'error' => $result['error'],
                ]);
                \DB::rollBack();
                return ResponseService::error('Error al crear contrato en blockchain', $result['error'], 500);
            }

            // Guardar tx_hash para manejo de errores críticos
            $txHash = $result['tx_hash'];
            Log::info('Contrato blockchain creado exitosamente', [
                'tx_hash' => $txHash,
                'block_number' => $result['block_number'],
            ]);

            // ========================================
            // PASO 4: GUARDAR EN BASE DE DATOS
            // ========================================
            Log::info('Guardando blockchain_address en contrato', [
                'contrato_id' => $contrato->id,
                'tx_hash' => $txHash,
            ]);

            // Update contrato with blockchain address (tx hash)
            $contrato->blockchain_address = $txHash;
            $contrato->save();

            // ========================================
            // PASO 5: COMMIT SI TODO SALIÓ BIEN
            // ========================================
            Log::info('Realizando commit de transacción DB', [
                'contrato_id' => $contrato->id,
            ]);
            \DB::commit();

            Log::info('Contrato blockchain creado exitosamente', [
                'contrato_id' => $contrato->id,
                'tx_hash' => $txHash,
                'block_number' => $result['block_number'],
            ]);

            return ResponseService::success('Contrato creado en blockchain exitosamente', [
                'contrato_id' => $contrato->id,
                'tx_hash' => $txHash,
                'block_number' => $result['block_number'],
                'landlord_wallet' => $landlord->wallet_address,
                'tenant_wallet' => $tenant->wallet_address,
            ]);

        } catch (Exception $e) {
            // ========================================
            // ROLLBACK DE BASE DE DATOS
            // ========================================
            \DB::rollBack();

            // ========================================
            // CASO CRÍTICO: Blockchain exitoso pero BD falló
            // ========================================
            if ($txHash) {
                Log::critical('INCONSISTENCIA CRÍTICA: Contrato blockchain exitoso pero BD falló - REQUIERE RECONCILIACIÓN MANUAL', [
                    'tx_hash' => $txHash,
                    'contrato_id' => $request->contrato_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => now()->toISOString(),
                ]);

                // TODO: Enviar alerta crítica al equipo técnico
                // Mail::to('admin@example.com')->send(new BlockchainInconsistencyAlert($txHash, $request->contrato_id));

                return ResponseService::error(
                    'Contrato creado en blockchain pero error al guardar en base de datos. Contacte soporte con este código: ' . $txHash,
                    '',
                    500
                );
            }

            // Error antes del blockchain - todo bien, solo loguear
            Log::error('Error al crear contrato blockchain', [
                'contrato_id' => $request->contrato_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseService::error('Error al crear el contrato', $e->getMessage(), 500);
        }
    }

    /**
     * Approve contract (by tenant)
     * POST /api/app/blockchain/contract/approve
     *
     * Request body:
     * {
     *   "contrato_id": 1,
     *   "user_id": 2
     * }
     */
    public function approveContract(Request $request)
    {
        $request->validate([
            'contrato_id' => 'required|integer|exists:contratos,id',
            'user_id' => 'required|integer|exists:users,id'
        ]);

        // ========================================
        // LOG DE AUDITORÍA - SOLICITUD RECIBIDA
        // ========================================
        Log::info('Solicitud de aprobación de contrato blockchain recibida', [
            'contrato_id' => $request->contrato_id,
            'user_id' => $request->user_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        $txHash = null; // Track if blockchain approval was made

        try {
            $contrato = Contrato::findOrFail($request->contrato_id);
            $user = User::findOrFail($request->user_id);

            // ========================================
            // PASO 1: VALIDAR TODO ANTES DE BLOCKCHAIN
            // ========================================
            Log::info('Iniciando validaciones de aprobación de contrato', [
                'contrato_id' => $contrato->id,
                'user_id' => $user->id,
            ]);

            // VALIDACIÓN #1: Verify user is the tenant
            if ($contrato->user_id !== $user->id) {
                Log::warning('Validación fallida: Usuario no autorizado para aprobar', [
                    'contrato_id' => $contrato->id,
                    'tenant_real' => $contrato->user_id,
                    'user_intentando' => $user->id,
                    'ip' => $request->ip(),
                ]);
                return ResponseService::error('Solo el inquilino puede aprobar el contrato', '', 403);
            }
            Log::info('Validación #1 pasada: Usuario es el inquilino del contrato');

            // VALIDACIÓN #2: Verify user has wallet
            if (!$user->wallet_address) {
                Log::warning('Validación fallida: Usuario no tiene wallet', [
                    'user_id' => $user->id,
                    'contrato_id' => $contrato->id,
                ]);
                return ResponseService::error('El usuario no tiene una dirección de billetera', '', 400);
            }
            Log::info('Validación #2 pasada: Usuario tiene wallet', ['wallet' => $user->wallet_address]);

            // VALIDACIÓN #3: Verify contract is not already approved
            if ($contrato->cliente_aprobado) {
                Log::warning('Validación fallida: Contrato ya está aprobado', [
                    'contrato_id' => $contrato->id,
                    'cliente_aprobado' => $contrato->cliente_aprobado,
                ]);
                return ResponseService::error('El contrato ya ha sido aprobado', '', 400);
            }
            Log::info('Validación #3 pasada: Contrato no está aprobado previamente');

            // VALIDACIÓN #4: Verify contract exists on blockchain
            if (!$contrato->blockchain_address) {
                Log::warning('Validación fallida: Contrato no existe en blockchain', [
                    'contrato_id' => $contrato->id,
                ]);
                return ResponseService::error('El contrato no existe en blockchain', '', 400);
            }
            Log::info('Validación #4 pasada: Contrato existe en blockchain', [
                'blockchain_address' => $contrato->blockchain_address,
            ]);

            // ========================================
            // PASO 2: INICIAR TRANSACCIÓN ATÓMICA
            // ========================================
            Log::info('Iniciando transacción atómica de base de datos', [
                'contrato_id' => $contrato->id,
            ]);
            \DB::beginTransaction();

            // ========================================
            // PASO 3: APROBAR CONTRATO EN BLOCKCHAIN
            // ========================================
            Log::info('Llamando a RentalContractService para aprobar contrato en blockchain', [
                'contrato_id' => $contrato->id,
                'user_wallet' => $user->wallet_address,
            ]);

            $result = $this->rentalContractService->approveContract($contrato->id, $user);

            if (!$result['success']) {
                Log::error('Aprobación blockchain falló, realizando rollback', [
                    'contrato_id' => $contrato->id,
                    'error' => $result['error'],
                ]);
                \DB::rollBack();
                return ResponseService::error('Error al aprobar el contrato', $result['error'], 500);
            }

            // Guardar tx_hash para manejo de errores críticos
            $txHash = $result['tx_hash'];
            Log::info('Aprobación blockchain exitosa', [
                'tx_hash' => $txHash,
            ]);

            // ========================================
            // PASO 4: GUARDAR EN BASE DE DATOS
            // ========================================
            Log::info('Actualizando estado del contrato en base de datos', [
                'contrato_id' => $contrato->id,
            ]);

            // Update contract status
            $estadoAnterior = $contrato->estado;
            $contrato->cliente_aprobado = true;
            $contrato->estado = 'aprobado';
            $contrato->save();

            Log::info('Estado del contrato actualizado', [
                'contrato_id' => $contrato->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'aprobado',
                'cliente_aprobado' => true,
            ]);

            // ========================================
            // PASO 5: COMMIT SI TODO SALIÓ BIEN
            // ========================================
            Log::info('Realizando commit de transacción DB', [
                'contrato_id' => $contrato->id,
            ]);
            \DB::commit();

            Log::info('Contrato aprobado exitosamente', [
                'contrato_id' => $contrato->id,
                'user_id' => $user->id,
                'tx_hash' => $txHash,
            ]);

            return ResponseService::success('Contrato aprobado exitosamente', [
                'contrato_id' => $contrato->id,
                'tx_hash' => $txHash,
            ]);

        } catch (Exception $e) {
            // ========================================
            // ROLLBACK DE BASE DE DATOS
            // ========================================
            \DB::rollBack();

            // ========================================
            // CASO CRÍTICO: Blockchain exitoso pero BD falló
            // ========================================
            if ($txHash) {
                Log::critical('INCONSISTENCIA CRÍTICA: Aprobación blockchain exitosa pero BD falló - REQUIERE RECONCILIACIÓN MANUAL', [
                    'tx_hash' => $txHash,
                    'contrato_id' => $request->contrato_id,
                    'user_id' => $request->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => now()->toISOString(),
                ]);

                // TODO: Enviar alerta crítica al equipo técnico
                // Mail::to('admin@example.com')->send(new BlockchainInconsistencyAlert($txHash, $request->contrato_id));

                return ResponseService::error(
                    'Contrato aprobado en blockchain pero error al guardar en base de datos. Contacte soporte con este código: ' . $txHash,
                    '',
                    500
                );
            }

            // Error antes del blockchain - todo bien, solo loguear
            Log::error('Error al aprobar el contrato', [
                'contrato_id' => $request->contrato_id ?? null,
                'user_id' => $request->user_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseService::error('Error al aprobar el contrato', $e->getMessage(), 500);
        }
    }

    /**
     * Make payment for rental contract
     * POST /api/app/blockchain/payment/create
     *
     * Request body:
     * {
     *   "contrato_id": 1,
     *   "user_id": 2,
     *   "amount": 500.0
     * }
     */
    public function makePayment(Request $request)
    {
        $request->validate([
            'contrato_id' => 'required|integer|exists:contratos,id',
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        // ========================================
        // LOG DE AUDITORÍA - SOLICITUD RECIBIDA
        // ========================================
        Log::info('Solicitud de pago blockchain recibida', [
            'user_id' => $request->user_id,
            'contrato_id' => $request->contrato_id,
            'amount' => $request->amount,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        $txHash = null; // Track if blockchain payment was made

        try {
            $contrato = Contrato::findOrFail($request->contrato_id);
            $user = User::findOrFail($request->user_id);

            // ========================================
            // PASO 1: VALIDAR TODO ANTES DE BLOCKCHAIN
            // ========================================
            Log::info('Iniciando validaciones de pago', [
                'contrato_id' => $contrato->id,
                'user_id' => $user->id,
            ]);

            // VALIDACIÓN #1: Verificar que el usuario sea el cliente del contrato
            if ($contrato->user_id !== $user->id) {
                Log::warning('Validación fallida: Usuario no autorizado para pagar', [
                    'contrato_id' => $contrato->id,
                    'tenant_real' => $contrato->user_id,
                    'user_intentando' => $user->id,
                    'ip' => $request->ip(),
                ]);
                return ResponseService::error('Solo el inquilino puede realizar pagos', '', 403);
            }
            Log::info('Validación #1 pasada: Usuario es el inquilino del contrato');

            // VALIDACIÓN #2: Verificar que el contrato esté en estado 'aprobado' o 'activo'
            if ($contrato->estado !== 'aprobado' && $contrato->estado !== 'activo') {
                Log::warning('Validación fallida: Contrato no está en estado válido para pagos', [
                    'contrato_id' => $contrato->id,
                    'estado_actual' => $contrato->estado,
                    'estados_requeridos' => ['aprobado', 'activo'],
                ]);
                return ResponseService::error('El contrato debe estar aprobado antes de realizar el pago', '', 403);
            }
            Log::info('Validación #2 pasada: Contrato está en estado válido', ['estado' => $contrato->estado]);

            // VALIDACIÓN #3: Verificar que el cliente haya aprobado el contrato
            if (!$contrato->cliente_aprobado) {
                Log::warning('Validación fallida: Cliente no ha aprobado el contrato', [
                    'contrato_id' => $contrato->id,
                    'cliente_aprobado' => $contrato->cliente_aprobado,
                ]);
                return ResponseService::error('El inquilino debe aprobar el contrato antes de realizar el pago', '', 403);
            }
            Log::info('Validación #3 pasada: Cliente ha aprobado el contrato');

            // VALIDACIÓN #4: Verificar que no exista pago duplicado para este mes
            $pagoExistente = Pago::where('contrato_id', $request->contrato_id)
                ->where('estado', 'pagado')
                ->whereYear('fecha_pago', now()->year)
                ->whereMonth('fecha_pago', now()->month)
                ->first();

            if ($pagoExistente) {
                Log::warning('Validación fallida: Ya existe pago para este mes', [
                    'contrato_id' => $contrato->id,
                    'pago_existente_id' => $pagoExistente->id,
                    'mes' => now()->month,
                    'año' => now()->year,
                ]);
                return ResponseService::error('Ya existe un pago para este mes', '', 400);
            }
            Log::info('Validación #4 pasada: No hay pagos duplicados para este mes');

            // VALIDACIÓN #5: Verificar que el usuario tenga wallet
            if (!$user->wallet_address) {
                Log::warning('Validación fallida: Usuario no tiene wallet', [
                    'user_id' => $user->id,
                    'contrato_id' => $contrato->id,
                ]);
                return ResponseService::error('El usuario no tiene una dirección de billetera', '', 400);
            }
            Log::info('Validación #5 pasada: Usuario tiene wallet', ['wallet' => $user->wallet_address]);

            // ========================================
            // PASO 2: INICIAR TRANSACCIÓN ATÓMICA
            // ========================================
            Log::info('Iniciando transacción atómica de base de datos', [
                'contrato_id' => $contrato->id,
            ]);
            \DB::beginTransaction();

            // ========================================
            // PASO 3: REALIZAR PAGO EN BLOCKCHAIN
            // ========================================
            Log::info('Llamando a RentalContractService para procesar pago en blockchain', [
                'contrato_id' => $contrato->id,
                'amount' => $request->amount,
                'tenant_wallet' => $user->wallet_address,
            ]);

            $result = $this->rentalContractService->makePayment(
                $contrato->id,
                $request->amount,
                $user
            );

            if (!$result['success']) {
                Log::error('Pago blockchain falló, realizando rollback', [
                    'contrato_id' => $contrato->id,
                    'error' => $result['error'],
                ]);
                \DB::rollBack();
                return ResponseService::error('Error al procesar el pago', $result['error'], 500);
            }

            // Guardar tx_hash para manejo de errores críticos
            $txHash = $result['tx_hash'];
            Log::info('Pago blockchain exitoso', [
                'tx_hash' => $txHash,
                'block_number' => $result['block_number'] ?? null,
            ]);

            // ========================================
            // PASO 4: GUARDAR EN BASE DE DATOS
            // ========================================
            Log::info('Guardando registro de pago en base de datos', [
                'contrato_id' => $contrato->id,
                'monto' => $request->amount,
                'tx_hash' => $txHash,
            ]);

            // Crear registro de pago
            $pago = Pago::create([
                'contrato_id' => $contrato->id,
                'monto' => $request->amount,
                'fecha_pago' => now(),
                'estado' => 'pagado',
                'blockchain_id' => $txHash,
                'historial_acciones' => json_encode([
                    [
                        'action' => 'payment_created',
                        'timestamp' => now()->toISOString(),
                        'tx_hash' => $txHash,
                        'block_number' => $result['block_number'] ?? null,
                    ]
                ])
            ]);

            Log::info('Registro de pago creado', ['pago_id' => $pago->id]);

            // Actualizar estado del contrato
            if ($contrato->estado === 'aprobado') {
                $estadoAnterior = $contrato->estado;
                $contrato->estado = 'activo';
                $contrato->fecha_pago = now();
                $contrato->save();

                Log::info('Estado de contrato actualizado', [
                    'contrato_id' => $contrato->id,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => 'activo',
                    'fecha_pago' => $contrato->fecha_pago,
                ]);

                // Marcar inmueble como ocupado
                $contrato->inmueble->isOcupado = true;
                $contrato->inmueble->save();

                Log::info('Inmueble marcado como ocupado', [
                    'inmueble_id' => $contrato->inmueble->id,
                    'nombre' => $contrato->inmueble->nombre,
                ]);
            }

            // ========================================
            // PASO 5: COMMIT SI TODO SALIÓ BIEN
            // ========================================
            Log::info('Realizando commit de transacción DB', [
                'contrato_id' => $contrato->id,
                'pago_id' => $pago->id,
            ]);
            \DB::commit();

            Log::info('Pago blockchain procesado exitosamente', [
                'contrato_id' => $contrato->id,
                'pago_id' => $pago->id,
                'tx_hash' => $txHash,
                'monto' => $request->amount,
                'nuevo_estado' => $contrato->estado,
            ]);

            return ResponseService::success('Pago procesado exitosamente', [
                'pago_id' => $pago->id,
                'contrato_id' => $contrato->id,
                'amount' => $request->amount,
                'tx_hash' => $txHash,
                'block_number' => $result['block_number'] ?? null,
                'contrato_estado' => $contrato->estado,
            ]);

        } catch (Exception $e) {
            // ========================================
            // ROLLBACK DE BASE DE DATOS
            // ========================================
            \DB::rollBack();

            // ========================================
            // CASO CRÍTICO: Blockchain exitoso pero BD falló
            // ========================================
            if ($txHash) {
                Log::critical('INCONSISTENCIA CRÍTICA: Pago blockchain exitoso pero BD falló - REQUIERE RECONCILIACIÓN MANUAL', [
                    'tx_hash' => $txHash,
                    'contrato_id' => $request->contrato_id,
                    'user_id' => $request->user_id,
                    'monto' => $request->amount,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => now()->toISOString(),
                ]);

                // TODO: Enviar alerta crítica al equipo técnico
                // Mail::to('admin@example.com')->send(new BlockchainInconsistencyAlert($txHash, $request->contrato_id));

                return ResponseService::error(
                    'Pago procesado en blockchain pero error al guardar en base de datos. Contacte soporte con este código: ' . $txHash,
                    '',
                    500
                );
            }

            // Error antes del blockchain - todo bien, solo loguear
            Log::error('Error al procesar pago blockchain', [
                'contrato_id' => $request->contrato_id ?? null,
                'user_id' => $request->user_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseService::error('Error al procesar el pago', $e->getMessage(), 500);
        }
    }

    /**
     * Get contract details from blockchain
     * GET /api/app/blockchain/contract/{contractId}
     */
    public function getContractDetails($contractId)
    {
        try {
            $details = $this->rentalContractService->getContractDetails($contractId);

            if (!$details) {
                return ResponseService::error('Contrato no encontrado en blockchain', '', 404);
            }

            return ResponseService::success('Contract details retrieved', $details);

        } catch (Exception $e) {
            return ResponseService::error('Failed to get contract details', $e->getMessage(), 500);
        }
    }

    /**
     * Terminate contract
     * POST /api/app/blockchain/contract/terminate
     *
     * Request body:
     * {
     *   "contrato_id": 1,
     *   "user_id": 2,
     *   "reason": "Breach of contract"
     * }
     */
    public function terminateContract(Request $request)
    {
        $request->validate([
            'contrato_id' => 'required|integer|exists:contratos,id',
            'user_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string'
        ]);

        try {
            $contrato = Contrato::with('inmueble.user')->findOrFail($request->contrato_id);
            $user = User::findOrFail($request->user_id);

            // Verify user is landlord or tenant
            $isLandlord = $contrato->inmueble->user_id === $user->id;
            $isTenant = $contrato->user_id === $user->id;

            if (!$isLandlord && !$isTenant) {
                return ResponseService::error('Solo el propietario o inquilino pueden terminar el contrato', '', 403);
            }

            if (!$user->wallet_address) {
                return ResponseService::error('User does not have a wallet address', '', 400);
            }

            $result = $this->rentalContractService->terminateContract(
                $contrato->id,
                $request->reason,
                $user->wallet_address
            );

            if (!$result['success']) {
                return ResponseService::error('Error al terminar el contrato', $result['error'], 500);
            }

            // Update contract status
            $contrato->estado = 'cancelado';
            $contrato->save();

            // Liberar inmueble
            $contrato->inmueble->isOcupado = false;
            $contrato->inmueble->save();

            Log::info('Inmueble liberado (disponible nuevamente)', [
                'inmueble_id' => $contrato->inmueble->id,
                'nombre' => $contrato->inmueble->nombre,
            ]);

            return ResponseService::success('Contrato terminado exitosamente', [
                'contrato_id' => $contrato->id,
                'tx_hash' => $result['tx_hash'],
                'reason' => $request->reason,
            ]);

        } catch (Exception $e) {
            return ResponseService::error('Error al terminar el contrato', $e->getMessage(), 500);
        }
    }

    /**
     * Check contract expiration
     * POST /api/app/blockchain/contract/check-expiration/{contractId}
     */
    public function checkExpiration($contractId)
    {
        try {
            $result = $this->rentalContractService->checkExpiration($contractId);

            if (!$result['success']) {
                return ResponseService::error('Failed to check expiration', $result['error'], 500);
            }

            return ResponseService::success('Expiration check completed', $result);

        } catch (Exception $e) {
            return ResponseService::error('Failed to check expiration', $e->getMessage(), 500);
        }
    }

    /**
     * Calcular monto de pago para un contrato
     * GET /api/app/contratos/{contratoId}/calcular-monto-pago
     */
    public function calcularMontoPago($contratoId)
    {
        try {
            Log::info('Calculando monto de pago para contrato', [
                'contrato_id' => $contratoId,
            ]);

            // Obtener contrato
            $contrato = Contrato::find($contratoId);

            if (!$contrato) {
                return ResponseService::error('Contrato no encontrado', '', 404);
            }

            // Determinar si es primer pago
            $esPrimerPago = ($contrato->estado === 'aprobado');

            // Calcular montos
            $montoBase = $contrato->monto;
            $requiereDeposito = $esPrimerPago;
            $montoDeposito = $esPrimerPago ? ($montoBase * 0.5) : 0;
            $montoTotal = $montoBase + $montoDeposito;

            $resultado = [
                'monto_base' => $montoBase,
                'requiere_deposito' => $requiereDeposito,
                'monto_deposito' => $montoDeposito,
                'monto_total' => $montoTotal,
                'descripcion' => $esPrimerPago
                    ? 'Primer pago (incluye depósito + renta)'
                    : 'Pago mensual de renta',
                'estado_contrato' => $contrato->estado,
            ];

            Log::info('Monto de pago calculado', [
                'contrato_id' => $contratoId,
                'resultado' => $resultado,
            ]);

            return ResponseService::success('Monto calculado correctamente', $resultado);

        } catch (Exception $e) {
            Log::error('Error al calcular monto de pago', [
                'contrato_id' => $contratoId,
                'error' => $e->getMessage(),
            ]);
            return ResponseService::error('Error al calcular monto', $e->getMessage(), 500);
        }
    }
}
