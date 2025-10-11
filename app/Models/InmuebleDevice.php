<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InmuebleDevice extends Model
{
    /** @use HasFactory<\Database\Factories\InmuebleDeviceFactory> */
    use HasFactory;
    protected $table = "inmueble_devices";
    public $incrementing = true; // Ajusta esto según sea necesario
    protected $fillable = [
        'inmueble_id',
        'device_id',
        'status', // activo, inactivo
        'role', // chapa, luz,
        'fecha_asignacion', // Fecha de asignación del dispositivo al inmueble
        'created_at',
        'updated_at',
    ];
    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }
    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
