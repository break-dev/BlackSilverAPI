<?php

namespace App\Modules\LugarExtraccionCarbon\Data;

use Illuminate\Support\Facades\DB;

class LugarExtraccionCarbonData
{
    // ====================================================================
    // JOIN: lugares asociados a un proveedor (a traves de lugar_extraccion_proveedor)
    // ====================================================================

    /**
     * Lista los lugares de extraccion ACTIVOS de un proveedor, con nombres
     * de departamento / provincia / distrito. La asociacion vive en
     * `lugar_extraccion_proveedor`; el catalogo del sitio vive en
     * `lugar_extraccion_carbon`.
     * @return array<object>
     */
    public static function get_por_proveedor(int $id_proveedor): array
    {
        $sql = '
            SELECT
                le.id AS id_lugar_extraccion,
                le.id_departamento,
                d.nombre AS departamento_nombre,
                le.id_provincia,
                p.nombre AS provincia_nombre,
                le.id_distrito,
                di.nombre AS distrito_nombre,
                le.direccion
            FROM lugar_extraccion_proveedor lp
            INNER JOIN lugar_extraccion_carbon le
                ON le.id = lp.id_lugar_extraccion_carbon
            LEFT JOIN departamento d ON d.id = le.id_departamento
            LEFT JOIN provincia p ON p.id = le.id_provincia
            LEFT JOIN distrito di ON di.id = le.id_distrito
            WHERE lp.id_proveedor = :id_proveedor
              AND IFNULL(le.estado, "Activo") = "Activo"
            ORDER BY le.direccion ASC
        ';
        return DB::select($sql, ['id_proveedor' => $id_proveedor]);
    }

    /**
     * Lista los lugares de extraccion de varios proveedores en una sola
     * consulta. Cada fila lleva el id_proveedor para que el caller pueda
     * reagrupar.
     * @param int[] $ids_proveedor
     * @return array<object>
     */
    public static function get_por_proveedores(array $ids_proveedor): array
    {
        if (empty($ids_proveedor)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids_proveedor as $i => $id) {
            $key = "id_proveedor_$i";
            $placeholders[] = ":$key";
            $params[$key] = $id;
        }
        $inClause = implode(',', $placeholders);

        $sql = "
            SELECT
                lp.id_proveedor,
                le.id AS id_lugar_extraccion,
                le.id_departamento,
                d.nombre AS departamento_nombre,
                le.id_provincia,
                p.nombre AS provincia_nombre,
                le.id_distrito,
                di.nombre AS distrito_nombre,
                le.direccion
            FROM lugar_extraccion_proveedor lp
            INNER JOIN lugar_extraccion_carbon le
                ON le.id = lp.id_lugar_extraccion_carbon
            LEFT JOIN departamento d ON d.id = le.id_departamento
            LEFT JOIN provincia p ON p.id = le.id_provincia
            LEFT JOIN distrito di ON di.id = le.id_distrito
            WHERE lp.id_proveedor IN ($inClause)
              AND IFNULL(le.estado, 'Activo') = 'Activo'
            ORDER BY lp.id_proveedor, le.direccion ASC
        ";
        return DB::select($sql, $params);
    }

    /**
     * Reemplaza el set de lugares asociados a un proveedor. Espera una lista
     * de IDs del catalogo `lugar_extraccion_carbon` y los persiste en la tabla
     * puente `lugar_extraccion_proveedor` (UNIQUE(id_proveedor, id_lugar_extraccion_carbon)
     * garantiza idempotencia).
     *
     * @param int[] $ids_lugar_extraccion_carbon
     */
    public static function set_para_proveedor(int $id_proveedor, array $ids_lugar_extraccion_carbon): void
    {
        DB::transaction(function () use ($id_proveedor, $ids_lugar_extraccion_carbon) {
            DB::table('lugar_extraccion_proveedor')
                ->where('id_proveedor', $id_proveedor)
                ->delete();

            $filas = [];
            foreach ($ids_lugar_extraccion_carbon as $id) {
                $idInt = (int) $id;
                if ($idInt <= 0) {
                    continue;
                }
                // INSERT IGNORE para no chocar con UNIQUE si llegan duplicados.
                DB::table('lugar_extraccion_proveedor')->insertOrIgnore([
                    'id_proveedor' => $id_proveedor,
                    'id_lugar_extraccion_carbon' => $idInt,
                ]);
            }
        });
    }

    // ====================================================================
    // CATALOGO: lugares_extraccion_carbon (sin proveedor)
    // ====================================================================

    /**
     * Lista el catalogo completo (activos). Pensado para alimentar el Select
     * de "Lugares de extraccion" en cualquier flujo (registro/edicion de
     * proveedor de carbon, formularios de compra, etc).
     */
    public static function get_catalogo(): array
    {
        $sql = '
            SELECT
                le.id AS id_lugar_extraccion,
                le.id_departamento,
                d.nombre AS departamento_nombre,
                le.id_provincia,
                p.nombre AS provincia_nombre,
                le.id_distrito,
                di.nombre AS distrito_nombre,
                le.direccion,
                le.estado
            FROM lugar_extraccion_carbon le
            LEFT JOIN departamento d ON d.id = le.id_departamento
            LEFT JOIN provincia p ON p.id = le.id_provincia
            LEFT JOIN distrito di ON di.id = le.id_distrito
            WHERE IFNULL(le.estado, "Activo") = "Activo"
            ORDER BY le.direccion ASC
        ';
        return DB::select($sql);
    }

    /**
     * Devuelve un lugar de extraccion por id (o null si no existe).
     */
    public static function get_por_id(int $id_lugar_extraccion): ?object
    {
        $sql = '
            SELECT
                le.id AS id_lugar_extraccion,
                le.id_departamento,
                d.nombre AS departamento_nombre,
                le.id_provincia,
                p.nombre AS provincia_nombre,
                le.id_distrito,
                di.nombre AS distrito_nombre,
                le.direccion,
                le.estado
            FROM lugar_extraccion_carbon le
            LEFT JOIN departamento d ON d.id = le.id_departamento
            LEFT JOIN provincia p ON p.id = le.id_provincia
            LEFT JOIN distrito di ON di.id = le.id_distrito
            WHERE le.id = :id
            LIMIT 1
        ';
        return DB::selectOne($sql, ['id' => $id_lugar_extraccion]);
    }

    /**
     * Inserta un nuevo sitio en el catalogo. Direccion es obligatoria; los
     * ids de ubigeo son opcionales.
     */
    public static function insertar_catalogo(
        ?int $id_departamento,
        ?int $id_provincia,
        ?int $id_distrito,
        string $direccion
    ): int {
        return DB::table('lugar_extraccion_carbon')->insertGetId([
            'id_departamento' => $id_departamento && $id_departamento > 0 ? $id_departamento : null,
            'id_provincia' => $id_provincia && $id_provincia > 0 ? $id_provincia : null,
            'id_distrito' => $id_distrito && $id_distrito > 0 ? $id_distrito : null,
            'direccion' => trim($direccion),
            'estado' => 'Activo',
        ]);
    }

    /**
     * Actualiza un sitio del catalogo. Direccion es obligatoria; los ids de
     * ubigeo son opcionales.
     */
    public static function actualizar_catalogo(
        int $id_lugar_extraccion,
        ?int $id_departamento,
        ?int $id_provincia,
        ?int $id_distrito,
        string $direccion
    ): int {
        return DB::table('lugar_extraccion_carbon')
            ->where('id', $id_lugar_extraccion)
            ->update([
                'id_departamento' => $id_departamento && $id_departamento > 0 ? $id_departamento : null,
                'id_provincia' => $id_provincia && $id_provincia > 0 ? $id_provincia : null,
                'id_distrito' => $id_distrito && $id_distrito > 0 ? $id_distrito : null,
                'direccion' => trim($direccion),
            ]);
    }

    /**
     * Desactivar (soft delete) un sitio del catalogo. Si tiene asociaciones
     * activas en `lugar_extraccion_proveedor`, estas siguen existiendo pero
     * el join en get_por_proveedor las filtra por estado=Activo.
     */
    public static function eliminar_catalogo(int $id_lugar_extraccion): int
    {
        return DB::table('lugar_extraccion_carbon')
            ->where('id', $id_lugar_extraccion)
            ->update(['estado' => 'Inactivo']);
    }
}
