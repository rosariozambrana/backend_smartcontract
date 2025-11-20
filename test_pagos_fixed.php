<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DESPUÉS DE LA CORRECCIÓN ===\n\n";

$propietarioId = 3;

// Test pagos completados (estado 'pagado')
$pagosCompletados = \App\Models\Pago::whereHas('contrato.inmueble', function ($query) use ($propietarioId) {
    $query->where('user_id', $propietarioId);
})
->where('estado', 'pagado')
->with(['contrato.inmueble', 'contrato.user'])
->get();

echo "✅ Pagos COMPLETADOS para propietario $propietarioId: " . $pagosCompletados->count() . "\n";

foreach($pagosCompletados as $pago) {
    echo "   - Pago ID: " . $pago->id . " | Monto: " . $pago->monto . " | Cliente: " . ($pago->contrato->user->name ?? 'N/A') . "\n";
}

echo "\n";

// Test pagos pendientes
$pagosPendientes = \App\Models\Pago::whereHas('contrato.inmueble', function ($query) use ($propietarioId) {
    $query->where('user_id', $propietarioId);
})
->where('estado', 'pendiente')
->with(['contrato.inmueble', 'contrato.user'])
->get();

echo "⏰ Pagos PENDIENTES para propietario $propietarioId: " . $pagosPendientes->count() . "\n";

echo "\n=== PRUEBA COMPLETADA ===\n";
