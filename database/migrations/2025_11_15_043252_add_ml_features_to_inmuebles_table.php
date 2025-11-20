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
        Schema::table('inmuebles', function (Blueprint $table) {
            // Campos para características físicas del inmueble
            $table->integer('num_banos')->default(1)->after('num_habitacion');
            $table->decimal('metros_cuadrados', 8, 2)->default(70.00)->after('num_banos');

            // Campos calculados automáticamente por geolocalización
            $table->integer('anillo')->nullable()->after('ciudad')->comment('Anillo 1-10 calculado por distancia del centro');
            $table->string('zona_especial', 50)->nullable()->after('anillo')->comment('Equipetrol, Urubo, etc. - calculado por coordenadas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inmuebles', function (Blueprint $table) {
            $table->dropColumn([
                'num_banos',
                'metros_cuadrados',
                'anillo',
                'zona_especial'
            ]);
        });
    }
};
