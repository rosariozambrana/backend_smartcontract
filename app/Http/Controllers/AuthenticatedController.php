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
        try{
            $user = User::create($request->all());
            $user->password = Hash::make($request->password);
            $user->save();
            return ResponseService::success('Usuario creado exitosamente', $user, 201);
        } catch (\Exception $e) {
            return ResponseService::error('Error al crear el usuario', $e->getMessage(), 500);
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
