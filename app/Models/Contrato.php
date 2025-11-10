<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    /** @use HasFactory<\Database\Factories\ContratoFactory> */
    use HasFactory;
    protected $table = "contratos";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = [
        'inmueble_id',
        'user_id',
        'solicitud_alquiler_id',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'monto',
        'detalle',
        'estado',
        'condicionales', // Almacena los condicionales en formato JSON
        'blockchain_address', // Dirección en la blockchain
        'cliente_aprobado', // Indica si el cliente ha aprobado el contrato
        'created_at',
        'updated_at'
    ];
    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    // obtener los pagos asociados al contrato
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'contrato_id', 'id');
    }
}
