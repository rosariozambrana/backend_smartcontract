<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inmueble_id')->constrained('inmuebles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('solicitud_alquiler_id')->nullable()->constrained('solicitud_alquiler')->cascadeOnDelete()->cascadeOnUpdate();
            $table->dateTime('fecha_inicio')->default(now());
            $table->dateTime('fecha_fin')->nullable();
            $table->dateTime('fecha_pago')->nullable();
            $table->double('monto')->default(0);
            $table->string('detalle')->default('');
            $table->json('condicionales')->nullable();
            $table->string('estado')->default('activo'); // Activo, Inactivo, Cancelado
            $table->string('blockchain_address')->nullable(); // Dirección en la blockchain
            $table->boolean('cliente_aprobado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
