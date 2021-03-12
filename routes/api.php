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


// PELANGGAN
Route::group(['prefix' => 'barang'], function () {
    //POST
    Route::post('/store', 'BarangController@store');
    //GET
    Route::get('/', 'BarangController@index');
    Route::get('/{id}', 'BarangController@show');
    Route::get('/satuan', 'BarangController@satuanList');
    Route::get('/jenis', 'BarangController@jenisList');
    Route::get('/merek', 'BarangController@merekList');
    //DESTROY
    Route::delete('/{id}', 'BarangController@destroy');
  });
