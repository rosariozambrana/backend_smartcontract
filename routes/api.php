<?php

use App\Http\Controllers\{AuthenticatedController,
    ContratoController,
    GaleriaInmuebleController,
    InmuebleController,
    InmuebleDeviceController,
    MenuController,
    PagoController,
    PermissionsController,
    RolesController,
    SolicitudAlquilerModelController,
    TipoInmuebleController,
    UserController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$controllers = [
    'tipoinmueble' => TipoInmuebleController::class,
    'menus' => MenuController::class,
    'inmuebles' => InmuebleController::class,
    'contratos' => ContratoController::class,
    'pagos' => PagoController::class,
    'roles' => RolesController::class,
    'permissions' => PermissionsController::class,
    'users' => UserController::class,
    'galeria-inmuebles' => GaleriaInmuebleController::class,
    'solicitudes-alquiler' => SolicitudAlquilerModelController::class
];

// Ruta protegida por autenticación
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/app/galeria-inmueble/upload', [GaleriaInmuebleController::class, 'upload'])->name('app.galeria.inmueble.upload');
Route::post('/app/login', [AuthenticatedController::class, 'store'])->name('app.login');
Route::post('/app/logout', [AuthenticatedController::class, 'destroy'])->name('app.logout');
Route::post('/app/create/user', [AuthenticatedController::class, 'createUser'])->name('app.create.user');
foreach ($controllers as $key => $controller) {
    Route::post("/app/$key/store", [$controller, 'store'])->name("app.$key.store");
    Route::post("/app/$key/query", [$controller, 'query'])->name("app.$key.query");
//    Route::put("/app/$key/update", [$controller, 'update'])->name("app.$key.update");
}

// Ruta inmuebles dispositivos
Route::prefix('/app/inmuebles/{inmueble}')->group(function () {
    Route::post('/dispositivos', [InmuebleDeviceController::class, 'store'])->name('inmueble.device.store');
    Route::get('/dispositivos', [InmuebleDeviceController::class, 'index'])->name('inmueble.device.index');
    Route::post('/control-dispositivo', [InmuebleDeviceController::class, 'controlDevice'])->name('inmueble.device.control');
});

// Ruta inmuebles por propietario
Route::get('/app/inmuebles/propietario/{userId}', [InmuebleController::class, 'getInmueblesByPropietario'])->name('app.inmuebles.inmueblesPorPropietario');
Route::post('/app/inmuebles/subir-imagen', [InmuebleController::class, 'subirImagen'])->name('app.inmuebles.subirImagen');
Route::get('/app/inmuebles/{inmueble}', [InmuebleController::class, 'getInmuebleById'])->name('app.inmuebles.getInmuebleById');
Route::get('/app/inmuebles/{inmueble}/galeria', [InmuebleController::class, 'getGaleriaImagenes'])->name('app.inmuebles.galeria');
Route::get('/app/inmuebles/{inmueble}/galeria/first', [GaleriaInmuebleController::class, 'firstImage'])->name('app.inmuebles.galeria.first');
Route::delete('/app/inmuebles/{inmueble}/galeria/{imagenId}', [GaleriaInmuebleController::class, 'destroy'])->name('app.inmuebles.galeria.destroy');
Route::delete('/app/inmuebles/{inmueble}', [InmuebleController::class, 'destroy'])->name('app.inmuebles.destroy');

// Ruta solicitudes por cliente
Route::get('/app/solicitudes-alquiler/cliente/{clienteId}', [SolicitudAlquilerModelController::class, 'solicitudesPorClienteId'])->name('app.solicitudes-alquiler.solicitudesPorClienteId');
Route::get('/app/solicitudes-alquiler/propietario/{propietarioId}', [SolicitudAlquilerModelController::class, 'solicitudesPorPropietario'])->name('app.solicitudes-alquiler.solicitudesPorPropietario');
Route::put('/app/solicitudes-alquiler/{solicitudAlquilerModel}/estado', [SolicitudAlquilerModelController::class, 'updateEstado'])->name('app.solicitudes-alquiler.updateEstado');

//Pagos
Route::get('/app/pagos/contrato/{contratoId}', [PagoController::class, 'getPagosContrato'])->name('app.pagos.contrato');
Route::get('/app/pagos/cliente/{userId}', [PagoController::class, 'getPagosContratoCliente'])->name('app.pagos.contrato.cliente');
Route::get('/app/pagos/pendientes/cliente/{userId}', [PagoController::class, 'getPagosPendientesCliente'])->name('app.pagos.pendientes.cliente');
Route::get('/app/pagos/completados/cliente/{userId}', [PagoController::class, 'getPagosCompletadosCliente'])->name('app.pagos.completados.cliente');
Route::put('/app/pagos/{pago}/estado', [PagoController::class, 'updateEstado'])->name('app.pagos.update.estado');
Route::put('/app/pagos/{pago}/blockchain', [PagoController::class, 'updateBlockchain'])->name('app.pagos.update.blockchain');

//contratos
Route::get('/app/contratos/{contrato}', [ContratoController::class, 'show'])->name('app.contratos.show');
Route::get('/app/contratos/cliente/{userId}', [ContratoController::class, 'getContratosByClienteId'])->name('app.contratos.cliente');
Route::get('/app/contratos/propietario/{userId}', [ContratoController::class, 'getContratosByPropietarioId'])->name('app.contratos.propietario');
Route::put('/app/contratos/{contrato}/estado', [ContratoController::class, 'updateEstado'])->name('app.contratos.update.estado');
Route::put('/app/contratos/{contrato}/blockchain', [ContratoController::class, 'updateBlockchain'])->name('app.contratos.update.blockchain');
Route::put('/app/contratos/{contrato}/pago', [ContratoController::class, 'updatePago'])->name('app.contratos.update.pago');
Route::put('/app/contratos/{contrato}/cliente-aprobado', [ContratoController::class, 'updateClienteAprobado'])->name('app.contratos.update.cliente.aprobado');
Route::put('/app/contratos/{contrato}/fecha-pago', [ContratoController::class, 'updateFechaPago'])->name('app.contratos.update.fecha.pago');

//user
Route::put('/app/users/{user}', [UserController::class, 'update'])->name('app.users.update');
Route::get('/app/users/{user}', [UserController::class, 'show'])->name('app.users.show');
