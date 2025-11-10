<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthenticatedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        try {
            if (Auth::attempt($credentials)) {
                $user = User::where('email', $request->email)->first();
                Auth::login($user);
                $datosSession = session()->all();
                $user->setAttribute('session_data', $datosSession);
                return ResponseService::success('Inicio de sesión exitoso', $user,200);
            } else {
                return ResponseService::error('Las credenciales proporcionadas son incorrectas.',[], 401);
            }
        } catch (\Exception $e) {
            return ResponseService::error('Se produjo un error durante el inicio de sesión.', $e->getMessage(), 500);
        }
    }
    public function createUser(Request $request)
    {
        try {
            // Asignar wallet según el modo blockchain
            $walletData = $this->assignWallet($request->wallet_address);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'usernick' => $request->usernick,
                'num_id' => $request->num_id,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion ?? null,
                'photo_path' => $request->photo_path ?? null,
                'tipo_usuario' => $request->tipo_usuario,
                'password' => Hash::make($request->password),
                'wallet_address' => $walletData['address'],
                'wallet_private_key' => $walletData['private_key'],
            ]);

            return ResponseService::success('Usuario creado exitosamente', $user, 201);
        } catch (\Exception $e) {
            return ResponseService::error('Error al crear el usuario', $e->getMessage(), 500);
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try{
            if (Auth::check()) {
                Auth::logout();
                session()->flush();
                return ResponseService::success('Sesión cerrada exitosamente', [], 200);
            } else {
                return ResponseService::success('No hay sesión activa', [], 200);
            }
        } catch (\Exception $e) {
            return ResponseService::error('Error al cerrar sesión', $e->getMessage(), 500);
        }
    }
}
