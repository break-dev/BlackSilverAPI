<?php
namespace App\Services;

use App\Data\ProveedoresData;
use App\Shared\Enums\_Generic\EstadoBase;
use App\Shared\Enums\_Generic\TipoEntidad;
use App\Shared\Responses\ApiResponse;

class ProveedoresService
{
    /**
     * Listar almacenes.
     */
    public static function get_proveedores(
        ?int $id_proveedor = null,
        ?EstadoBase $estado = null,
        ?TipoEntidad $tipoEntidad = null,
        ?bool $paraMantenimiento = null,
        ?bool $paraTransporte = null,
        ?bool $paraCarbon = null
    ) {
        $empleados = ProveedoresData::get_proveedores(
            id_proveedor: $id_proveedor,
            estado: $estado,
            tipoEntidad: $tipoEntidad,
            paraMantenimiento: $paraMantenimiento,
            paraTransporte: $paraTransporte,
            paraCarbon: $paraCarbon
        );

        return ApiResponse::success($empleados);
    }

    /**
     * Registrar proveedor.
     *
     * `codigoReinfo` y `contratos` solo se persisten cuando `paraCarbon=true`.
     * Para proveedores logisticos se ignoran silenciosamente (defensa).
     *
     * @param array $contratos Listado de archivos del contrato (IArchivo[]).
     */
    public static function crear_proveedor(
        TipoEntidad $tipoEntidad,
        string $razonSocial,
        bool $paraMantenimiento,
        bool $paraTransporte = false,
        ?string $dni = null,
        ?string $ruc = null,
        ?string $direccion = null,
        ?string $telefono = null,
        ?string $correo = null,
        bool $paraCarbon = false,
        ?string $codigoReinfo = null,
        array $contratos = [],
        ?bool $return_object = false
    ): array {
        // verificamos que no exista
        $ya_existe = ProveedoresData::ya_existe(dni: $dni, ruc: $ruc, razonSocial: $razonSocial);
        if ($ya_existe) {
            return ApiResponse::error("El proveedor ya existe");
        }

        $codigoReinfoPersisted = $paraCarbon
            ? ($codigoReinfo === '' ? null : $codigoReinfo)
            : null;
        $contratosJson = ($paraCarbon && !empty($contratos))
            ? json_encode($contratos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        $id = ProveedoresData::crear_proveedor(
            tipoEntidad: $tipoEntidad,
            razonSocial: $razonSocial,
            paraMantenimiento: $paraMantenimiento,
            paraTransporte: $paraTransporte,
            dni: $dni,
            ruc: $ruc,
            direccion: $direccion,
            telefono: $telefono,
            correo: $correo,
            paraCarbon: $paraCarbon,
            codigoReinfo: $codigoReinfoPersisted,
            contratosJson: $contratosJson,
        );

        if ($return_object) {
            $new_proveedor = ProveedoresData::get_proveedores(id_proveedor: $id);
            return ApiResponse::success($new_proveedor, "Proveedor registrado correctamente");
        }

        return ApiResponse::success($id, "Proveedor registrado correctamente");
    }
}