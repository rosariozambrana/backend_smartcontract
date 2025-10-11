<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arrays_permisos = [
            'accesorio',
            'accione-control-contrato',
            'accione-control',
            'cliente',
            'contratos',
            'galeria-inmueble',
            'inmueble-accesorio',
            'inmueble',
            'pago',
            'permissions',
            'propietario',
            'roles',
            'tipo-cliente',
            'tipo-inmueble',
            'users'
        ];

        // Crear permisos para cada elemento en el array
        foreach ($arrays_permisos as $permiso) {
            Permission::create(['name' => $permiso . '-list']);
            Permission::create(['name' => $permiso . '-create']);
            Permission::create(['name' => $permiso . '-store']);
            Permission::create(['name' => $permiso . '-edit']);
            Permission::create(['name' => $permiso . '-update']);
            Permission::create(['name' => $permiso . '-delete']);
            Permission::create(['name' => $permiso . '-index']);
            Permission::create(['name' => $permiso . '-show']);
        }
    }
}
