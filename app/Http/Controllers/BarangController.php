<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\MerekBarang;
use App\Models\SatuanBarang;

class BarangController extends Controller
{
    public function index(){
        $data = Barang::all();
        return response()->json(['code'=>200, 'message'=>'Daftar Data Siswa','data' => $data], 200);
    }

    public function store(Request $request){
        $data = Barang::create([
            'nama' => $request->nama,
            'jenis_id' => $request->jenis_id,
            'merek_id' => $request->merek_id,
            'satuan_id' => $request->satuan_id,
            'harga_1' => $request->harga_1,
            'harga_2' => $request->harga_2,
            'harga_3' => $request->harga_3,
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
