<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ResponseService;
use Illuminate\Http\Request;

/**
 * Trait HasDynamicQuery
 *
 * Proporciona funcionalidad de búsqueda dinámica para controllers.
 * Permite buscar en múltiples atributos del modelo de forma flexible.
 *
 * Requisitos:
 * - El controller debe tener una propiedad $model que sea una instancia del modelo Eloquent
 *
 * @package App\Http\Controllers\Concerns
 */
trait HasDynamicQuery
{
    /**
     * Realiza una búsqueda dinámica en el modelo basada en los parámetros de la solicitud.
     *
     * Parámetros de request:
     * - query: string - Texto a buscar
     * - perPage: int - Cantidad de resultados por página (default: 10)
     * - page: int - Número de página (default: 1)
     * - attributes: array - Atributos en los que buscar (default: ['id'])
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function query(Request $request)
    {
        try {
            // Obtener parámetros de la solicitud
            $queryStr = $request->get('query', '');
            $perPage = $request->get('perPage', 10);
            $page = $request->get('page', 1);
            $attributes = $request->get('attributes', ['id']); // Atributos por defecto

            // Obtener los atributos permitidos del modelo
            $modelAttributes = $this->model->getFillable();
            $modelAttributes[] = 'id'; // Permitir búsqueda por ID
            $modelAttributes[] = 'created_at';
            $modelAttributes[] = 'updated_at';

            // Validar que los atributos solicitados estén en la lista de atributos permitidos
            foreach ($attributes as $attribute) {
                if (!in_array($attribute, $modelAttributes)) {
                    return ResponseService::error('Atributo no permitido: ' . $attribute, '', 400);
                }
            }

            // Construir la consulta dinámica
            $query = $this->model::query();
            $first = true;

            foreach ($attributes as $attribute) {
                if ($first) {
                    // Primera condición: usar WHERE
                    if (in_array($attribute, ['created_at', 'updated_at'])) {
                        $query->whereDate($attribute, $queryStr);
                    } else {
                        $query->where($attribute, 'LIKE', '%' . $queryStr . '%');
                    }
                    $first = false;
                } else {
                    // Condiciones adicionales: usar OR WHERE
                    if (in_array($attribute, ['created_at', 'updated_at'])) {
                        $query->orWhereDate($attribute, $queryStr);
                    } else {
                        $query->orWhere($attribute, 'LIKE', '%' . $queryStr . '%');
                    }
                }
            }

            // Ejecutar la consulta y obtener resultados con eager loading selectivo
            // Verificar qué relaciones existen en el modelo antes de cargarlas
            $relations = [];
            if (method_exists($this->model, 'tipoInmueble')) {
                $relations[] = 'tipoInmueble';
            }
            if (method_exists($this->model, 'galeria')) {
                $relations[] = 'galeria';
            }

            // Cargar solo las relaciones que existen para evitar errores
            // Evita N+1 queries cuando las relaciones están disponibles
            $response = $query->when(!empty($relations), function($q) use ($relations) {
                                  return $q->with($relations);
                              })
                              ->orderBy('id', 'ASC')
                              ->get();
            $cantidad = count($response);
            $str = strval($cantidad);

            return ResponseService::success("$str datos encontrados con $queryStr", $response);
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), '', $e->getCode());
        }
    }
}
