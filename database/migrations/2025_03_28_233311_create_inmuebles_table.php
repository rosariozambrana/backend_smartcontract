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
        Schema::create('inmuebles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('nombre')->unique();
            $table->string('detalle')->nullable();
            $table->string('num_habitacion')->default('01');
            $table->string('num_piso')->default('PB');
            $table->double('precio')->default(1);
            $table->boolean('isOcupado')->default(false);
            $table->foreignId('tipo_inmueble_id')->nullable()->constrained('tipo_inmuebles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->json('accesorios')->nullable();
            $table->json('servicios_basicos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmuebles');
    }
};
