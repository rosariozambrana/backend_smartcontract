<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudAlquilerModel extends Model
{
    /** @use HasFactory<\Database\Factories\SolicitudAlquilerModelFactory> */
    use HasFactory;
    protected $table = 'solicitud_alquiler';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'inmueble_id',
        'user_id',
        'estado',
        'mensaje',
        'servicios_basicos',
    ];
    public function inmuebles()
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id', 'id');
    }
}
