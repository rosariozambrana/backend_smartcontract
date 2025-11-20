<?php

namespace App\Http\Controllers;

use App\Services\ML\MLPredictionService;
use App\Services\ResponseService;
use App\Models\Inmueble;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    protected $mlService;

    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Predecir precio para nuevo inmueble (sin guardar)
     * POST /api/app/predecir-precio
     */
    public function predecirPrecio(Request $request)
    {
        try {
            $features = [
                'metros_cuadrados' => $request->input('metros_cuadrados', 70),
                'num_habitacion' => $request->input('num_habitacion', 1),
                'num_banos' => $request->input('num_banos', 1),
                'ciudad' => $request->input('ciudad', 'Santa Cruz'),
                'tiene_parking' => $request->input('tiene_parking', 0),
                'tiene_piscina' => $request->input('tiene_piscina', 0),
            ];

            $prediccion = $this->mlService->predecirPrecio($features);

            if (!$prediccion) {
                return ResponseService::error(
                    'Servicio ML no disponible',
                    'El servicio de predicción está temporalmente fuera de línea',
                    503
                );
            }

            return ResponseService::success('Predicción generada', $prediccion);

        } catch (\Exception $e) {
            return ResponseService::error('Error al predecir precio', $e->getMessage());
        }
    }

    /**
     * Predecir y guardar precio para inmueble existente
     * POST /api/app/inmuebles/{inmueble}/predecir
     */
    public function predecirYGuardar(Inmueble $inmueble)
    {
        try {
            $features = [
                'metros_cuadrados' => 70, // Extraer de inmueble cuando agregues el campo
                'num_habitacion' => $inmueble->num_habitacion,
                'num_banos' => 1, // Extraer de inmueble cuando agregues el campo
                'ciudad' => $inmueble->ciudad,
                'tiene_parking' => 0, // Extraer de accesorios
                'tiene_piscina' => 0, // Extraer de accesorios
            ];

            $prediccion = $this->mlService->predecirPrecio($features);

            if (!$prediccion) {
                return ResponseService::error('Servicio ML no disponible', '', 503);
            }

            $this->mlService->guardarPrediccion($inmueble, $prediccion);

            return ResponseService::success('Predicción guardada', [
                'inmueble' => $inmueble->fresh(),
                'prediccion' => $prediccion,
            ]);

        } catch (\Exception $e) {
            return ResponseService::error('Error al predecir', $e->getMessage());
        }
    }

    /**
     * Verificar estado del servicio ML
     * GET /api/app/ml/status
     */
    public function status()
    {
        $isAvailable = $this->mlService->checkHealth();

        return ResponseService::success('Estado del servicio ML', [
            'disponible' => $isAvailable,
            'url' => env('ML_SERVICE_URL'),
            'timestamp' => now(),
        ]);
    }
}
