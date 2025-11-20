<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN COMPLETA DE PAGOS ===\n\n";

// Test user IDs
$propietarioId = 3; // Rosario
$clienteId = 5;     // Yoseline

echo "📊 ESTADÍSTICAS GENERALES:\n";
echo "Total pagos en BD: " . \App\Models\Pago::count() . "\n";
echo "Pagos con estado 'pendiente': " . \App\Models\Pago::where('estado', 'pendiente')->count() . "\n";
echo "Pagos con estado 'pagado': " . \App\Models\Pago::where('estado', 'pagado')->count() . "\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "👤 CLIENTE (ID: $clienteId) - Yoseline\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Cliente - Pagos pendientes
$pagosPendientesCliente = \App\Models\Pago::whereHas('contrato', function ($query) use ($clienteId) {
    $query->where('user_id', $clienteId);
})
->where('estado', 'pendiente')
->with(['contrato'])
->get();

echo "⏰ Pagos PENDIENTES (que debe pagar): " . $pagosPendientesCliente->count() . "\n";
foreach($pagosPendientesCliente as $pago) {
    echo "   - Pago ID: " . $pago->id . " | Monto: " . $pago->monto . " ETH | Fecha: " . $pago->fecha_pago . "\n";
}

// Cliente - Pagos completados
$pagosCompletadosCliente = \App\Models\Pago::whereHas('contrato', function ($query) use ($clienteId) {
    $query->where('user_id', $clienteId);
})
->where('estado', 'pagado')
->with(['contrato'])
->get();

echo "\n✅ Pagos COMPLETADOS (que ya pagó): " . $pagosCompletadosCliente->count() . "\n";
foreach($pagosCompletadosCliente as $pago) {
    echo "   - Pago ID: " . $pago->id . " | Monto: " . $pago->monto . " ETH | Fecha: " . $pago->fecha_pago . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🏠 PROPIETARIO (ID: $propietarioId) - Rosario\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Propietario - Pagos pendientes
$pagosPendientesPropietario = \App\Models\Pago::whereHas('contrato.inmueble', function ($query) use ($propietarioId) {
    $query->where('user_id', $propietarioId);
})
->where('estado', 'pendiente')
->with(['contrato.inmueble', 'contrato.user'])
->get();

echo "⏰ Pagos PENDIENTES (que le deben): " . $pagosPendientesPropietario->count() . "\n";
foreach($pagosPendientesPropietario as $pago) {
    $clienteNombre = $pago->contrato->user->name ?? 'N/A';
    echo "   - Pago ID: " . $pago->id . " | Monto: " . $pago->monto . " ETH | Cliente: $clienteNombre\n";
}

// Propietario - Pagos completados
$pagosCompletadosPropietario = \App\Models\Pago::whereHas('contrato.inmueble', function ($query) use ($propietarioId) {
    $query->where('user_id', $propietarioId);
})
->where('estado', 'pagado')
->with(['contrato.inmueble', 'contrato.user'])
->get();

echo "\n💰 Pagos RECIBIDOS (que ya le pagaron): " . $pagosCompletadosPropietario->count() . "\n";
foreach($pagosCompletadosPropietario as $pago) {
    $clienteNombre = $pago->contrato->user->name ?? 'N/A';
    echo "   - Pago ID: " . $pago->id . " | Monto: " . $pago->monto . " ETH | Cliente: $clienteNombre\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";
