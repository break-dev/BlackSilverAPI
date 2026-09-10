<?php

use App\Modules\LugarExtraccionCarbon\Controllers\LugarExtraccionCarbonController;
use Illuminate\Support\Facades\Route;

// JOIN: lugares asociados a un proveedor (vista desde lugar_extraccion_proveedor
// hacia el catalogo lugar_extraccion_carbon).
Route::middleware('auth.jwt.custom')->group(function () {
    Route::prefix('proveedores')->controller(LugarExtraccionCarbonController::class)->group(function () {
        Route::get('{id_proveedor}/lugares-extraccion', 'get_por_proveedor');
        Route::put('{id_proveedor}/lugares-extraccion', 'set_para_proveedor');
    });
});

// CATALOGO: CRUD sobre lugar_extraccion_carbon (sin proveedor).
Route::middleware('auth.jwt.custom')->group(function () {
    Route::prefix('lugar-extraccion-carbon')->controller(LugarExtraccionCarbonController::class)->group(function () {
        Route::get('/', 'get_catalogo');
        Route::post('/', 'crear_item');
        Route::get('{id_lugar_extraccion}', 'get_item');
        Route::put('{id_lugar_extraccion}', 'actualizar_item');
        Route::delete('{id_lugar_extraccion}', 'eliminar_item');
    });
});
