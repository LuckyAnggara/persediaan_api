<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\MerekBarang;
use App\Models\SatuanBarang;

class BarangController extends Controller
{
    public function index(){


        $data = DB::table('barang')
        ->join('satuan_barang', 'barang.satuan_id', '=', 'satuan_barang.id')
        ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
        ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
        ->select('barang.*', 'satuan_barang.nama as nama_satuan', 'jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
        ->get();
        return response()->json($data, 200);
    }

    // public function index(){
    //     $data = Barang::all();
    //     return response()->json($data, 200);
    // }

    public function store(Request $request){
        $data = Barang::all();
        $output = collect($data)->last();
        $str = $request->nama[0];
        $kode_barang = $str.str_pad($output['id'] + 1,5,"0",STR_PAD_LEFT);

        while(Barang::where('kode_barang', $kode_barang)->exists()) {
            $kode_barang = $str.str_pad($output['id'] + 1,5,"0",STR_PAD_LEFT);
        }

        $data = Barang::create([
            'kode_barang'=> $kode_barang,
            'nama' => $request->nama,
            'jenis_id' => $request->jenis_id,
            'merek_id' => $request->merek_id,
            'satuan_id' => $request->satuan_id,
            'harga_1' => $request->harga_1,
            'harga_2' => $request->harga_2,
            'harga_3' => $request->harga_3,
            'catatan' => $request->catatan,
        ]);
        return response()->json($data, 200);
    }

    public function satuanList(){
        $data = SatuanBarang::all();
        return response()->json($data, 200);
    }

    public function jenisList(){
        $data = JenisBarang::all();
        return response()->json($data, 200);
    }

    public function merekList(){
        $data = MerekBarang::all();
        return response()->json($data, 200);
    }
}
