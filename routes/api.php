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

// Cabang
Route::group(['prefix' => 'auth'], function () {
    //POST
    Route::post('/login', 'AuthController@login');
});

// Cabang
Route::group(['prefix' => 'cabang'], function () {
    //GET
    Route::get('/', 'CabangController@index');
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

// Persediaan
Route::group(['prefix' => 'persediaan'], function () {
    //POST
    //GET
    Route::get('/', 'PersediaanController@index');
    Route::get('/{id}', 'PersediaanController@show');
    //DESTROY
    Route::delete('/{id}', 'BarangController@destroy');
});

// Kontak
Route::group(['prefix' => 'kontak'], function () {
    //POST
    Route::post('/store', 'KontakController@store');
    //GET
    Route::get('/', 'KontakController@index');
    // Route::get('/pelanggan', 'KontakController@pelanggan');
    Route::get('/{id}', 'KontakController@show');
    //DESTROY
    Route::delete('/{id}', 'BarangController@destroy');
});

// Bank
Route::group(['prefix' => 'bank'], function () {
    //GET
    Route::get('/', 'BankController@index');
});

// Transaksi Penjualan
Route::group(['prefix' => 'penjualan'], function () {
    //POST
    Route::post('/store', 'TransaksiPenjualanController@store');
    Route::get('/store2', 'TransaksiPenjualanController@postJurnal');
    //GET
    Route::get('/{dd}/{ddd}', 'TransaksiPenjualanController@index');
    Route::get('/detail/barang/{id}', 'TransaksiPenjualanController@getDetailTransaksiByBarang');
});

// Transaksi Pembelian
Route::group(['prefix' => 'pembelian'], function () {
    //POST
    Route::post('/store', 'TransaksiPembelianController@store');
    Route::get('/store2', 'TransaksiPembelianController@postJurnal');
    //GET
    // Route::get('/', 'TransaksiPembelianController@index2');
    Route::get('/{dd}/{ddd}', 'TransaksiPembelianController@index');
    Route::get('/detail/barang/{id}', 'TransaksiPembelianController@getDetailTransaksiByBarang');
});
