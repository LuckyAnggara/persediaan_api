<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Gudang;

class GudangController extends Controller
{
    function index(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $day = $payload->input('hari');
        if($cabang_id == 0){
            $data = Gudang::select('gudang.*','master_cabang.nama as nama_cabang')->join('master_cabang','gudang.cabang_id','=','master_cabang.id')->get();
            foreach ($data as $key => $value) {
                $value->saldo = Http::get(keuanganBaseUrl().'akun/cek-saldo?akun_id='.$value->kode_akun_id.'&cabang_id='.$cabang_id.'&tahun=&bulan=&hari='.$day)->json();
            }
        }else{
            $data = Gudang::where('cabang_id', $cabang_id)->get();
            foreach ($data as $key => $value) {
                $value->saldo = Http::get(keuanganBaseUrl().'akun/cek-saldo?akun_id='.$value->kode_akun_id.'&cabang_id='.$cabang_id.'&tahun=&bulan=&hari='.$day)->json();
            }
        }
        return response()->json($data, 200);
    }

    function gudang(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $master = Gudang::where('cabang_id', $cabang_id)->get();
        return response()->json($master, 200);
    }
}
