<?php

namespace App\Http\Controllers;

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

class InmuebleController extends Controller
{
    public Inmueble $model;
    public $rutaVisita = 'Inmueble';
    public function __construct()
    {
        $this->model = new Inmueble();
    }

    public function query(Request $request)
    {
        try {
            $queryStr = $request->get('query', '');
            $perPage = $request->get('perPage', 10);
            $page = $request->get('page', 1);
            $attributes = $request->get('attributes', ['id']); // Atributos por defecto

            // Obtener los atributos del modelo
            $modelAttributes = $this->model->getFillable();
            $modelAttributes[] = 'id';
            $modelAttributes[] = 'created_at';
            $modelAttributes[] = 'updated_at';

            // Validar que los atributos estén en la lista de atributos permitidos
            foreach ($attributes as $attribute) {
                if (!in_array($attribute, $modelAttributes)) {
                    return ResponseService::error('Atributo no permitido: ' . $attribute, '', 400);
                }
            }

            // Construir la consulta dinámica
            $query = $this->model::query();

            if (!empty($queryStr)) {
                $query->where(function ($q) use ($attributes, $queryStr) {
                    foreach ($attributes as $i => $attribute) {
                        if (in_array($attribute, ['created_at', 'updated_at'])) {
                            $method = $i === 0 ? 'whereDate' : 'orWhereDate';
                            $q->$method($attribute, $queryStr);
                        } else {
                            $method = $i === 0 ? 'where' : 'orWhere';
                            $q->$method($attribute, 'LIKE', '%' . $queryStr . '%');
                        }
                    }
                });
            }
            //$response = $query->orderBy('id', 'ASC')->paginate($perPage, ['*'], 'page', $page);
            $response = $query->orderBy('id', 'ASC')->get();
            // cantidad de datos encontrados
            $response->total = $response->count();
            return ResponseService::success(
                "{$response->total} datos encontrados con {$queryStr}",
                $response
            );
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), '', $e->getCode());
        }
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
                'detalle' => $request->detalle,
                'num_habitacion' => $request->num_habitacion,
                'num_piso' => $request->num_piso,
                'precio' => $request->precio,
                'isOcupado' => $request->isOcupado,
                'tipo_inmueble_id' => $request->tipo_inmueble_id, // Relación con tipo_inmuebles
                'accesorios' => json_encode($request->accesorios ?? []),
                'servicios_basicos' => json_encode($request->servicios_basicos ?? [])
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
            $inmuebles = $this->model::where('user_id', $userId)->get();
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
}
