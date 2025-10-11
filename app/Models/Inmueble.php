<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Inmueble extends Model
{
    /** @use HasFactory<\Database\Factories\InmuebleFactory> */
    use HasFactory;
    protected $table = "inmuebles";
    protected $primaryKey = "id";
    protected $fillable = [
        'user_id',
        'nombre',
        'detalle',
        'num_habitacion',
        'num_piso',
        'precio',
        'isOcupado',
        'accesorios',
        'servicios_basicos',
        'tipo_inmueble_id', // Relación con tipo_inmuebles
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'inmueble_id', 'id');
    }
    public function galeria()
    {
        return $this->hasMany(GaleriaInmueble::class, 'inmueble_id', 'id');
    }
    // Relación muchos a muchos con dispositivos
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'inmueble_devices')
            ->withPivot('role', 'fecha_asignacion')
            ->withTimestamps();
    }
    public function tipoInmueble()
    {
        return $this->belongsTo(TipoInmueble::class, 'tipo_inmueble_id', 'id');
    }
    public function imagenes()
    {
        return $this->hasMany(GaleriaInmueble::class, 'inmueble_id', 'id');
    }
    public function solicitudesAlquiler()
    {
        return $this->hasMany(SolicitudAlquilerModel::class, 'inmueble_id', 'id');
    }
}
