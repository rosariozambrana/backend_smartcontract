<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE PAGOS ===\n\n";

// Get all pagos with relationships
$pagos = \App\Models\Pago::with('contrato.inmueble')->get();

echo "Total pagos: " . $pagos->count() . "\n\n";

foreach($pagos as $pago) {
    echo "Pago ID: " . $pago->id . "\n";
    echo "  - Estado: " . $pago->estado . "\n";
    echo "  - Monto: " . $pago->monto . "\n";
    echo "  - Contrato ID: " . $pago->contrato_id . "\n";

    if($pago->contrato) {
        echo "  - Contrato existe: Sí\n";
        echo "  - Inmueble ID: " . $pago->contrato->inmueble_id . "\n";

        if($pago->contrato->inmueble) {
            echo "  - Inmueble existe: Sí\n";
            echo "  - Propietario ID: " . $pago->contrato->inmueble->user_id . "\n";
        } else {
            echo "  - Inmueble existe: NO\n";
        }
    } else {
        echo "  - Contrato existe: NO\n";
    }
    echo "\n";
}

echo "\n=== PRUEBA DE CONSULTA PROPIETARIO ID 3 ===\n\n";

$propietarioId = 3;
$pagosCompletados = \App\Models\Pago::whereHas('contrato.inmueble', function ($query) use ($propietarioId) {
    $query->where('user_id', $propietarioId);
})
->where('estado', 'aprobado')
->with(['contrato.inmueble', 'contrato.user'])
->get();

echo "Pagos completados para propietario $propietarioId: " . $pagosCompletados->count() . "\n";

$pagosPendientes = \App\Models\Pago::whereHas('contrato.inmueble', function ($query) use ($propietarioId) {
    $query->where('user_id', $propietarioId);
})
->where('estado', 'pendiente')
->with(['contrato.inmueble', 'contrato.user'])
->get();

echo "Pagos pendientes para propietario $propietarioId: " . $pagosPendientes->count() . "\n";
