<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
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
            'tipo_usuario' => 'Cliente',
            'tipo_cliente' => 'Cliente',
            "created_at" => date_create('now')->format('Y-m-d H:i:s'),
            "updated_at" => date_create('now')->format('Y-m-d H:i:s')
        ]);*/

        User::create([
            'name' => 'Propietario',
            'email' => 'propietario@gmail.com',
            'usernick' => 'propietario',
            'password' => Hash::make('123456789'),
            'num_id' => '123',
            'telefono' => '1234567890',
            'tipo_usuario' => 'propietario',
            'direccion' => 'Calle Falsa 123',
            "created_at" => date_create('now')->format('Y-m-d H:i:s'),
            "updated_at" => date_create('now')->format('Y-m-d H:i:s')
        ]);

        User::create([
            'name' => 'Cliente',
            'email' => 'cliente@gmail.com',
            'usernick' => 'cliente',
            'password' => Hash::make('123456789'),
            'num_id' => '456',
            'telefono' => '0987654321',
            'tipo_usuario' => 'cliente',
            'tipo_cliente' => 'cliente',
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
