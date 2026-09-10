<?php

namespace App\Modules\LugarExtraccionCarbon\Controllers;

use App\Modules\LugarExtraccionCarbon\Services\LugarExtraccionCarbonService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LugarExtraccionCarbonController
{
    // ====================================================================
    // JOIN: lugares por proveedor.
    // ====================================================================

    public function get_por_proveedor(int $id_proveedor): JsonResponse
    {
        return response()->json(
            LugarExtraccionCarbonService::get_por_proveedor($id_proveedor)
        );
    }

    /**
     * Body esperado: { lugares: [id_lugar_extraccion_carbon, ...] }
     */
    public function set_para_proveedor(Request $request, int $id_proveedor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lugares' => 'present|array',
            'lugares.*' => 'integer|min:1',
        ], [
            'lugares.present' => 'El campo lugares es requerido',
            'lugares.array' => 'lugares debe ser un arreglo',
            'lugares.*.integer' => 'Cada id de lugar debe ser entero',
            'lugares.*.min' => 'Cada id de lugar debe ser mayor a 0',
        ]);

        if ($validator->fails()) {
            return response()->json(ApiResponse::error($validator->errors()->first()), 422);
        }

        $ids = array_map('intval', (array) $request->input('lugares', []));

        return response()->json(
            LugarExtraccionCarbonService::set_para_proveedor($id_proveedor, $ids)
        );
    }

    // ====================================================================
    // CATALOGO: CRUD sobre lugar_extraccion_carbon.
    // ====================================================================

    public function get_catalogo(): JsonResponse
    {
        return response()->json(LugarExtraccionCarbonService::get_catalogo());
    }

    public function get_item(int $id_lugar_extraccion): JsonResponse
    {
        return response()->json(LugarExtraccionCarbonService::get_item($id_lugar_extraccion));
    }

    /**
     * Body esperado: { id_departamento?, id_provincia?, id_distrito?, direccion }.
     * direccion obligatorio; los ids de ubigeo son opcionales.
     */
    public function crear_item(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_departamento' => 'nullable|integer|min:1',
            'id_provincia' => 'nullable|integer|min:1',
            'id_distrito' => 'nullable|integer|min:1',
            'direccion' => 'required|string|max:255',
        ], [
            'direccion.required' => 'La direccion es obligatoria',
        ]);

        if ($validator->fails()) {
            return response()->json(ApiResponse::error($validator->errors()->first()), 422);
        }

        return response()->json(LugarExtraccionCarbonService::insertar_item(
            $request->input('id_departamento') !== null ? (int) $request->input('id_departamento') : null,
            $request->input('id_provincia') !== null ? (int) $request->input('id_provincia') : null,
            $request->input('id_distrito') !== null ? (int) $request->input('id_distrito') : null,
            (string) $request->input('direccion'),
        ));
    }

    public function actualizar_item(Request $request, int $id_lugar_extraccion): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_departamento' => 'nullable|integer|min:1',
            'id_provincia' => 'nullable|integer|min:1',
            'id_distrito' => 'nullable|integer|min:1',
            'direccion' => 'required|string|max:255',
        ], [
            'direccion.required' => 'La direccion es obligatoria',
        ]);

        if ($validator->fails()) {
            return response()->json(ApiResponse::error($validator->errors()->first()), 422);
        }

        return response()->json(LugarExtraccionCarbonService::actualizar_item(
            $id_lugar_extraccion,
            $request->input('id_departamento') !== null ? (int) $request->input('id_departamento') : null,
            $request->input('id_provincia') !== null ? (int) $request->input('id_provincia') : null,
            $request->input('id_distrito') !== null ? (int) $request->input('id_distrito') : null,
            (string) $request->input('direccion'),
        ));
    }

    public function eliminar_item(int $id_lugar_extraccion): JsonResponse
    {
        return response()->json(LugarExtraccionCarbonService::eliminar_item($id_lugar_extraccion));
    }
}
