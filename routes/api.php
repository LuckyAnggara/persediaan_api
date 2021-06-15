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

// AUTH
Route::group(['prefix' => 'auth'], function () {
    //POST
    Route::post('/login', 'AuthController@login');
    Route::get('/cek', 'TransaksiPenjualanController@index2');
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
    Route::post('/store', 'PersediaanController@store');
    //GET
    Route::get('/', 'PersediaanController@index');
    Route::get('/penyesuaian/cabang/{cabang_id}', 'PersediaanController@daftarPenyesuaian');
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
    Route::post('/retur', 'TransaksiPenjualanController@retur');
    //PUT
    Route::put('/edit/{id}', 'TransaksiPenjualanController@update');
    //GET
    Route::get('/cabang/{cabang}/awal/{dd}/akhir/{ddd}', 'TransaksiPenjualanController@index');
    Route::get('/detail/barang/{id}/cabang/{cabang}', 'TransaksiPenjualanController@getDetailTransaksiByBarang');
    //DELETE
    Route::delete('/delete/{id}', 'TransaksiPenjualanController@destroy');
});

// Transaksi Pembelian
Route::group(['prefix' => 'pembelian'], function () {
    //POST
    Route::post('/store', 'TransaksiPembelianController@store');
    Route::post('/retur', 'TransaksiPembelianController@retur');
    //GET
    // Route::get('/', 'TransaksiPembelianController@index2');
    Route::get('/cabang/{cabang}/awal/{dd}/akhir/{ddd}', 'TransaksiPembelianController@index');
    Route::get('/detail/barang/{id}/cabang/{cabang}', 'TransaksiPembelianController@getDetailTransaksiByBarang');
    //DELETE
    Route::delete('/delete/{id}', 'TransaksiPembelianController@destroy');
});

// Kepegawaian
Route::group(['prefix' => 'pegawai'], function () {
    //GET
    Route::get('/', 'PegawaiController@index');
    Route::get('/presensi/{id}/bulan/{m}', 'PegawaiController@presensi');
    Route::get('/jabatandivisicabang', 'PegawaiController@jabatandivisicabang');
    //PUT
    Route::put('/edit/{id}', 'PegawaiController@update');
     //POST
     Route::post('/store', 'PegawaiController@store');
});

// Presensi
Route::group(['prefix' => 'presensi'], function () {
    //GET
    Route::get('/{date}', 'PresensiController@index');
    Route::get('/{id}/bulan/{m}', 'PresensiController@presensiPegawai');
     //POST
     Route::put('/store/masuk/', 'PresensiController@storeMasuk');
     Route::get('/store/keluar/{id}', 'PresensiController@storeKeluar');
});

