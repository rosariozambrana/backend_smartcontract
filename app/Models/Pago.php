<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    /** @use HasFactory<\Database\Factories\PagoFactory> */
    use HasFactory;
    protected $table = "pagos";
    protected $primaryKey = "id";
    protected $fillable = [
        'contrato_id',
        'blockchain_id',
        'fecha_pago',
        'monto',
        'estado',
        'historial_acciones',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'historial_acciones' => 'array',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
