<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\{
    ContratoController,
    GaleriaInmuebleController,
    InmuebleController,
    MenuController,
    PagoController,
    PermissionsController,
    RolesController,
    TipoInmuebleController,
    UserController
};

$resources = [
    'contrato' => ContratoController::class,
    'galeria-inmueble' => GaleriaInmuebleController::class,
    'inmueble' => InmuebleController::class,
    'menu' => MenuController::class,
    'pago' => PagoController::class,
    'permissions' => PermissionsController::class,
    'roles' => RolesController::class,
    'tipo-inmueble' => TipoInmuebleController::class,
    'users' => UserController::class,
];

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

foreach ($resources as $key => $controller) {
    Route::resource("/$key", $controller);
    Route::post("/$key/query", [$controller, 'query'])->name("$key.query");
}
