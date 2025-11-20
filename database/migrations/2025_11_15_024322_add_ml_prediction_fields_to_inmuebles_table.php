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
            $table->decimal('precio_sugerido_ml', 10, 2)->nullable()->after('precio');
            $table->decimal('precio_min_ml', 10, 2)->nullable()->after('precio_sugerido_ml');
            $table->decimal('precio_max_ml', 10, 2)->nullable()->after('precio_min_ml');
            $table->decimal('confianza_ml', 5, 2)->nullable()->after('precio_max_ml');
            $table->timestamp('ultima_prediccion')->nullable()->after('confianza_ml');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inmuebles', function (Blueprint $table) {
            $table->dropColumn([
                'precio_sugerido_ml',
                'precio_min_ml',
                'precio_max_ml',
                'confianza_ml',
                'ultima_prediccion'
            ]);
        });
    }
};
