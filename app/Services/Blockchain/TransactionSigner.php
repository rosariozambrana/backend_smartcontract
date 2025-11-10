<?php

namespace App\Services\Blockchain;

use Web3p\EthereumTx\Transaction;
use Exception;

class TransactionSigner
{
    /**
     * Sign a transaction with a private key
     *
     * @param array $txData Transaction data
     * @param string $privateKey Private key (with or without 0x prefix)
     * @param int $chainId Chain ID
     * @return string Signed transaction in hex format
     */
    public static function signTransaction(array $txData, string $privateKey, int $chainId): string
    {
        // Remove 0x prefix from private key if present
        $privateKey = str_replace('0x', '', $privateKey);

        // Prepare transaction array for ethereum-tx library
        // IMPORTANTE: Los valores DEBEN TENER el prefijo 0x para que la librería los procese correctamente
        $txArray = [
            'nonce' => $txData['nonce'] ?? '0x0',
            'gasPrice' => $txData['gasPrice'] ?? '0x' . dechex(20000000000),
            'gasLimit' => $txData['gas'] ?? '0x' . dechex(3000000),
            'to' => $txData['to'] ?? '',
            'value' => $txData['value'] ?? '0x0',
            'data' => $txData['data'] ?? '0x',
            'chainId' => $chainId, // Chain ID como integer
        ];

        // Create and sign transaction (EIP-155)
        $transaction = new Transaction($txArray);
        $signedTransaction = $transaction->sign($privateKey);

        // DEBUG: Log what type we got
        \Log::info('TransactionSigner debug', [
            'signed_type' => gettype($signedTransaction),
            'signed_class' => is_object($signedTransaction) ? get_class($signedTransaction) : 'not object',
            'signed_preview' => is_string($signedTransaction) ? substr($signedTransaction, 0, 100) : 'not string',
        ]);

        // Ensure we have a string (serialize() returns hex string without 0x prefix)
        $signedHex = (string) $signedTransaction;

        // Return serialized signed transaction with 0x prefix
        return '0x' . $signedHex;
    }
}
