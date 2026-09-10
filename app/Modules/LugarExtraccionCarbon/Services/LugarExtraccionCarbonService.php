<?php

namespace App\Modules\LugarExtraccionCarbon\Services;

use App\Modules\LugarExtraccionCarbon\Data\LugarExtraccionCarbonData;
use App\Shared\Responses\ApiResponse;

class LugarExtraccionCarbonService
{
    // ====================================================================
    // JOIN: lugares por proveedor (vista que une el catalogo con la tabla
    // puente lugar_extraccion_proveedor).
    // ====================================================================

    /**
     * Lista los lugares de extraccion asociados a un proveedor.
     */
    public static function get_por_proveedor(int $id_proveedor): array
    {
        $data = LugarExtraccionCarbonData::get_por_proveedor($id_proveedor);
        return ApiResponse::success($data, 'Lugares de extraccion del proveedor');
    }

    /**
     * Reemplaza el set de lugares asociados a un proveedor. Espera una lista
     * de IDs del catalogo `lugar_extraccion_carbon`.
     * @param int[] $ids_lugar_extraccion_carbon
     */
    public static function set_para_proveedor(int $id_proveedor, array $ids_lugar_extraccion_carbon): array
    {
        LugarExtraccionCarbonData::set_para_proveedor($id_proveedor, $ids_lugar_extraccion_carbon);
        $data = LugarExtraccionCarbonData::get_por_proveedor($id_proveedor);
        return ApiResponse::success($data, 'Lugares de extraccion actualizados');
    }

    // ====================================================================
    // CATALOGO: CRUD sobre lugar_extraccion_carbon (sin proveedor).
    // ====================================================================

    public static function get_catalogo(): array
    {
        $data = LugarExtraccionCarbonData::get_catalogo();
        return ApiResponse::success($data, 'Catalogo de lugares de extraccion');
    }

    public static function get_item(int $id_lugar_extraccion): array
    {
        $row = LugarExtraccionCarbonData::get_por_id($id_lugar_extraccion);
        if (!$row) {
            return ApiResponse::error('Lugar de extraccion no encontrado');
        }
        return ApiResponse::success($row, 'Lugar de extraccion');
    }

    /**
     * Inserta un nuevo sitio en el catalogo. Direccion obligatoria; ubigeo
     * opcional.
     */
    public static function insertar_item(
        ?int $id_departamento,
        ?int $id_provincia,
        ?int $id_distrito,
        string $direccion
    ): array {
        $direccion = trim($direccion);
        if ($direccion === '') {
            return ApiResponse::error('La direccion es obligatoria');
        }

        $id = LugarExtraccionCarbonData::insertar_catalogo(
            $id_departamento,
            $id_provincia,
            $id_distrito,
            $direccion,
        );

        $item = LugarExtraccionCarbonData::get_por_id($id);
        return ApiResponse::success($item, 'Lugar de extraccion registrado correctamente');
    }

    public static function actualizar_item(
        int $id_lugar_extraccion,
        ?int $id_departamento,
        ?int $id_provincia,
        ?int $id_distrito,
        string $direccion
    ): array {
        $existe = LugarExtraccionCarbonData::get_por_id($id_lugar_extraccion);
        if (!$existe) {
            return ApiResponse::error('Lugar de extraccion no encontrado');
        }

        $direccion = trim($direccion);
        if ($direccion === '') {
            return ApiResponse::error('La direccion es obligatoria');
        }

        LugarExtraccionCarbonData::actualizar_catalogo(
            $id_lugar_extraccion,
            $id_departamento,
            $id_provincia,
            $id_distrito,
            $direccion,
        );

        $item = LugarExtraccionCarbonData::get_por_id($id_lugar_extraccion);
        return ApiResponse::success($item, 'Lugar de extraccion actualizado correctamente');
    }

    public static function eliminar_item(int $id_lugar_extraccion): array
    {
        $existe = LugarExtraccionCarbonData::get_por_id($id_lugar_extraccion);
        if (!$existe) {
            return ApiResponse::error('Lugar de extraccion no encontrado');
        }

        LugarExtraccionCarbonData::eliminar_catalogo($id_lugar_extraccion);
        $item = LugarExtraccionCarbonData::get_por_id($id_lugar_extraccion);
        return ApiResponse::success($item, 'Lugar de extraccion eliminado correctamente');
    }
}
