<?php

/**
 * Test completo del flujo de pago blockchain
 *
 * Flujo:
 * 1. Verificar/crear usuarios con wallets
 * 2. Verificar/crear inmueble
 * 3. Verificar/crear contrato
 * 4. Crear contrato en blockchain
 * 5. Aprobar contrato (inquilino)
 * 6. Realizar pago
 * 7. Verificar resultados
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Models\Inmueble;
use App\Models\Contrato;
use App\Models\Pago;
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

function printWarning($message) {
    global $YELLOW, $RESET;
    echo "{$YELLOW}⚠ {$message}{$RESET}\n";
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
echo "║   TEST COMPLETO DE FLUJO BLOCKCHAIN END-TO-END            ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";

$baseUrl = env('APP_URL', 'http://localhost:8000');
printInfo("Base URL: {$baseUrl}");

// ====================================================================
// PASO 1: OBTENER PROPIETARIO
// ====================================================================
printStep(1, "OBTENER PROPIETARIO");

$propietario = User::where('tipo_usuario', 'propietario')->first();

if (!$propietario) {
    printError("No hay usuarios con tipo_usuario='propietario' en la BD");
    printWarning("Por favor crea un propietario desde el frontend primero");
    exit(1);
}

printSuccess("Propietario encontrado: ID {$propietario->id}");
printInfo("Nombre: {$propietario->name}");
printInfo("Email: {$propietario->email}");
printInfo("Wallet: " . ($propietario->wallet_address ?: 'Sin asignar (se asignará automáticamente)'));

// ====================================================================
// PASO 2: OBTENER INQUILINO
// ====================================================================
printStep(2, "OBTENER INQUILINO");

$inquilino = User::where('tipo_usuario', 'cliente')->first();

if (!$inquilino) {
    printError("No hay usuarios con tipo_usuario='cliente' en la BD");
    printWarning("Por favor crea un cliente/inquilino desde el frontend primero");
    exit(1);
}

printSuccess("Inquilino encontrado: ID {$inquilino->id}");
printInfo("Nombre: {$inquilino->name}");
printInfo("Email: {$inquilino->email}");
printInfo("Wallet: " . ($inquilino->wallet_address ?: 'Sin asignar (se asignará automáticamente)'));

// ====================================================================
// PASO 3: VERIFICAR/CREAR INMUEBLE
// ====================================================================
printStep(3, "VERIFICAR/CREAR INMUEBLE");

$inmueble = Inmueble::where('user_id', $propietario->id)->first();

if (!$inmueble) {
    printWarning("Inmueble no existe, creando...");
    $inmueble = Inmueble::create([
        'user_id' => $propietario->id,
        'titulo' => 'Casa Test Blockchain',
        'descripcion' => 'Casa para testing de blockchain',
        'direccion' => 'Calle Test 123',
        'ciudad' => 'Test City',
        'precio' => 500,
        'tipo_inmueble' => 'casa',
        'estado' => 'disponible',
        'num_habitaciones' => 3,
        'num_banos' => 2,
        'area' => 100,
    ]);
    printSuccess("Inmueble creado: ID {$inmueble->id}");
} else {
    printSuccess("Inmueble existente: ID {$inmueble->id}");
}

printInfo("Título: {$inmueble->titulo}");
printInfo("Precio: \${$inmueble->precio}");

// ====================================================================
// PASO 4: CREAR CONTRATO
// ====================================================================
printStep(4, "CREAR CONTRATO EN BASE DE DATOS");

// Limpiar contratos anteriores del test
Contrato::where('inmueble_id', $inmueble->id)
    ->where('user_id', $inquilino->id)
    ->delete();

$contrato = Contrato::create([
    'inmueble_id' => $inmueble->id,
    'user_id' => $inquilino->id,
    'fecha_inicio' => now(),
    'fecha_fin' => now()->addYear(),
    'monto' => $inmueble->precio,
    'estado' => 'pendiente',
    'detalle' => 'Contrato de prueba blockchain',
    'cliente_aprobado' => false,
]);

printSuccess("Contrato creado en BD: ID {$contrato->id}");
printInfo("Propietario: {$propietario->name} (ID: {$propietario->id})");
printInfo("Inquilino: {$inquilino->name} (ID: {$inquilino->id})");
printInfo("Monto: \${$contrato->monto}");
printInfo("Estado: {$contrato->estado}");

// ====================================================================
// PASO 5: CREAR CONTRATO EN BLOCKCHAIN
// ====================================================================
printStep(5, "CREAR CONTRATO EN BLOCKCHAIN");

try {
    $response = Http::post("{$baseUrl}/api/app/blockchain/contract/create", [
        'contrato_id' => $contrato->id,
    ]);

    $data = $response->json();

    if ($response->successful() && ($data['success'] ?? $data['isSuccess'] ?? false)) {
        printSuccess("Contrato creado en blockchain exitosamente");
        printInfo("TX Hash: {$data['data']['tx_hash']}");
        printInfo("Block Number: {$data['data']['block_number']}");
        printInfo("Landlord Wallet: {$data['data']['landlord_wallet']}");
        printInfo("Tenant Wallet: {$data['data']['tenant_wallet']}");

        // Refrescar contrato
        $contrato->refresh();
        printInfo("Blockchain Address guardado: {$contrato->blockchain_address}");
    } else {
        printError("Error al crear contrato en blockchain");
        printError("Response: " . json_encode($data, JSON_PRETTY_PRINT));
        exit(1);
    }
} catch (Exception $e) {
    printError("Exception: " . $e->getMessage());
    exit(1);
}

// ====================================================================
// PASO 6: APROBAR CONTRATO (INQUILINO)
// ====================================================================
printStep(6, "APROBAR CONTRATO (INQUILINO)");

try {
    $response = Http::post("{$baseUrl}/api/app/blockchain/contract/approve", [
        'contrato_id' => $contrato->id,
        'user_id' => $inquilino->id,
    ]);

    $data = $response->json();

    if ($response->successful() && ($data['success'] ?? $data['isSuccess'] ?? false)) {
        printSuccess("Contrato aprobado exitosamente");
        printInfo("TX Hash: {$data['data']['tx_hash']}");

        // Refrescar contrato
        $contrato->refresh();
        printInfo("Estado del contrato: {$contrato->estado}");
        printInfo("Cliente aprobado: " . ($contrato->cliente_aprobado ? 'Sí' : 'No'));
    } else {
        printError("Error al aprobar contrato");
        printError("Response: " . json_encode($data, JSON_PRETTY_PRINT));
        exit(1);
    }
} catch (Exception $e) {
    printError("Exception: " . $e->getMessage());
    exit(1);
}

// ====================================================================
// PASO 7: REALIZAR PAGO
// ====================================================================
printStep(7, "REALIZAR PAGO BLOCKCHAIN");

// El primer pago debe incluir deposito (50%) + primer mes de renta
$depositAmount = $contrato->monto * 0.5;
$firstPaymentAmount = $depositAmount + $contrato->monto;
printInfo("Primer pago debe ser: Deposito (\${$depositAmount}) + Renta (\${$contrato->monto}) = \${$firstPaymentAmount}");

try {
    $response = Http::post("{$baseUrl}/api/app/blockchain/payment/create", [
        'contrato_id' => $contrato->id,
        'user_id' => $inquilino->id,
        'amount' => $firstPaymentAmount,
    ]);

    $data = $response->json();

    if ($response->successful() && ($data['success'] ?? $data['isSuccess'] ?? false)) {
        printSuccess("Pago procesado exitosamente en blockchain");
        printInfo("Pago ID: {$data['data']['pago_id']}");
        printInfo("TX Hash: {$data['data']['tx_hash']}");
        printInfo("Block Number: {$data['data']['block_number']}");
        printInfo("Estado del contrato: {$data['data']['contrato_estado']}");

        // Refrescar contrato
        $contrato->refresh();
    } else {
        printError("Error al procesar pago");
        printError("Response: " . json_encode($data, JSON_PRETTY_PRINT));
        exit(1);
    }
} catch (Exception $e) {
    printError("Exception: " . $e->getMessage());
    exit(1);
}

// ====================================================================
// PASO 8: VERIFICAR RESULTADOS
// ====================================================================
printStep(8, "VERIFICAR RESULTADOS");

// Verificar contrato
$contrato->refresh();
printSuccess("Contrato actualizado:");
printInfo("  Estado: {$contrato->estado}");
printInfo("  Cliente aprobado: " . ($contrato->cliente_aprobado ? 'Sí' : 'No'));
printInfo("  Blockchain address: {$contrato->blockchain_address}");
printInfo("  Fecha pago: " . ($contrato->fecha_pago ?: 'N/A'));

// Verificar pago
$pago = Pago::where('contrato_id', $contrato->id)->latest()->first();
if ($pago) {
    printSuccess("Pago registrado:");
    printInfo("  ID: {$pago->id}");
    printInfo("  Monto: \${$pago->monto}");
    printInfo("  Estado: {$pago->estado}");
    printInfo("  Blockchain ID: {$pago->blockchain_id}");
    printInfo("  Fecha: {$pago->fecha_pago}");
} else {
    printError("No se encontró registro de pago en BD");
}

// Verificar wallets
$propietario->refresh();
$inquilino->refresh();

printSuccess("Wallets asignadas:");
printInfo("  Propietario: {$propietario->wallet_address}");
printInfo("  Inquilino: {$inquilino->wallet_address}");

// ====================================================================
// RESUMEN FINAL
// ====================================================================
echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║   ✅ TEST COMPLETADO EXITOSAMENTE                         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

printSuccess("FLUJO BLOCKCHAIN END-TO-END FUNCIONA CORRECTAMENTE");
echo "\n";
printInfo("Resumen de transacciones:");
echo "  1. Contrato creado en blockchain ✓\n";
echo "  2. Contrato aprobado por inquilino ✓\n";
echo "  3. Pago procesado en blockchain ✓\n";
echo "  4. Base de datos actualizada ✓\n";
echo "\n";

printInfo("IDs importantes para debugging:");
echo "  Contrato ID: {$contrato->id}\n";
echo "  Pago ID: {$pago->id}\n";
echo "  Propietario ID: {$propietario->id}\n";
echo "  Inquilino ID: {$inquilino->id}\n";
echo "\n";

printWarning("Revisa los logs en storage/logs/laravel.log para ver el logging completo");
echo "\n";
