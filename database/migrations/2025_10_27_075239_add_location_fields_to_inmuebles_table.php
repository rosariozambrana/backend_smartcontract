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
            $table->string('direccion')->nullable()->after('nombre');
            $table->decimal('latitude', 10, 7)->nullable()->after('direccion');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('ciudad')->nullable()->after('longitude');
            $table->string('pais')->default('Bolivia')->after('ciudad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inmuebles', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'latitude', 'longitude', 'ciudad', 'pais']);
        });
    }
};
