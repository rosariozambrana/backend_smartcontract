<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arrays_menus = [
            [
                'title' => 'Dashboard',
                'href' => 'dashboard',
                'icon' => "fa-solid fa-house"
            ],
            [
                'title' => 'Accesorios',
                'href' => 'accesorio.index',
                'icon' => "fa-solid fa-warehouse"
            ],
            [
                'title' => 'Acciones de Control',
                'href' => 'accion-control.index',
                'icon' => "fa-solid fa-layer-group"
            ],
            [
                'title' => 'Clientes',
                'href' => 'cliente.index',
                'icon' => "fa-solid fa-person-shelter"
            ],
            [
                'title' => 'Contratos',
                'href' => 'contrato.index',
                'icon' => "fa-solid fa-user-shield"
            ],
            [
                'title' => 'Galeria Inmueble',
                'href' => 'galeria-inmueble.index',
                'icon' => "fa-solid fa-users-rays"
            ],
            [
                'title' => 'Inmueble Accesorio',
                'href' => 'inmueble-accesorio.index',
                'icon' => "fa-solid fa-building"
            ],
            [
                'title' => 'Inmueble',
                'href' => 'inmueble.index',
                'icon' => "fa-solid fa-money-bill-trend-up"
            ],
            [
                'title' => 'Pagos',
                'href' => 'pago.index',
                'icon' => "fa-solid fa-store"
            ],
            [
                'title' => 'Propietario',
                'href' => 'propietario.index',
                'icon' => "fa-solid fa-bars"
            ],
            [
                'title' => 'Tipos de Clientes',
                'href' => 'tipo-cliente.index',
                'icon' => "fa-solid fa-key"
            ],
            [
                'title' => 'Tipo Inmuebles',
                'href' => 'tipo-inmueble.index',
                'icon' => "fa-solid fa-user-tie"
            ],
            [
                'title' => 'Usuarios',
                'href' => 'users.index',
                'icon' => "fa-solid fa-circle-user"
            ]
        ];


        foreach ($arrays_menus as $menu) {
            Menu::create([
                'title' => $menu['title'],
                'href' =>  $menu['href'],
                'icon' => $menu['icon'],
                'isMain' => 1,
            ]);
        }
    }
}
