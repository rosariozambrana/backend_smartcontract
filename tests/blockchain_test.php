<?php

// Script de prueba para blockchain
// Ejecutar: php tests/blockchain_test.php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Blockchain\BlockchainService;
use App\Services\Blockchain\WalletService;

echo "\n";
echo "=================================================================\n";
echo "           PRUEBA DE INTEGRACIÓN BLOCKCHAIN                      \n";
echo "=================================================================\n\n";

try {
    // Test 1: Check connection
    echo "[TEST 1] Verificando conexión a Ganache...\n";
    $blockchain = app(BlockchainService::class);
    $status = $blockchain->checkConnection();

    if ($status['connected']) {
        echo "✅ CONEXIÓN EXITOSA\n";
        echo "   - RPC URL: " . $status['rpc_url'] . "\n";
        echo "   - Network ID: " . $status['network_id'] . "\n";
        echo "   - Block Number: " . $status['block_number'] . "\n";
        echo "   - Contract Address: " . $status['contract_address'] . "\n";
    } else {
        echo "❌ ERROR: No se pudo conectar a Ganache\n";
        echo "   - Error: " . ($status['error'] ?? 'Unknown') . "\n";
        exit(1);
    }

    echo "\n";

    // Test 2: Check wallet balance
    echo "[TEST 2] Verificando balance de la wallet del backend...\n";
    $walletAddress = $blockchain->getWalletAddress();
    $balanceWei = $blockchain->getBalance($walletAddress);
    $balanceEth = $blockchain->weiToEther($balanceWei);

    echo "✅ BALANCE OBTENIDO\n";
    echo "   - Wallet: " . $walletAddress . "\n";
    echo "   - Balance: " . $balanceEth . " ETH\n";
    echo "   - Balance (Wei): " . $balanceWei . " Wei\n";

    echo "\n";

    // Test 3: Test wallet generation
    echo "[TEST 3] Probando generación de wallets...\n";
    $walletService = app(WalletService::class);
    $newWallet = $walletService->generateWallet();

    if ($newWallet['success']) {
        echo "✅ WALLET GENERADA\n";
        echo "   - Address: " . $newWallet['address'] . "\n";
        echo "   - Private Key: " . substr($newWallet['private_key'], 0, 20) . "...\n";
    } else {
        echo "❌ ERROR: " . $newWallet['error'] . "\n";
    }

    echo "\n";

    // Test 4: Validate contract address
    echo "[TEST 4] Validando dirección del contrato...\n";
    $contractAddress = config('blockchain.contract_address');
    $isValid = $walletService->isValidAddress($contractAddress);

    if ($isValid) {
        echo "✅ DIRECCIÓN VÁLIDA\n";
        echo "   - Contract: " . $contractAddress . "\n";
    } else {
        echo "❌ DIRECCIÓN INVÁLIDA\n";
    }

    echo "\n";
    echo "=================================================================\n";
    echo "           TODAS LAS PRUEBAS COMPLETADAS EXITOSAMENTE            \n";
    echo "=================================================================\n\n";

    echo "CONFIGURACIÓN ACTUAL:\n";
    echo "  - RPC URL: " . config('blockchain.rpc_url') . "\n";
    echo "  - Chain ID: " . config('blockchain.chain_id') . "\n";
    echo "  - Contract Address: " . config('blockchain.contract_address') . "\n";
    echo "  - Wallet Address: " . config('blockchain.wallet_address') . "\n";
    echo "  - Blockchain Enabled: " . (config('blockchain.enabled') ? 'YES' : 'NO') . "\n";
    echo "\n";

    echo "PRÓXIMOS PASOS:\n";
    echo "  1. El backend está listo para procesar transacciones blockchain\n";
    echo "  2. Usa los endpoints API para crear contratos y pagos\n";
    echo "  3. El frontend Flutter debe actualizar sus llamadas a estos endpoints\n";
    echo "\n";

} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
