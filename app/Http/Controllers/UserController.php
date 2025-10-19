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
            $data = $this->model::create($request->all());
            return ResponseService::success('Registro guardado correctamente', $data);
        } catch (\Exception $e) {
            return ResponseService::error('Error al guardar el registro', $e->getMessage());
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
