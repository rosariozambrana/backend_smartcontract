<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condicional extends Model
{
    /** @use HasFactory<\Database\Factories\CondicionalFactory> */
    use HasFactory;
    protected $table = "condicionals";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = [
        'descripcion',
        'tipoCondicion', // Puede ser 'condicional' o 'obligatorio'
        'accion',
        'parametros', // Almacena los parámetros en formato JSON
        'created_at',
        'updated_at'
    ];
}
