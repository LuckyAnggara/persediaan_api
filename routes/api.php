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
    Route::get('/logout', 'AuthController@logout');
});

// DASHBOARD
Route::group(['prefix' => 'dashboard-cabang'], function () {
    //GET
    Route::get('/omset-harian', 'DashboardCabangController@omsetHarian');
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
    Route::get('/aw', 'BarangController@aw');
    Route::get('/detail', 'BarangController@show');
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
    Route::post('/store', 'PersediaanController@storeOpname');
    Route::post('/transfer', 'PersediaanController@storeTransfer');
    //GET
    Route::get('/cabang/{cabang_id}/{gudang_id}', 'PersediaanController@index');
    Route::get('/penyesuaian/cabang/{cabang_id}', 'PersediaanController@daftarPenyesuaian');
    Route::get('/transfer/cabang/{cabang_id}', 'PersediaanController@daftarTransfer');
    Route::get('/{id}/gudang/{gudang}', 'PersediaanController@show');
    //DESTROY
    Route::delete('/transfer/destroy/{id}', 'PersediaanController@destroyTransfer');
});

// Gudang
Route::group(['prefix' => 'gudang'], function () {
    //GET
    Route::get('/{cabang_id}', 'GudangController@index');
});

// Kontak
Route::group(['prefix' => 'kontak'], function () {
    //POST
    Route::post('/store', 'KontakController@store');
    //GET
    Route::get('/', 'KontakController@index');
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
    Route::get('/detail/barang/', 'TransaksiPenjualanController@getDetailTransaksiByBarang');
    Route::get('/transaksi/{id}', 'TransaksiPenjualanController@getTransaksi');
    //DELETE
    Route::delete('/delete/{id}', 'TransaksiPenjualanController@destroy');
});

// Transaksi Pembelian
Route::group(['prefix' => 'pembelian'], function () {
    //POST
    Route::post('/store', 'TransaksiPembelianController@store');
    Route::post('/retur', 'TransaksiPembelianController@retur');
    //GET
    Route::get('/cek-nomor-transaksi', 'TransaksiPembelianController@cekNomorTransaksi');
    Route::get('/cabang/{cabang}/awal/{dd}/akhir/{ddd}', 'TransaksiPembelianController@index');
    Route::get('/detail/barang/', 'TransaksiPembelianController@getDetailTransaksiByBarang');
    Route::get('/transaksi/{id}', 'TransaksiPembelianController@getTransaksi');
    //DELETE
    Route::delete('/delete/{id}', 'TransaksiPembelianController@destroy');
});

// Purchase Order
Route::group(['prefix' => 'po'], function () {
    //GET
    Route::get('/keluar', 'POController@keluar');
    Route::get('/masuk', 'POController@masuk');
    Route::get('/PO', 'POController@makeNomorPo');
    Route::get('/detail', 'POController@show');
    Route::get('/batal', 'POController@batal');
    Route::get('/show', 'POController@show');
    Route::get('/show-invoice', 'POController@showInvoice');
    Route::get('/show-keluar', 'POController@showKeluar');

    //STORE
    Route::post('/store', 'POController@store');
    Route::post('/selesai', 'POController@selesai');
    Route::post('/update-status', 'POController@updateStatus');
    //GET
    Route::delete('/delete/{id}', 'POController@destroy');
});


// UTANG PIUTANG
Route::group(['prefix' => 'utang-piutang'], function () {
    //POST
    Route::post('/store-utang', 'UtangPiutangController@storeUtang');
    Route::post('/store-piutang', 'UtangPiutangController@storePiutang');
    //GET
    Route::get('/get-utang', 'UtangPiutangController@getUtang');
    Route::get('/get-piutang', 'UtangPiutangController@getPiutang');
    Route::get('/get-list-supplier', 'UtangPiutangController@getListSupplier');
    Route::get('/get-list-pelanggan', 'UtangPiutangController@getListPelanggan');
    //DELETE
});

//PEMBAYARAN UTANG PIUTANG
Route::group(['prefix' => 'pembayaran'], function () {
    //POST
    Route::post('/store/piutang', 'PembayaranController@storePiutang');
    Route::post('/store/utang', 'PembayaranController@storeUtang');
    //GET
    Route::get('/daftar/piutang/{id}', 'PembayaranController@getDetailPembayaranPiutang');
    Route::get('/daftar/utang/{id}', 'PembayaranController@getDetailPembayaranUtang');
    //DELETE
    Route::delete('/delete/utang/{id}', 'PembayaranController@deleteUtang');
    Route::delete('/delete/piutang/{id}', 'PembayaranController@deletePiutang');
});

// Kepegawaian
Route::group(['prefix' => 'pegawai'], function () {
    //GET
    Route::get('/', 'PegawaiController@index');
    Route::get('/presensi/{id}/bulan/{m}', 'PegawaiController@presensi');
    Route::get('/gaji/{tipe}/{absensi}', 'PegawaiController@dataPenggajian');
    Route::get('/jabatandivisicabang', 'PegawaiController@jabatandivisicabang');
    //PUT
    Route::put('/edit/{id}', 'PegawaiController@update');
    //POST
    Route::post('/store', 'PegawaiController@store');
    Route::post('/store/gaji', 'PegawaiController@storeGaji');
});

// PENGGAJIAN
Route::group(['prefix' => 'gaji'], function () {
    //GET
    Route::get('daftar/{cabang}/{tahun}', 'GajiController@index');
    // Route::get('/pegawai/{tipe}/{absensi}/{tanggal}', 'GajiController@dataPenggajian');
    Route::get('/pegawai', 'GajiController@dataPenggajian');
    //POST
    Route::post('/store', 'GajiController@store');
    //PUT
    Route::delete('/delete/{id}', 'GajiController@destroy');

});

// Presensi
Route::group(['prefix' => 'presensi'], function () {
    //GET
    Route::get('/{date}', 'PresensiController@index');
    Route::get('/{id}/bulan/{m}', 'PresensiController@presensiPegawai');
     //POST
     Route::post('/store/manual/', 'PresensiController@storeManual');
     Route::post('/store/update-masuk/', 'PresensiController@updateMasuk');
     Route::post('/store/update-keluar/', 'PresensiController@updateKeluar');
     Route::get('/store/keluar/{id}', 'PresensiController@storeKeluar');
     Route::post('/upload-file/', 'PresensiController@import');

});

// Setor
Route::group(['prefix' => 'setor'], function () {
    //GET
    Route::get('/', 'SetorController@index');
    //POST
    Route::post('/store/', 'SetorController@store');
    Route::post('/confirm/', 'SetorController@confirm');
});

// Laporan

Route::group(['prefix'=>'laporan'], function(){
    //GET
    Route::get('/transaksi-penjualan/', 'LaporanController@laporanTransaksiPenjualan');
    Route::get('/persediaan', 'LaporanController@laporanPersediaan');
    Route::get('/persediaan/opname/', 'LaporanController@laporanPersediaanOpname');
    Route::get('/persediaan/transfer/', 'LaporanController@laporanPersediaanTransfer');
    Route::get('/gaji/', 'LaporanController@gaji');
    Route::get('/cabang/', 'LaporanController@cabang');
    Route::get('/print/', 'LaporanController@print');
});
