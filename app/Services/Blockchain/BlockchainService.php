<?php

namespace App\Services\Blockchain;

use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Exception;
use Illuminate\Support\Facades\Log;

class BlockchainService
{
    protected $web3;
    protected $contract;
    protected $contractAddress;
    protected $contractAbi;
    protected $walletAddress;
    protected $privateKey;
    protected $chainId;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialize Web3 connection and contract
     */
    protected function initialize()
    {
        try {
            $rpcUrl = config('blockchain.rpc_url');
            $this->contractAddress = config('blockchain.contract_address');
            $this->walletAddress = config('blockchain.wallet_address');
            $this->privateKey = config('blockchain.wallet_private_key');
            $this->chainId = config('blockchain.chain_id');

            // Load contract ABI
            $abiPath = storage_path('contracts/RentalContract.json');
            if (!file_exists($abiPath)) {
                throw new Exception("Contract ABI file not found at: {$abiPath}");
            }

            $contractData = json_decode(file_get_contents($abiPath), true);
            $this->contractAbi = $contractData['abi'];

            // Initialize Web3 with HTTP provider and timeout
            $requestManager = new HttpRequestManager($rpcUrl, 30); // 30 seconds timeout
            $provider = new HttpProvider($requestManager);
            $this->web3 = new Web3($provider);

            // Increase max listeners to prevent memory leak warnings
            // This is safe since we're using singleton pattern
            if (method_exists($this->web3->provider, 'setMaxListeners')) {
                $this->web3->provider->setMaxListeners(20);
            }

            // Initialize Contract with same provider
            $this->contract = new Contract($provider, $this->contractAbi);
            $this->contract->at($this->contractAddress);

            Log::info('Blockchain service initialized successfully', [
                'rpc_url' => $rpcUrl,
                'contract_address' => $this->contractAddress,
                'wallet_address' => $this->walletAddress,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to initialize Blockchain service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Check connection to Ganache/Ethereum node
     */
    public function checkConnection(): array
    {
        try {
            $isConnected = false;
            $networkVersion = null;
            $blockNumber = null;

            $this->web3->net->version(function ($err, $version) use (&$networkVersion) {
                if (!$err) {
                    $networkVersion = $version;
                }
            });

            $this->web3->eth->blockNumber(function ($err, $number) use (&$blockNumber) {
                if (!$err) {
                    $blockNumber = $number->toString();
                }
            });

            $isConnected = ($networkVersion !== null && $blockNumber !== null);

            return [
                'connected' => $isConnected,
                'network_id' => $networkVersion,
                'block_number' => $blockNumber,
                'rpc_url' => config('blockchain.rpc_url'),
                'contract_address' => $this->contractAddress,
            ];

        } catch (Exception $e) {
            Log::error('Blockchain connection check failed', ['error' => $e->getMessage()]);
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get wallet balance
     */
    public function getBalance(string $address = null): string
    {
        $address = $address ?? $this->walletAddress;
        $balance = null;

        $this->web3->eth->getBalance($address, function ($err, $bal) use (&$balance) {
            if (!$err) {
                $balance = $bal->toString();
            }
        });

        return $balance ?? '0';
    }

    /**
     * Convert Wei to Ether
     */
    public function weiToEther(string $wei): string
    {
        $result = Utils::fromWei($wei, 'ether');
        return is_array($result) ? $result[0] : $result;
    }

    /**
     * Convert Ether to Wei
     */
    public function etherToWei(float $ether): string
    {
        $result = Utils::toWei((string)$ether, 'ether');
        return is_array($result) ? $result[0] : $result;
    }

    /**
     * Get contract details from blockchain
     */
    public function getContractDetails(int $contractId): ?array
    {
        try {
            $details = null;

            $this->contract->call('getContractDetails', $contractId, function ($err, $result) use (&$details) {
                if (!$err && $result) {
                    $details = $result;
                }
            });

            if (!$details) {
                return null;
            }

            return [
                'landlord' => $details['landlord'],
                'tenant' => $details['tenant'],
                'property_id' => $details['propertyId']->toString(),
                'rent_amount' => $this->weiToEther($details['rentAmount']->toString()),
                'deposit_amount' => $this->weiToEther($details['depositAmount']->toString()),
                'start_date' => $details['startDate']->toString(),
                'end_date' => $details['endDate']->toString(),
                'last_payment_date' => $details['lastPaymentDate']->toString(),
                'state' => $this->getContractState($details['state']),
                'terms_hash' => $details['termsHash'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to get contract details', [
                'contract_id' => $contractId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Convert contract state number to string
     */
    protected function getContractState($state): string
    {
        $states = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Active',
            3 => 'Terminated',
            4 => 'Expired'
        ];

        $stateValue = is_object($state) ? $state->toString() : $state;
        return $states[$stateValue] ?? 'Unknown';
    }

    /**
     * Get gas price from network
     */
    protected function getGasPrice(): string
    {
        $gasPrice = null;

        $this->web3->eth->gasPrice(function ($err, $price) use (&$gasPrice) {
            if (!$err) {
                $gasPrice = $price->toString();
            }
        });

        return $gasPrice ?? '20000000000'; // 20 Gwei default
    }

    /**
     * Get current nonce for wallet
     */
    protected function getNonce(string $address = null): int
    {
        $address = $address ?? $this->walletAddress;
        $nonce = null;

        $this->web3->eth->getTransactionCount($address, 'pending', function ($err, $count) use (&$nonce) {
            if (!$err) {
                $nonce = intval($count->toString());
            }
        });

        return $nonce ?? 0;
    }

    /**
     * Send a transaction (used by other methods)
     */
    protected function sendTransaction(array $txData): ?string
    {
        try {
            $txHash = null;

            // Sign and send transaction
            $this->web3->eth->sendTransaction($txData, function ($err, $hash) use (&$txHash) {
                if (!$err) {
                    $txHash = $hash;
                }
            });

            if ($txHash) {
                Log::info('Transaction sent successfully', [
                    'tx_hash' => $txHash,
                    'from' => $txData['from'] ?? null,
                    'to' => $txData['to'] ?? null,
                ]);
            }

            return $txHash;

        } catch (Exception $e) {
            Log::error('Failed to send transaction', [
                'error' => $e->getMessage(),
                'tx_data' => $txData
            ]);
            throw $e;
        }
    }

    /**
     * Send a raw signed transaction
     */
    protected function sendRawTransaction(string $signedTx): ?string
    {
        try {
            $txHash = null;

            Log::info('Sending raw transaction', [
                'signed_tx' => substr($signedTx, 0, 20) . '...'
            ]);

            $this->web3->eth->sendRawTransaction($signedTx, function ($err, $hash) use (&$txHash) {
                if (!$err) {
                    $txHash = $hash;
                } else {
                    Log::error('sendRawTransaction error', ['error' => $err]);
                }
            });

            if ($txHash) {
                Log::info('Raw transaction sent successfully', [
                    'tx_hash' => $txHash,
                ]);
            }

            return $txHash;

        } catch (Exception $e) {
            Log::error('Failed to send raw transaction', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Wait for transaction receipt
     */
    public function waitForReceipt(string $txHash, int $maxWait = 60): ?array
    {
        $startTime = time();
        $receipt = null;

        while ((time() - $startTime) < $maxWait) {
            $this->web3->eth->getTransactionReceipt($txHash, function ($err, $rec) use (&$receipt) {
                if (!$err && $rec) {
                    $receipt = $rec;
                }
            });

            if ($receipt) {
                // Handle both BigNumber objects and strings
                $blockNumber = is_object($receipt->blockNumber) ? $receipt->blockNumber->toString() : $receipt->blockNumber;
                $gasUsed = is_object($receipt->gasUsed) ? $receipt->gasUsed->toString() : $receipt->gasUsed;

                return [
                    'tx_hash' => $txHash,
                    'block_number' => $blockNumber,
                    'gas_used' => $gasUsed,
                    'status' => $receipt->status === '0x1' ? 'success' : 'failed',
                ];
            }

            sleep(2); // Wait 2 seconds before retry
        }

        return null;
    }

    /**
     * Get Web3 instance
     */
    public function getWeb3(): Web3
    {
        return $this->web3;
    }

    /**
     * Get Contract instance
     */
    public function getContract(): Contract
    {
        return $this->contract;
    }

    /**
     * Get wallet address
     */
    public function getWalletAddress(): string
    {
        return $this->walletAddress;
    }
}
