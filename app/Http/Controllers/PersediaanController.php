<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Barang;
use App\Models\Persediaan;

class PersediaanController extends Controller
{

    public function index(){

        $data = DB::table('barang')
        ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
        ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
        ->join('gudang', 'barang.gudang_id', '=', 'gudang.id')
        ->select('barang.*', 'gudang.nama as nama_gudang','jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
        ->where('barang.deleted_at', '=',null)
        ->get();

        
        foreach ($data as $key => $value) {
            $persediaan = DB::table('kartu_persediaan')
            ->select(
                DB::raw('SUM(debit) as saldo_masuk, SUM(kredit) as saldo_keluar'),
                )
            ->where('master_barang_id', '=',$value->id)
            ->first();

            $persediaan->saldo = $persediaan->saldo_masuk - $persediaan->saldo_keluar;
            
            if($value->jenis == 'FIFO'){
                $harga_perolehan = DB::table('detail_pembelian')
                ->select('harga')
                ->where('kode_barang_id', '=',$value->kode_barang)
                ->orderBy('created_at', 'asc')
                ->first();
            }else{
                $harga_perolehan = DB::table('detail_pembelian')
                ->select(DB::raw('round(AVG(harga),0) as harga'),)
                ->where('kode_barang_id', '=',$value->kode_barang)
                ->first();
            }
          
            if($harga_perolehan){
                $saldo = $persediaan->saldo;
                $persediaan->harga = $harga_perolehan->harga;
                $persediaan->jenis = $value->jenis;
                $persediaan->total = $saldo * $harga_perolehan->harga;
            }
            
            $value->persediaan = $persediaan;
            $output[] = $value;
        }
        return response()->json($output, 200);
    }

    public function show($id){
      
        $persediaan =[];
        try{
            // $barang = Barang::findOrFail($id);
            // $barang = DB::table('barang')
            // ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
            // ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
            // ->join('gudang', 'barang.gudang_id', '=', 'gudang.id')
            // ->select('barang.*', 'gudang.nama as nama_gudang','jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
            // ->where('barang.id', '=',$id)
            // ->first();
            
            $data = DB::table('master_persediaan')
            ->select('*')
            ->where('kode_barang_id', '=',$id)
            ->orderBy('id', 'desc')
            ->get();

            foreach ($data as $key => $value) {
                $master_penjualan = DB::table('master_penjualan')
                ->select('*')
                ->where('id', '=',$value->master_penjualan_id)
                ->orderBy('id', 'desc')
                ->first();
                $value->master_penjualan = $master_penjualan;
                $persediaan[] = $value;
            }
            
            $response = $persediaan;

            $code = 200;
        }catch(Execption $e){
            if($e instanceof ModelNotFoundException){
                $code = 404;
                $response = 'ID tidak ditemukan';
            }else{
                $code = 500;
                $response = $e->getMessage();
            }
        }

        return response()->json($response, 200);
    }
}
