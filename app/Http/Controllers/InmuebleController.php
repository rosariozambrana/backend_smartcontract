<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasDynamicQuery;
use App\Models\Accesorio;
use App\Models\GaleriaInmueble;
use App\Models\Inmueble;
use App\Http\Requests\StoreInmuebleRequest;
use App\Http\Requests\UpdateInmuebleRequest;
use App\Services\PermissionService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class InmuebleController extends Controller
{
    use HasDynamicQuery;

    public Inmueble $model;
    public $rutaVisita = 'Inmueble';
    public function __construct()
    {
        $this->model = new Inmueble();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render($this->rutaVisita . '/Index', array_merge([
            'listado' => $this->model::all(),
        ], PermissionService::getPermissions($this->rutaVisita)));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permiso = strtolower($this->rutaVisita);
        if (!Auth::user()->can($permiso.'-create')) {
            abort(403);
        }
        return Inertia::render($this->rutaVisita . '/CreateUpdate', array_merge([
            'isCreate' => true
        ], PermissionService::getPermissions($permiso)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInmuebleRequest $request)
    {
        try {
            $data = $this->model::create([
                'user_id' => $request->user_id,
                'nombre' => $request->nombre,
                'direccion' => $request->direccion,
                'ciudad' => $request->ciudad,
                'pais' => $request->pais ?? 'Bolivia',
                'detalle' => $request->detalle,
                'num_habitacion' => $request->num_habitacion,
                'num_piso' => $request->num_piso,
                'precio' => $request->precio,
                'isOcupado' => $request->isOcupado,
                'tipo_inmueble_id' => $request->tipo_inmueble_id, // Relación con tipo_inmuebles
                'accesorios' => json_encode($request->accesorios ?? []),
                'servicios_basicos' => json_encode($request->servicios_basicos ?? []),
                // Campos ML
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'metros_cuadrados' => $request->metros_cuadrados,
                'num_banos' => $request->num_banos,
                'anillo' => $request->anillo,
                'zona_especial' => $request->zona_especial,
            ]);
            return ResponseService::success('Registro guardado correctamente', $data);
        } catch (\Exception $e) {
            return ResponseService::error('Error al guardar el registro', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Inmueble $inmueble)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inmueble $inmueble)
    {
        $permiso = strtolower($this->rutaVisita);
        if (!Auth::user()->can($permiso.'-edit')) {
            abort(403);
        }
        return Inertia::render($this->rutaVisita . '/CreateUpdate', array_merge([
            'isCreate' => false,
            'model' => $inmueble,
        ], PermissionService::getPermissions($permiso)));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInmuebleRequest $request, Inmueble $inmueble)
    {
        try {
            $inmueble->update($request->all());
            return ResponseService::success('Registro actualizado correctamente', $inmueble);
        } catch (\Exception $e) {
            return ResponseService::error('Error al actualizar el registro', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inmueble $inmueble)
    {
        try {
            // eliminar las imágenes asociadas al inmueble si existem
            $galeria = GaleriaInmueble::where('inmueble_id', $inmueble->id)->get();
            foreach ($galeria as $imagen) {
                if ($imagen->photo_path && \Storage::disk('public')->exists($imagen->photo_path)) {
                    \Storage::disk('public')->delete($imagen->photo_path);
                }
                $imagen->delete();
            }
            // eliminar los devices asociados al inmueble
            $inmueble->devices()->detach();
            // eliminar solicitudes de alquiler asociadas al inmueble
            $inmueble->solicitudesAlquiler()->delete();
            // eliminar el inmueble
            $inmueble->delete();
            return ResponseService::success('Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ResponseService::error('Error al eliminar el registro', $e->getMessage());
        }
    }
    // get inmuebles por propietario
    public function getInmueblesByPropietario($userId)
    {
        try {
            $inmuebles = $this->model::where('user_id', $userId)->with('galeria')->get();
            return ResponseService::success('Inmuebles encontrados', $inmuebles);
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), '', $e->getCode());
        }
    }

    // get inmuebles por id
    public function getInmuebleById(Inmueble $inmueble)
    {
        try {
            return ResponseService::success('Inmueble encontrado', $inmueble);
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), '', $e->getCode());
        }
    }
    // subir imagen del inmueble
    public function subirImagen(Request $request)
    {
        try {
            $inmuebleId = $request->get('inmueble_id');
            if (!$inmuebleId) {
                return ResponseService::error('ID de inmueble no proporcionado', '', 400);
            }

            if (!$request->hasFile('file')) {
                return ResponseService::error('No se ha proporcionado una imagen', '', 400);
            }

            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'imagen_' . $inmuebleId . '.' . $extension;
            $path = $file->store('imagenes/inmuebles', 'public');

            $galeriaInmueble = new \App\Models\GaleriaInmueble();
            $galeriaInmueble->inmueble_id = $inmuebleId;
            $galeriaInmueble->photo_path = $path;
            $galeriaInmueble->save();

            return ResponseService::success('Imagen subida correctamente', ['path' => $path]);
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), '', $e->getCode());
        }
    }
    // get galeria de imagenes del inmueble
    public function getGaleriaImagenes(Inmueble $inmueble)
    {
        try {
            $galeria_imagenes = GaleriaInmueble::where('inmueble_id', $inmueble->id)->get(); // Asumiendo que tienes una relación definida en el modelo Inmueble
            return ResponseService::success('Galería de imágenes obtenida correctamente', $galeria_imagenes);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener la galería de imágenes', $e->getMessage());
        }
    }
    public function firstImage(Inmueble $inmueble){
        try {
            $galeria_imagenes = GaleriaInmueble::where('inmueble_id', $inmueble->id)->first(); // Asumiendo que tienes una relación definida en el modelo Inmueble
            return ResponseService::success('imágenes obtenida correctamente', $galeria_imagenes);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener la galería de imágenes', $e->getMessage());
        }
    }
    // eliminar imagen del inmueble
    public function eliminarImagen(Request $request)
    {
        try {
            $inmuebleId = $request->get('inmueble_id');
            if (!$inmuebleId) {
                return ResponseService::error('ID de inmueble no proporcionado', '', 400);
            }

            $inmueble = $this->model::find($inmuebleId);
            if (!$inmueble) {
                return ResponseService::error('Inmueble no encontrado', '', 404);
            }

            if ($inmueble->imagen) {
                // Eliminar el archivo de imagen del almacenamiento
                \Storage::disk('public')->delete($inmueble->imagen);
                $inmueble->imagen = null; // Limpiar el campo de imagen
                $inmueble->save();
                return ResponseService::success('Imagen eliminada correctamente');
            } else {
                return ResponseService::error('No hay imagen para eliminar', '', 400);
            }
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), '', $e->getCode());
        }
    }

    // Buscar inmuebles cercanos a una ubicación
    public function getInmueblesCercanos(Request $request)
    {
        try {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radio = $request->input('radio', 5); // Radio en kilómetros, por defecto 5km

            if (!$latitude || !$longitude) {
                return ResponseService::error('Latitud y longitud son requeridos', '', 400);
            }

            // Fórmula Haversine para calcular distancia entre dos puntos GPS
            // 6371 es el radio de la Tierra en kilómetros
            $inmuebles = $this->model::selectRaw("
                    *,
                    (6371 * acos(cos(radians(?))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(latitude)))) AS distancia
                ", [$latitude, $longitude, $latitude])
                ->having('distancia', '<=', $radio)
                ->orderBy('distancia', 'asc')
                ->with(['user', 'tipoInmueble', 'imagenes'])
                ->get();

            return ResponseService::success('Inmuebles cercanos encontrados', $inmuebles);
        } catch (\Exception $e) {
            return ResponseService::error('Error al buscar inmuebles cercanos', $e->getMessage());
        }
    }

    // Obtener todos los inmuebles con ubicación para mostrar en mapa
    public function getInmueblesParaMapa()
    {
        try {
            $inmuebles = $this->model::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('isOcupado', false)
                ->with(['user', 'tipoInmueble'])
                ->select('id', 'nombre', 'direccion', 'latitude', 'longitude', 'precio', 'ciudad', 'num_habitacion')
                ->get();

            return ResponseService::success('Inmuebles para mapa obtenidos correctamente', $inmuebles);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener inmuebles para mapa', $e->getMessage());
        }
    }

    // Buscar inmuebles por ciudad
    public function getInmueblesPorCiudad($ciudad)
    {
        try {
            $inmuebles = $this->model::where('ciudad', 'LIKE', "%{$ciudad}%")
                ->where('isOcupado', false)
                ->with(['user', 'tipoInmueble', 'imagenes'])
                ->get();

            return ResponseService::success('Inmuebles encontrados en ' . $ciudad, $inmuebles);
        } catch (\Exception $e) {
            return ResponseService::error('Error al buscar inmuebles por ciudad', $e->getMessage());
        }
    }

    /**
     * Obtener datos para mapa de calor
     * GET /api/app/inmuebles/mapa-calor
     */
    public function getHeatmapData()
    {
        try {
            $zonas = $this->model::select(
                DB::raw('ROUND(latitude, 3) as lat'),
                DB::raw('ROUND(longitude, 3) as lon'),
                DB::raw('AVG(precio) as precio_promedio'),
                DB::raw('COUNT(*) as total_inmuebles'),
                DB::raw('MIN(precio) as precio_min'),
                DB::raw('MAX(precio) as precio_max')
            )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('isOcupado', false)
            ->groupBy('lat', 'lon')
            ->having('total_inmuebles', '>=', 2)
            ->orderBy('precio_promedio', 'desc')
            ->get();

            return ResponseService::success('Datos de mapa de calor obtenidos', $zonas);
        } catch (\Exception $e) {
            return ResponseService::error('Error al obtener datos del mapa de calor', $e->getMessage());
        }
    }

    /**
     * Predice el precio de un inmueble usando ML
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function predictPrice(Request $request)
    {
        try {
            $validated = $request->validate([
                'latitud' => 'required|numeric',
                'longitud' => 'required|numeric',
                'metros_cuadrados' => 'required|numeric|min:10',
                'num_habitacion' => 'required|integer|min:1',
                'num_banos' => 'required|integer|min:1',
                'tiene_parking' => 'required|boolean',
                'tiene_piscina' => 'required|boolean',
            ]);

            $mlService = new \App\Services\ML\MLPredictionService();

            // Verificar que el servicio ML esté disponible
            if (!$mlService->checkHealth()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El servicio ML no está disponible. Asegúrate de que Python ML esté ejecutándose en puerto 5000.'
                ], 503);
            }

            $prediccion = $mlService->predecirPrecio($validated);

            if ($prediccion) {
                return response()->json([
                    'success' => true,
                    'data' => $prediccion
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la predicción'
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al predecir precio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica el estado del servicio ML
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mlStatus()
    {
        try {
            $mlService = new \App\Services\ML\MLPredictionService();
            $isHealthy = $mlService->checkHealth();

            return response()->json([
                'success' => true,
                'status' => $isHealthy ? 'online' : 'offline',
                'message' => $isHealthy ? 'Servicio ML disponible' : 'Servicio ML no disponible'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
