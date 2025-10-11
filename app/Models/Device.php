<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;
    protected $table = "devices";
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'type',
        'status',
        'macAddress',
        'created_at',
        'updated_at',
    ];
    // Relación muchos a muchos con inmuebles
    public function inmuebles(): BelongsToMany
    {
        return $this->belongsToMany(Inmueble::class, 'inmueble_device')
            ->withPivot('role', 'fecha_asignacion')
            ->withTimestamps();
    }
}
