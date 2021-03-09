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

    public function satuanList(){
        $data = SatuanBarang::all();
        return response()->json($data, 200);
    }

    public function jenisList(){
        $data = JenisBarang::all();
        return response()->json($data, 200);
    }
}
