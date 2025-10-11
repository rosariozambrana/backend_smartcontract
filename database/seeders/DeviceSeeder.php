<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // devices como chapa y luz
        $devices = [
            ['type' => 'chapa', 'status' => 'abierto', 'macAddress' => null],
            ['type' => 'luz', 'status' => 'activo', 'macAddress' => null],
        ];
        foreach ($devices as $device) {
            \App\Models\Device::create($device);
        }
    }
}
