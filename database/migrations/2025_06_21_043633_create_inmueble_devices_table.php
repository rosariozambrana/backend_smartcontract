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
        Schema::create('inmueble_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inmueble_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('role'); // chapa, luz
            $table->date('fechaAsignacion')->default(now());
            $table->string('status')->default('abierta'); // 'abierta'/'cerrada' o 'encendida'/'apagada'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmueble_devices');
    }
};
