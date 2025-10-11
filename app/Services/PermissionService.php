<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;

class PermissionService
{
    public static function havePermission($permiso)
    {
        $user = Auth::user();
        // verificar si el usuario tiene permisos de super admin
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        // verificar si el usuario tiene permisos para la ruta
        if (!$user->can($permiso)) {
            abort(403);
        }
        return true;
    }

    public static function getPermissions($rutaVisita)
    {
        $user = Auth::user();
        // verificar si el usuario tiene permisos de super admin
        if ($user->hasRole('Super Admin')) {
            return [
                'crear' => true,
                'editar' => true,
                'eliminar' => true,
            ];
        }
        // verificar si el usuario tiene permisos para la ruta
        if (!$user->can($rutaVisita.'-list')) {
            abort(403);
        }
        // verificar si el usuario tiene permisos para la ruta
        if (!$user->can($rutaVisita.'-create')) {
            abort(403);
        }
        if (!$user->can($rutaVisita.'-edit')) {
            abort(403);
        }
        if (!$user->can($rutaVisita.'-delete')) {
            abort(403);
        }
        // verificar si el usuario tiene permisos para la ruta
        if (!$user->can($rutaVisita.'-index')) {
            abort(403);
        }
        if (!$user->can($rutaVisita.'-show')) {
            abort(403);
        }
        // verificar si el usuario tiene permisos para la ruta
        if (!$user->can($rutaVisita.'-store')) {
            abort(403);
        }
        if (!$user->can($rutaVisita.'-update')) {
            abort(403);
        }
        // verificar si el usuario tiene permisos para la ruta
        if (!$user->can($rutaVisita.'-delete')) {
            abort(403);
        }

        return [
            'crear' => $user->can($rutaVisita.'-create', ),
            'editar' => $user->can($rutaVisita.'-edit', ),
            'eliminar' => $user->can($rutaVisita.'-delete'),
        ];
    }
}
