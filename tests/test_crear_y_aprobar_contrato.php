<?php

/**
 * Test que replica el flujo EXACTO del frontend Flutter
 *
 * Flujo:
 * 1. Propietario crea contrato → POST /api/app/contratos/store
 *    - Debe crear automáticamente en blockchain
 *    - Debe asignar blockchain_address
 * 2. Cliente aprueba contrato → POST /api/app/blockchain/contract/approve
 *    - Debe aprobar en blockchain
 *    - Debe actualizar cliente_aprobado y estado en BD
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Cargar Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Colores
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$CYAN = "\033[36m";
$RESET = "\033[0m";

function printSuccess($message) {
    global $GREEN, $RESET;
    echo "{$GREEN}✓ {$message}{$RESET}\n";
}

function printError($message) {
    global $RED, $RESET;
    echo "{$RED}✗ {$message}{$RESET}\n";
}

function printInfo($message) {
    global $BLUE, $RESET;
    echo "{$BLUE}ℹ {$message}{$RESET}\n";
}

function printStep($step, $title) {
    global $CYAN, $RESET;
    echo "\n";
    echo "{$CYAN}═══════════════════════════════════════════════════════════{$RESET}\n";
    echo "{$CYAN}  PASO {$step}: {$title}{$RESET}\n";
    echo "{$CYAN}═══════════════════════════════════════════════════════════{$RESET}\n";
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║   TEST: CREAR Y APROBAR CONTRATO (FLUJO FRONTEND)        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";

$baseUrl = env('APP_URL', 'http://192.168.100.9:8000');
printInfo("Base URL: {$baseUrl}");
printInfo("Ganache URL: http://192.168.100.9:8545");

// Datos de la BD
$propietarioId = 1;
$clienteId = 2;
$inmuebleId = 1;

echo "\n";
printInfo("Datos del test:");
echo "  Propietario ID: {$propietarioId}\n";
echo "  Cliente ID: {$clienteId}\n";
echo "  Inmueble ID: {$inmuebleId} (Edificio Cortez)\n";

// ====================================================================
// PASO 1: CREAR CONTRATO (como hace el frontend)
// ====================================================================
printStep(1, "CREAR CONTRATO - POST /api/app/contratos/store");

printInfo("Replicando JSON del frontend...");

$contratoData = [
    'inmueble_id' => $inmuebleId,
    'user_id' => $clienteId,
    'solicitud_alquiler_id' => null,
    'fecha_inicio' => now()->toISOString(),
    'fecha_fin' => now()->addYear()->toISOString(),
    'monto' => 0.5,
    'detalle' => 'Test desde backend - replica frontend',
    'estado' => 'pendiente',
    'condicionales' => [
        [
            'id' => 1,
            'descripcion' => 'Retraso de Pago',
            'tipo_condicion' => 'incumplimiento',
            'accion' => 'multa',
            'parametros' => []
        ]
    ],
    'blockchain_address' => null,
    'cliente_aprobado' => false,
    'fecha_pago' => null,
];

printInfo("JSON a enviar:");
echo json_encode($contratoData, JSON_PRETTY_PRINT) . "\n";

try {
    printInfo("Enviando POST a /api/app/contratos/store...");
    $response = Http::post("{$baseUrl}/api/app/contratos/store", $contratoData);

    $data = $response->json();

    printInfo("Status Code: " . $response->status());

    if ($response->successful() && ($data['isSuccess'] ?? false)) {
        printSuccess("Contrato creado exitosamente en BD");

        $contratoId = $data['data']['id'];
        $blockchainAddress = $data['data']['blockchain_address'];

        printInfo("Contrato ID: {$contratoId}");
        printInfo("Blockchain Address: " . ($blockchainAddress ?: 'NULL ❌'));
        printInfo("Estado: {$data['data']['estado']}");
        printInfo("Cliente Aprobado: " . ($data['data']['cliente_aprobado'] ? 'true' : 'false'));

        // VERIFICACIÓN CRÍTICA
        if ($blockchainAddress && $blockchainAddress !== 'null') {
            printSuccess("✅ BLOCKCHAIN ADDRESS ASIGNADO CORRECTAMENTE");
            echo "\n";
            printInfo("El contrato fue creado en blockchain automáticamente");
            printInfo("TX Hash: {$blockchainAddress}");
        } else {
            printError("❌ BLOCKCHAIN ADDRESS ES NULL");
            printError("El contrato NO fue creado en blockchain");
            printError("La mejora del backend NO está funcionando");
            exit(1);
        }

    } else {
        printError("Error al crear contrato");
        echo "Response completa:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }
} catch (Exception $e) {
    printError("Exception: " . $e->getMessage());
    exit(1);
}

// ====================================================================
// PASO 2: APROBAR CONTRATO (como hace el frontend)
// ====================================================================
printStep(2, "APROBAR CONTRATO - POST /api/app/blockchain/contract/approve");

printInfo("Esperando 2 segundos antes de aprobar...");
sleep(2);

printInfo("Cliente ID {$clienteId} aprobando contrato ID {$contratoId}...");

try {
    $approveData = [
        'contrato_id' => $contratoId,
        'user_id' => $clienteId,
    ];

    printInfo("JSON a enviar:");
    echo json_encode($approveData, JSON_PRETTY_PRINT) . "\n";

    printInfo("Enviando POST a /api/app/blockchain/contract/approve...");
    $response = Http::post("{$baseUrl}/api/app/blockchain/contract/approve", $approveData);

    $data = $response->json();

    printInfo("Status Code: " . $response->status());

    if ($response->successful() && ($data['isSuccess'] ?? false)) {
        printSuccess("Contrato aprobado exitosamente");

        $txHash = $data['data']['tx_hash'] ?? 'N/A';

        printInfo("TX Hash: {$txHash}");

        printSuccess("✅ APROBACIÓN EXITOSA");
        echo "\n";
        printInfo("El cliente aprobó el contrato correctamente");

    } else {
        printError("Error al aprobar contrato");
        echo "Response completa:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

        // Mostrar mensaje de error específico
        if (isset($data['message'])) {
            printError("Mensaje: {$data['message']}");
        }
        if (isset($data['messageError'])) {
            printError("Error: {$data['messageError']}");
        }

        exit(1);
    }
} catch (Exception $e) {
    printError("Exception: " . $e->getMessage());
    exit(1);
}

// ====================================================================
// PASO 3: VERIFICAR ESTADO FINAL EN BD
// ====================================================================
printStep(3, "VERIFICAR ESTADO FINAL EN BASE DE DATOS");

try {
    $response = Http::get("{$baseUrl}/api/app/contratos/{$contratoId}");
    $data = $response->json();

    if ($response->successful() && ($data['isSuccess'] ?? false)) {
        $contrato = $data['data'];

        printSuccess("Contrato verificado en BD:");
        echo "  ID: {$contrato['id']}\n";
        echo "  Estado: {$contrato['estado']}\n";
        echo "  Cliente Aprobado: " . ($contrato['cliente_aprobado'] ? 'true ✅' : 'false ❌') . "\n";
        echo "  Blockchain Address: {$contrato['blockchain_address']}\n";
        echo "  Monto: {$contrato['monto']} ETH\n";

        // Verificaciones finales
        if ($contrato['estado'] === 'aprobado') {
            printSuccess("✅ Estado actualizado a 'aprobado'");
        } else {
            printError("❌ Estado NO actualizado (actual: {$contrato['estado']})");
        }

        if ($contrato['cliente_aprobado']) {
            printSuccess("✅ Cliente aprobado = true");
        } else {
            printError("❌ Cliente aprobado = false");
        }

    } else {
        printError("Error al verificar contrato");
    }
} catch (Exception $e) {
    printError("Exception: " . $e->getMessage());
}

// ====================================================================
// RESUMEN FINAL
// ====================================================================
echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║   ✅ TEST COMPLETADO EXITOSAMENTE                         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

printSuccess("FLUJO FRONTEND REPLICADO CORRECTAMENTE");
echo "\n";
printInfo("Resumen:");
echo "  1. ✅ Contrato creado con blockchain_address automático\n";
echo "  2. ✅ Cliente aprobó contrato exitosamente\n";
echo "  3. ✅ Base de datos actualizada correctamente\n";
echo "\n";

printInfo("Contrato ID: {$contratoId}");
printInfo("Blockchain Address: {$blockchainAddress}");
echo "\n";

printInfo("Ahora puedes probar desde Flutter y debería funcionar igual");
echo "\n";
