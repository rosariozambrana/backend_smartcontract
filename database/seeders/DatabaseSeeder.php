<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
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
                'private_key' => null,
            ];
        }
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        /*User::create([
            'name' => 'Administrador',
            'email' => 'administrador@gmail.com',
            'usernick' => 'administrador',
            'password' => Hash::make('123456789'),
            'num_id' => '123',
            'num_phone' => '1234567890',
            'tipo_usuario' => 'cliente',
            "created_at" => date_create('now')->format('Y-m-d H:i:s'),
            "updated_at" => date_create('now')->format('Y-m-d H:i:s')
        ]);*/

        // Asignar wallet al propietario
        $walletPropietario = $this->assignWallet();
        User::create([
            'name' => 'Propietario',
            'email' => 'propietario@gmail.com',
            'usernick' => 'propietario',
            'password' => Hash::make('123456789'),
            'num_id' => '123',
            'telefono' => '1234567890',
            'tipo_usuario' => 'propietario',
            'direccion' => 'Calle Falsa 123',
            'wallet_address' => $walletPropietario['address'],
            'wallet_private_key' => $walletPropietario['private_key'],
            "created_at" => date_create('now')->format('Y-m-d H:i:s'),
            "updated_at" => date_create('now')->format('Y-m-d H:i:s')
        ]);

        // Asignar wallet al cliente
        $walletCliente = $this->assignWallet();
        User::create([
            'name' => 'Cliente',
            'email' => 'cliente@gmail.com',
            'usernick' => 'cliente',
            'password' => Hash::make('123456789'),
            'num_id' => '456',
            'telefono' => '0987654321',
            'tipo_usuario' => 'cliente',
            'wallet_address' => $walletCliente['address'],
            'wallet_private_key' => $walletCliente['private_key'],
            "created_at" => date_create('now')->format('Y-m-d H:i:s'),
            "updated_at" => date_create('now')->format('Y-m-d H:i:s')
        ]);

        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            TipoInmuebleSeeder::class,
            DeviceSeeder::class,
//            ServicioBasicoSeeder::class,
        ]);
    }
}
