<?php

namespace App\Http\Requests;

use App\Services\PermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreInmuebleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'nombre' => 'required|string|max:255|unique:inmuebles,nombre',
            'direccion' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:100',
            'pais' => 'nullable|string|max:100',
            'detalle' => 'nullable|string|max:1000',
            'num_habitacion' => 'nullable|string|max:10',
            'num_piso' => 'nullable|string|max:10',
            'precio' => 'required|numeric|min:0',
            'isOcupado' => 'nullable|boolean',
            'tipo_inmueble_id' => 'nullable|exists:tipo_inmuebles,id',
            'accesorios' => 'nullable|array',
            'servicios_basicos' => 'nullable|array',
        ];
    }
}
