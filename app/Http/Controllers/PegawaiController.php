<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;

class PegawaiController extends Controller
{
    public function index(){
        $data = Pegawai::
        select('master_pegawai.*','master_jabatan.nama as jabatan','master_divisi.nama as divisi')
        ->join('master_jabatan','master_pegawai.jabatan_id','=','master_jabatan.id')
        ->join('master_divisi','master_pegawai.divisi_id','=','master_divisi.id')
        ->get();
        return response()->json($data, 200);
    }
}
