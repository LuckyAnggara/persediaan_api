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
            $persediaan = DB::table('master_persediaan')
            ->select(DB::raw('SUM(debit) as saldo_masuk, SUM(kredit) as saldo_keluar,SUM(saldo) as saldo_akhir'))
            ->where('kode_barang', '=',$value->kode_barang)
            ->get();
            $value->persediaan = $persediaan;
            $output[] = $value;
        }
        return response()->json($output, 200);
    }
}
