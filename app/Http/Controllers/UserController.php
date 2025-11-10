<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasDynamicQuery;
use App\Http\Requests\StoreUserRequest;
use App\Models\Accesorio;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    use HasDynamicQuery;

    public User $model;
    public $rutaVisita = 'User';
    public function __construct()
    {
        $this->model = new User();
        /*$this->middleware('permission:almacen-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:almacen-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:almacen-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:almacen-delete', ['only' => ['destroy']]);*/
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render($this->rutaVisita . '/Index', array_merge([
            'listado' => $this->model::all(),
        ], PermissionService::getPermissions($this->rutaVisita)));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permiso = strtolower($this->rutaVisita);
        if (!Auth::user()->can($permiso.'-create')) {
            abort(403);
        }
        return Inertia::render($this->rutaVisita . '/CreateUpdate', array_merge([
            'isCreate' => true
        ], PermissionService::getPermissions($permiso)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            // Asignar wallet según el modo
            $walletData = $this->assignWallet($request->wallet_address);

            $data = $this->model::create([
                'name' => $request->name,
                'email' => $request->email,
                'usernick' => $request->usernick,
                'num_id' => $request->num_id,
                'telefono' => $request->telefono,
                'photo_path' => $request->photo_path,
                'tipo_usuario' => $request->tipo_usuario,
                'password' => $request->password,
                'wallet_address' => $walletData['address'],
                'wallet_private_key' => $walletData['private_key'],
            ]);
            
            return ResponseService::success('Registro guardado correctamente', $data);
        } catch (\Exception $e) {
            return ResponseService::error('Error al guardar el registro', $e->getMessage());
        }
    }

    /**
     * Asigna wallet según el modo (Ganache o Producción)
     */
    private function assignWallet($requestedWallet = null)
    {
        $mode = config('blockchain.mode');

        if ($mode === 'ganache') {
            // MODO DESARROLLO: Asignar wallet de Ganache
            $wallets = config('blockchain.ganache_wallets');

            // Contar usuarios que ya tienen wallet asignada
            $assignedCount = User::whereNotNull('wallet_address')->count();

            // Asignar la siguiente wallet disponible (cicla entre 0-9)
            $walletIndex = $assignedCount % count($wallets);

            return [
                'address' => $wallets[$walletIndex]['address'],
                'private_key' => $wallets[$walletIndex]['private_key'],
            ];
        } else {
            // MODO PRODUCCIÓN: Usar wallet enviada desde Flutter
            return [
                'address' => $requestedWallet,
                'private_key' => null, // En producción, Flutter maneja la clave
            ];
        }
    }

    /**
     * Obtener la private key de un usuario (SOLO en modo Ganache)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrivateKey($id)
    {
        try {
            // Verificar que estamos en modo Ganache
            if (config('blockchain.mode') !== 'ganache') {
                return ResponseService::error('Private keys solo están disponibles en modo Ganache', '', 403);
            }

            $user = User::findOrFail($id);

            return ResponseService::success('Private key obtenida correctamente', [
                'wallet_address' => $user->wallet_address,
                'wallet_private_key' => $user->wallet_private_key,
            ]);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener private key', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        try{
            return ResponseService::success('Registro encontrado correctamente', $user);
        }catch (\Exception $e){
            return ResponseService::error('Error al mostrar el registro', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUserRequest $request, User $user)
    {
        try {
            $user->update([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'usernick' => $request->input('usernick'),
                'num_id' => $request->input('num_id'),
                'telefono' => $request->input('telefono'),
                'wallet_address' => $request->input('wallet_address'),
                'direccion' => $request->input('direccion'),
            ]);
            return ResponseService::success('Registro actualizado correctamente', $user);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el registro', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return ResponseService::success('Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ResponseService::error('Error al eliminar el registro', $e->getMessage());
        }
    }
}
