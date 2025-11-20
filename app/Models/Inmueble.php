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
        'direccion',
        'ciudad',
        'pais',
        'latitude',
        'longitude',
        'detalle',
        'num_habitacion',
        'num_banos',
        'metros_cuadrados',
        'num_piso',
        'precio',
        'anillo',
        'zona_especial',
        'precio_sugerido_ml',
        'precio_min_ml',
        'precio_max_ml',
        'confianza_ml',
        'ultima_prediccion',
        'isOcupado',
        'accesorios',
        'servicios_basicos',
        'tipo_inmueble_id', // Relación con tipo_inmuebles
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_sugerido_ml' => 'decimal:2',
        'precio_min_ml' => 'decimal:2',
        'precio_max_ml' => 'decimal:2',
        'confianza_ml' => 'decimal:2',
        'ultima_prediccion' => 'datetime',
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

