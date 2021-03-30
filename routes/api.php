<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


// Barang
Route::group(['prefix' => 'barang'], function () {
    //POST
    Route::post('/store', 'BarangController@store');
    Route::post('/gudang/store', 'BarangController@gudangStore');
    Route::post('/harga/store', 'BarangController@hargaStore');
    Route::post('/jenis/store', 'BarangController@jenisStore');
    Route::post('/satuan/store', 'BarangController@satuanStore');
    Route::post('/merek/store', 'BarangController@merekStore');
    //GET
    Route::get('/', 'BarangController@index');
    Route::get('/detail/{id}', 'BarangController@show');
    Route::get('/gudang', 'BarangController@gudang');
    Route::get('/satuan', 'BarangController@satuan');
    Route::get('/jenis', 'BarangController@jenis');
    Route::get('/merek', 'BarangController@merek');
    //DESTROY
    Route::delete('/{id}', 'BarangController@destroy');
    Route::delete('/harga/{id}', 'BarangController@hargaDestroy');
});

// Barang
Route::group(['prefix' => 'kontak'], function () {
    //POST
    Route::post('/store', 'KontakController@store');
    //GET
    Route::get('/', 'KontakController@index');
    Route::get('/{id}', 'KontakController@show');
    //DESTROY
    Route::delete('/{id}', 'BarangController@destroy');
});
