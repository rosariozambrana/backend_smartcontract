<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioBasico extends Model
{
    /** @use HasFactory<\Database\Factories\ServicioBasicoFactory> */
    use HasFactory;
    protected $table = 'servicio_basicos';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'nombre',
        'descripcion',
        'is_selected',
    ];
}
