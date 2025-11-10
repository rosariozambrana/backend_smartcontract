<?php

namespace App\Services\Blockchain;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use kornrunner\Keccak;

class WalletService extends BlockchainService
{
    /**
     * Generate a new Ethereum wallet for a user
     * Note: In production, consider using a secure key management system
     */
    public function generateWallet(): array
    {
        try {
            // Generate random private key (32 bytes)
            $privateKey = bin2hex(random_bytes(32));

            // Derive public key and address from private key
            $address = $this->privateKeyToAddress($privateKey);

            Log::info('New wallet generated', [
                'address' => $address
            ]);

            return [
                'success' => true,
                'address' => $address,
                'private_key' => $privateKey, // NEVER expose this to frontend
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate wallet', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Assign a wallet address to a user
     */
    public function assignWalletToUser(User $user): array
    {
        try {
            // Check if user already has a wallet
            if ($user->wallet_address) {
                return [
                    'success' => true,
                    'address' => $user->wallet_address,
                    'message' => 'User already has a wallet'
                ];
            }

            // Generate new wallet
            $wallet = $this->generateWallet();

            if (!$wallet['success']) {
                throw new Exception($wallet['error']);
            }

            // Update user with wallet address and private key (encrypted automatically by Laravel)
            $user->wallet_address = $wallet['address'];
            $user->wallet_private_key = $wallet['private_key'];
            $user->save();

            Log::info('User wallet created and private key stored securely', [
                'user_id' => $user->id,
                'address' => $wallet['address'],
            ]);

            return [
                'success' => true,
                'address' => $wallet['address'],
                'message' => 'Wallet assigned to user successfully'
            ];

        } catch (Exception $e) {
            Log::error('Failed to assign wallet to user', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get balance for a user's wallet
     */
    public function getUserBalance(User $user): array
    {
        try {
            if (!$user->wallet_address) {
                return [
                    'success' => false,
                    'error' => 'User does not have a wallet address'
                ];
            }

            $balanceWei = $this->getBalance($user->wallet_address);
            $balanceEth = $this->weiToEther($balanceWei);

            return [
                'success' => true,
                'address' => $user->wallet_address,
                'balance_wei' => $balanceWei,
                'balance_eth' => $balanceEth
            ];

        } catch (Exception $e) {
            Log::error('Failed to get user balance', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate Ethereum address format
     */
    public function isValidAddress(string $address): bool
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
    }

    /**
     * Convert private key to Ethereum address
     * Simplified version - in production use a proper library
     */
    protected function privateKeyToAddress(string $privateKey): string
    {
        // This is a simplified implementation
        // In production, use web3p/ethereum-util or similar

        // For now, generate a pseudo-address
        // In real implementation, derive from private key using elliptic curve crypto
        $hash = Keccak::hash(hex2bin($privateKey), 256);
        $address = '0x' . substr($hash, -40);

        return $address;
    }

    /**
     * Import existing wallet by private key
     */
    public function importWallet(User $user, string $privateKey): array
    {
        try {
            if (!ctype_xdigit(str_replace('0x', '', $privateKey))) {
                throw new Exception('Invalid private key format');
            }

            $privateKey = str_replace('0x', '', $privateKey);
            $address = $this->privateKeyToAddress($privateKey);

            // Update user with wallet address and private key (encrypted automatically by Laravel)
            $user->wallet_address = $address;
            $user->wallet_private_key = $privateKey;
            $user->save();

            Log::info('Wallet imported for user', [
                'user_id' => $user->id,
                'address' => $address
            ]);

            return [
                'success' => true,
                'address' => $address,
                'message' => 'Wallet imported successfully'
            ];

        } catch (Exception $e) {
            Log::error('Failed to import wallet', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
