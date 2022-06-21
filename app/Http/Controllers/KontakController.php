<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Kontak;
use Illuminate\Support\Facades\Http;

class KontakController extends Controller
{
    public function index(){

        // if($cabang_id == 0)
        // {
        //     $data = Kontak::where('wic', '=',0)->get();
        // }else{
            $data = DB::table('master_kontak')
            ->select('*')
            ->where('wic', '=',0)
            // ->where('cabang_id', '=',$cabang_id)
            ->where('deleted_at', '=',null)
            ->get();
        // }

        return response()->json($data, 200);



    }

    public function store(Request $payload){
        $kontak = Kontak::create([
            'tipe'=> $payload->tipe,
            'nama' => $payload->nama,
            'alamat' => $payload->alamat,
            'telepon' => $payload->telepon,
            'wic' => 0,
            'user_id' => $payload->user['id'],
            'cabang_id' => $payload->user['cabang_id'],
        ]);
        if($kontak){
            // PEMBUATAN AKUN PIUTANG UNTUK KONTAK INI

            $dataAkunPiutang = [
                'jenis_akun_id' => 1,
                'kode_akun' => '1.1.4',
                'nama' => 'PIUTANG DAGANG - '.  strtoupper($kontak->nama),
                'saldo_normal' => 'DEBIT',
                'komponen' => '1.1.4',
                'cabang_id' => $payload->user['cabang_id'],
            ];
            $akunPiutang = Http::post(keuanganBaseUrl().'akun/store', $dataAkunPiutang)->json();
            $kontak->akun_piutang_id = $akunPiutang['id'];
    
            // PEMBUATAN AKUN UTANG UNTUK KONTAK INI
            $dataAkunUtang = [
                'jenis_akun_id' => 2,
                'kode_akun' => '2.1.1',
                'nama' => 'UTANG DAGANG - '.  strtoupper($kontak->nama),
                'saldo_normal' => 'KREDIT',
                'komponen' => '2.1.1',
                'cabang_id' => $payload->user['cabang_id'],
            ];
            $akunUtang = Http::post(keuanganBaseUrl().'akun/store', $dataAkunUtang)->json();
            $kontak->akun_utang_id = $akunUtang['id'];
            $kontak->save();
    
        }
        
        return response()->json($kontak, 200);
    }
}
