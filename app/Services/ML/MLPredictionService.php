<?php

namespace App\Services\ML;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Inmueble;

class MLPredictionService
{
    protected $mlServiceUrl;

    public function __construct()
    {
        $this->mlServiceUrl = env('ML_SERVICE_URL', 'http://localhost:5000');
    }

    /**
     * Verifica si el servicio ML está disponible
     */
    public function checkHealth(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->mlServiceUrl}/");
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('ML Service no disponible: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Predice el precio de un inmueble
     */
    public function predecirPrecio(array $features): ?array
    {
        try {
            if (!$this->checkHealth()) {
                return null;
            }

            $response = Http::timeout(10)->post("{$this->mlServiceUrl}/predict", [
                'lat' => $features['latitud'] ?? -17.783889,
                'lon' => $features['longitud'] ?? -63.182222,
                'metros' => $features['metros_cuadrados'] ?? 70,
                'cuartos' => $features['num_habitacion'] ?? 1,
                'banos' => $features['num_banos'] ?? 1,
                'parking' => $features['tiene_parking'] ?? 0,
                'piscina' => $features['tiene_piscina'] ?? 0,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error en predicción ML: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Excepción en predicción ML: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mapea ciudad a zona_id
     */
    protected function getZonaId(string $ciudad): int
    {
        // Mapeo de ciudades bolivianas a IDs
        $zonas = [
            'Santa Cruz' => 1,
            'La Paz' => 2,
            'Cochabamba' => 3,
            'Sucre' => 4,
            'Oruro' => 5,
            'Potosí' => 6,
            'Tarija' => 7,
            'Beni' => 8,
            'Pando' => 9,
        ];

        return $zonas[$ciudad] ?? 0;
    }

    /**
     * Guarda la predicción en el inmueble
     */
    public function guardarPrediccion(Inmueble $inmueble, array $prediccion): bool
    {
        try {
            $inmueble->update([
                'precio_sugerido_ml' => $prediccion['precio_sugerido'] ?? null,
                'precio_min_ml' => $prediccion['precio_min'] ?? null,
                'precio_max_ml' => $prediccion['precio_max'] ?? null,
                'confianza_ml' => $prediccion['confianza'] ?? null,
                'anillo' => $prediccion['anillo'] ?? null,
                'zona_especial' => $prediccion['zona_especial'] ?? null,
                'ultima_prediccion' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error guardando predicción: ' . $e->getMessage());
            return false;
        }
    }
}
