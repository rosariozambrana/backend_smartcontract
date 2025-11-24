<?php

/**
 * Script de verificación completa de Ganache
 *
 * Verifica:
 * 1. Conexión a Ganache
 * 2. Wallets y balances
 * 3. Contract deployado
 * 4. Configuración correcta
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Services\Blockchain\BlockchainService;
use App\Services\Blockchain\WalletService;

// Cargar Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     VERIFICACIÓN COMPLETA DE GANACHE                      ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Colores para terminal
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$RESET = "\033[0m";

function printSuccess($message) {
    global $GREEN, $RESET;
    echo "{$GREEN}✓ {$message}{$RESET}\n";
}

function printError($message) {
    global $RED, $RESET;
    echo "{$RED}✗ {$message}{$RESET}\n";
}

function printWarning($message) {
    global $YELLOW, $RESET;
    echo "{$YELLOW}⚠ {$message}{$RESET}\n";
}

function printInfo($message) {
    global $BLUE, $RESET;
    echo "{$BLUE}ℹ {$message}{$RESET}\n";
}

// ====================================================================
// 1. VERIFICAR CONFIGURACIÓN
// ====================================================================
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  1. VERIFICANDO CONFIGURACIÓN\n";
echo "═══════════════════════════════════════════════════════════\n";

$rpcUrl = config('blockchain.rpc_url');
$chainId = config('blockchain.chain_id');
$contractAddress = config('blockchain.contract_address');
$walletAddress = config('blockchain.wallet_address');
$privateKey = config('blockchain.wallet_private_key');
$mode = config('blockchain.mode');

printInfo("RPC URL: {$rpcUrl}");
printInfo("Chain ID: {$chainId}");
printInfo("Contract Address: {$contractAddress}");
printInfo("Wallet Address: {$walletAddress}");
printInfo("Private Key: " . substr($privateKey, 0, 10) . "...");
printInfo("Mode: {$mode}");

if (!$contractAddress) {
    printError("BLOCKCHAIN_CONTRACT_ADDRESS no está configurado en .env");
    exit(1);
}

if (!$walletAddress) {
    printError("BLOCKCHAIN_WALLET_ADDRESS no está configurado en .env");
    exit(1);
}

if (!$privateKey) {
    printError("BLOCKCHAIN_WALLET_PRIVATE_KEY no está configurado en .env");
    exit(1);
}

printSuccess("Configuración cargada correctamente");

// ====================================================================
// 2. VERIFICAR CONEXIÓN A GANACHE
// ====================================================================
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  2. VERIFICANDO CONEXIÓN A GANACHE\n";
echo "═══════════════════════════════════════════════════════════\n";

try {
    $blockchainService = app(BlockchainService::class);
    $status = $blockchainService->checkConnection();

    if ($status['connected']) {
        printSuccess("Conexión exitosa a Ganache");
        printInfo("Network ID: {$status['network_id']}");
        printInfo("Bloque actual: {$status['block_number']}");
    } else {
        printError("No se pudo conectar a Ganache");
        printWarning("¿Está Ganache corriendo en {$rpcUrl}?");
        exit(1);
    }
} catch (Exception $e) {
    printError("Error al conectar: " . $e->getMessage());
    exit(1);
}

// ====================================================================
// 3. VERIFICAR WALLETS Y BALANCES
// ====================================================================
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  3. VERIFICANDO WALLETS Y BALANCES\n";
echo "═══════════════════════════════════════════════════════════\n";

try {
    $walletService = app(WalletService::class);

    // Verificar wallet principal del backend
    printInfo("Verificando wallet principal del backend...");
    $balanceWei = $blockchainService->getBalance($walletAddress);
    $balanceEth = $blockchainService->weiToEther($balanceWei);

    printSuccess("Wallet: {$walletAddress}");
    printInfo("Balance: {$balanceEth} ETH");

    if ($balanceEth < 1) {
        printWarning("Balance bajo. Considera usar una wallet con más fondos.");
    }

    // Mostrar todas las wallets disponibles de Ganache
    echo "\n";
    printInfo("Wallets disponibles en Ganache:");
    $ganacheWallets = config('blockchain.ganache_wallets');

    foreach (array_slice($ganacheWallets, 0, 5) as $index => $wallet) {
        $balance = $blockchainService->getBalance($wallet['address']);
        $balanceEth = $blockchainService->weiToEther($balance);
        echo "  [{$index}] {$wallet['address']} - {$balanceEth} ETH\n";
    }

} catch (Exception $e) {
    printError("Error al verificar wallets: " . $e->getMessage());
}

// ====================================================================
// 4. VERIFICAR CONTRATO DESPLEGADO
// ====================================================================
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  4. VERIFICANDO SMART CONTRACT\n";
echo "═══════════════════════════════════════════════════════════\n";

try {
    // Verificar si existe código en la dirección del contrato
    $web3 = $blockchainService->getWeb3();
    $code = null;

    $web3->eth->getCode($contractAddress, function ($err, $result) use (&$code) {
        if (!$err) {
            $code = $result;
        }
    });

    if ($code && $code !== '0x' && $code !== '0x0') {
        printSuccess("Smart contract encontrado en {$contractAddress}");
        printInfo("Tamaño del bytecode: " . strlen($code) . " caracteres");

        // Verificar ABI
        $abiPath = storage_path('contracts/RentalContract.json');
        if (file_exists($abiPath)) {
            printSuccess("ABI encontrado en {$abiPath}");
        } else {
            printError("ABI no encontrado en {$abiPath}");
        }

    } else {
        printError("NO HAY CÓDIGO EN LA DIRECCIÓN {$contractAddress}");
        printWarning("El contrato NO está desplegado o la dirección es incorrecta");
        echo "\n";
        printInfo("Pasos para solucionar:");
        echo "  1. Abre Ganache en http://192.168.180.149:7545\n";
        echo "  2. Ve a la pestaña 'Contracts'\n";
        echo "  3. Si no ves 'RentalContract', despliega con:\n";
        echo "     cd blockchain-deployment\n";
        echo "     npm run deploy\n";
        echo "  4. Copia la nueva dirección del contrato\n";
        echo "  5. Actualiza BLOCKCHAIN_CONTRACT_ADDRESS en .env\n";
    }

} catch (Exception $e) {
    printError("Error al verificar contrato: " . $e->getMessage());
}

// ====================================================================
// 5. MNEMONIC (OPCIONAL)
// ====================================================================
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  5. VERIFICANDO MNEMONIC (OPCIONAL)\n";
echo "═══════════════════════════════════════════════════════════\n";

printInfo("El MNEMONIC NO es necesario para el backend");
printInfo("Solo se usa si quieres regenerar las mismas wallets");
printInfo("Las private keys ya están configuradas directamente");
printSuccess("No se requiere acción");

// ====================================================================
// RESUMEN FINAL
// ====================================================================
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN\n";
echo "═══════════════════════════════════════════════════════════\n";

if ($status['connected'] && $code && $code !== '0x') {
    printSuccess("✅ TODO ESTÁ CORRECTO - LISTO PARA HACER PAGOS");
    echo "\n";
    printInfo("Endpoints disponibles:");
    echo "  POST /api/app/blockchain/contract/create\n";
    echo "  POST /api/app/blockchain/contract/approve\n";
    echo "  POST /api/app/blockchain/payment/create\n";
} else {
    printWarning("⚠️  CONFIGURACIÓN INCOMPLETA - REVISAR ERRORES ARRIBA");
}

echo "\n";
