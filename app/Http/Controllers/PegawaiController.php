<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;
use App\Models\Presensi;

class PegawaiController extends Controller
{
    public function index(){
        $data = Pegawai::
        select('master_pegawai.*','master_jabatan.nama as jabatan','master_divisi.nama as divisi','master_cabang.nama as cabang')
        ->join('master_jabatan','master_pegawai.jabatan_id','=','master_jabatan.id')
        ->join('master_divisi','master_pegawai.divisi_id','=','master_divisi.id')
        ->join('master_cabang','master_pegawai.cabang_id','=','master_cabang.id')
        ->get();

        $output;
        
        foreach ($data as $key => $value) {
            $value->lama_bekerja = $value->age;

        }

        return response()->json($data, 200);
    }

    public function presensi($id, $m){
        $dateawal = date("Y-".$m."-01 00:00:01");
        $dateakhir = date("Y-".$m."-d 23:59:59");
        $data = Presensi::where('pegawai_id', $id)->whereBetween('tanggal',[$dateawal, $dateakhir])->get();
        return response()->json($data, 200);
    }
}
