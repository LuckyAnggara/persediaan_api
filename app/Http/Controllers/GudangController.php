<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Models\Gudang;


class GudangController extends Controller
{
    function index($cabang_id){

        if($cabang_id == 0){
            $data = Gudang::select('gudang.*','master_cabang.nama as nama_cabang')->join('master_cabang','gudang.cabang_id','=','master_cabang.id')->get();
            
            foreach ($data as $key => $value) {
                $value->saldo = Http::get(keuanganBaseUrl().'akun/cek-saldo?id='.$value->kode_akun_id.'&cabang_id='.$cabang_id)->json();
            }
        }else{
            $data = Gudang::where('cabang_id', $cabang_id)->get();
            foreach ($data as $key => $value) {
                $value->saldo = Http::get(keuanganBaseUrl().'akun/cek-saldo?id='.$value->kode_akun_id.'&cabang_id='.$cabang_id)->json();
            }
        }

        return response()->json($data, 200);
    }

    
}
