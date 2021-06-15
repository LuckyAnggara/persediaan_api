<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;
use App\Models\Presensi;

class PresensiController extends Controller
{
    public function index($date = null){
        if($date == null){
            $date = date('y-m-d');
        }
       $data =  Presensi::
        select('master_presensi.*','master_pegawai.nama')
        ->join('master_pegawai','master_presensi.pegawai_id','=','master_pegawai.id')
        ->where('tanggal',date("y-m-d",strtotime($date)))
        ->get();
        return response()->json($data, 200);


    }

    public function presensiPegawai($id, $m){
        $dateawal = date("Y-".$m."-01 00:00:01");
        $dateakhir = date("Y-".$m."-d 23:59:59");
        $data = Presensi::where('pegawai_id', $id)->whereBetween('tanggal',[$dateawal, $dateakhir])->get();
        return response()->json($data, 200);
    }

    // ABSENSI MASUK
    public function storeMasuk(Request $payload){
        if(date("y-m-d h:i:s") > date("y-m-d 08:00:00"))
        {
            return "Oopss";
        }
        if($payload->jam = null){
            $jam = date("y-m-d h:i:s");
        }else{
            $jam =date("y-m-d h:i:s");
        }

        $tanggal = date("y-m-d");
        $data = Presensi::where('pegawai_id', $payload->id)->where('tanggal', $tanggal)->first();
        if($data){
            $response = 200;
            $data->jam_masuk = $jam;
            $data->save();
        }else{
            $response = 404;
            $data = 'Tidak ada Data';
        }
        return response()->json($data, $response);
    }

    // ABSENSI KELUAR
    public function storeKeluar($id){
        if(date("y-m-d h:i:s") < date("y-m-d 15:00:00"))
        {
            return "Belum Saatnya Pulang";
        }

        $tanggal = date("y-m-d");
        $data = Presensi::where('pegawai_id', $id)->where('tanggal', $tanggal)->first();
        if($data){
            $response = 200;
            $data->jam_keluar = date("y-m-d h:i:s");
            $data->save();
        }else{
            $response = 404;
            $data = 'Tidak ada Data';
        }
        return response()->json($data, $response);
    }
}
