<?php

namespace App\Jobs;

use App\Models\Contrato;
use App\Models\Pago;
use App\Services\Blockchain\RentalContractService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessBlockchainTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionType;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(string $transactionType, array $data)
    {
        $this->transactionType = $transactionType;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(RentalContractService $rentalContractService): void
    {
        try {
            Log::info('Processing blockchain transaction', [
                'type' => $this->transactionType,
                'data' => $this->data
            ]);

            switch ($this->transactionType) {
                case 'create_contract':
                    $this->handleCreateContract($rentalContractService);
                    break;

                case 'approve_contract':
                    $this->handleApproveContract($rentalContractService);
                    break;

                case 'make_payment':
                    $this->handleMakePayment($rentalContractService);
                    break;

                case 'terminate_contract':
                    $this->handleTerminateContract($rentalContractService);
                    break;

                default:
                    Log::warning('Unknown transaction type', ['type' => $this->transactionType]);
            }

        } catch (Exception $e) {
            Log::error('Blockchain transaction job failed', [
                'type' => $this->transactionType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle create contract transaction
     */
    protected function handleCreateContract(RentalContractService $service): void
    {
        $contrato = Contrato::with(['user', 'inmueble.user'])->find($this->data['contrato_id']);

        if (!$contrato) {
            Log::error('Contract not found', ['contrato_id' => $this->data['contrato_id']]);
            return;
        }

        $landlord = $contrato->inmueble->user;
        $tenant = $contrato->user;

        $result = $service->createRentalContract($contrato, $landlord, $tenant);

        if ($result['success']) {
            $contrato->blockchain_address = $result['tx_hash'];
            $contrato->save();

            Log::info('Contract created on blockchain via job', [
                'contrato_id' => $contrato->id,
                'tx_hash' => $result['tx_hash']
            ]);
        } else {
            Log::error('Failed to create contract on blockchain', [
                'contrato_id' => $contrato->id,
                'error' => $result['error']
            ]);
        }
    }

    /**
     * Handle approve contract transaction
     */
    protected function handleApproveContract(RentalContractService $service): void
    {
        $contratoId = $this->data['contrato_id'];
        $walletAddress = $this->data['wallet_address'];

        $result = $service->approveContract($contratoId, $walletAddress);

        if ($result['success']) {
            $contrato = Contrato::find($contratoId);
            if ($contrato) {
                $contrato->cliente_aprobado = true;
                $contrato->estado = 'aprobado';
                $contrato->save();
            }

            Log::info('Contract approved on blockchain via job', [
                'contrato_id' => $contratoId,
                'tx_hash' => $result['tx_hash']
            ]);
        }
    }

    /**
     * Handle make payment transaction
     */
    protected function handleMakePayment(RentalContractService $service): void
    {
        $contratoId = $this->data['contrato_id'];
        $amount = $this->data['amount'];
        $walletAddress = $this->data['wallet_address'];

        $result = $service->makePayment($contratoId, $amount, $walletAddress);

        if ($result['success']) {
            // Create payment record
            Pago::create([
                'contrato_id' => $contratoId,
                'monto' => $amount,
                'fecha_pago' => now(),
                'estado' => 'pagado',
                'blockchain_id' => $result['tx_hash'],
                'historial_acciones' => json_encode([
                    [
                        'action' => 'payment_created_via_job',
                        'timestamp' => now()->toISOString(),
                        'tx_hash' => $result['tx_hash'],
                    ]
                ])
            ]);

            Log::info('Payment processed on blockchain via job', [
                'contrato_id' => $contratoId,
                'amount' => $amount,
                'tx_hash' => $result['tx_hash']
            ]);
        }
    }

    /**
     * Handle terminate contract transaction
     */
    protected function handleTerminateContract(RentalContractService $service): void
    {
        $contratoId = $this->data['contrato_id'];
        $reason = $this->data['reason'];
        $walletAddress = $this->data['wallet_address'];

        $result = $service->terminateContract($contratoId, $reason, $walletAddress);

        if ($result['success']) {
            $contrato = Contrato::find($contratoId);
            if ($contrato) {
                $contrato->estado = 'cancelado';
                $contrato->save();
            }

            Log::info('Contract terminated on blockchain via job', [
                'contrato_id' => $contratoId,
                'tx_hash' => $result['tx_hash']
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Blockchain transaction job failed permanently', [
            'type' => $this->transactionType,
            'data' => $this->data,
            'error' => $exception->getMessage()
        ]);

        // Notify administrators or update database status
    }
}
