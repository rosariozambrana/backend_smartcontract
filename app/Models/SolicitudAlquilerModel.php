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
    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
