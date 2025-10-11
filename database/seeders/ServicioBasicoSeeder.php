<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServicioBasicoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicios = [
            ['nombre' => 'Agua', 'descripcion' => 'Servicio de agua potable', 'is_selected' => false],
            ['nombre' => 'Electricidad', 'descripcion' => 'Servicio de electricidad', 'is_selected' => false],
            ['nombre' => 'Gas', 'descripcion' => 'Servicio de gas natural', 'is_selected' => false],
            ['nombre' => 'Internet', 'descripcion' => 'Servicio de internet de alta velocidad', 'is_selected' => false],
            ['nombre' => 'Televisión por cable', 'descripcion' => 'Servicio de televisión por cable', 'is_selected' => false],
        ];

        foreach ($servicios as $servicio) {
            \App\Models\ServicioBasico::create($servicio);
        }
    }
}
