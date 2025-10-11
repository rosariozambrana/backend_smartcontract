<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoInmueble extends Model
{
    /** @use HasFactory<\Database\Factories\TipoInmuebleFactory> */
    use HasFactory;
    protected $table = "tipo_inmuebles";
    protected $primaryKey = "id";
    protected $fillable = [
        'id',
        'nombre',
        'detalle',
        'created_at',
        'updated_at'
    ];
    public function inmuebles()
    {
        return $this->hasMany(Inmueble::class, 'tipo_inmueble_id', 'id');
    }
}
