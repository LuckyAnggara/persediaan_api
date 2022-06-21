<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Cabang;
use App\Models\Gudang;
use App\Models\Kontak;
use App\Models\Pegawai;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;

class CabangController extends Controller
{
    public function index(){
        try{
            // $barang = Barang::findOrFail($id);
            $master= Cabang::all();

            foreach ($master as $key => $value) {
                $pegawai = Pegawai::where('cabang_id', $value->id)->get();
                $value->pegawai = $pegawai;
            }
            $code = 200;
            $response = $master;

        }catch(Exception $e){
            if($e instanceof ModelNotFoundException){
                $code = 404;
                $response = 'Data Tidak Ditemukan';
            }else{
                $code = 500;
                $response = $e->getMessage();
            }
        }

        return response()->json($response, $code);
    }
    
    public function store(Request $payload){
        $data = Cabang::all();
        $output = collect($data)->last();
        $kode_cabang = 'BBM-1';

        if($output){
            $dd = $output->kode_cabang;
            $str = explode('-', $dd);

            $last_prefix = $str[1]+ 1;
            $kode_cabang = 'BBM-'.$last_prefix;
        }

        $master = Cabang::create([
           'kode_cabang' => $kode_cabang,
           'nama' => $payload->nama,
           'alamat' => $payload->alamat,
           'nomor_telepon' => $payload->nomor_telepon,
        ]);

        if($master){
            $dataAkunKas = [
                'jenis_akun_id' => 1,
                'kode_akun' => '1.1.2',
                'nama' => 'KAS KECIL - BBM '. strtoupper($master->nama),
                'saldo_normal' => 'DEBIT',
                'komponen' => '1.1.2',
                'cabang_id' => $master->id,
            ];
            $akunKas = Http::post(keuanganBaseUrl().'akun/store', $dataAkunKas)->json();

            $master->kode_akun_id = $akunKas['id'];

            $kontak = Kontak::create([
                'nama' => 'CABANG BBM - ' .strtoupper($master->nama),
                'tipe' => 'PELANGGAN',
                'telepon' => $payload->nomor_telepon,
                'alamat' => $payload->alamat,
                'cabang_id' => 0,
             ]);

             $gudang = Gudang::create([
                'nama' => 'GUDANG - BBM '. strtoupper($master->nama),
                'tipe' => 'PELANGGAN',
                'utama' => 1,
                'telepon' => $payload->nomor_telepon,
                'alamat' => $payload->alamat,
                'cabang_id' => $master->id,
             ]);

             $master->kontak_id = $kontak->id;
             $master->save();
             // PEMBUATAN AKUN PIUTANG UNTUK CABANG INI
             $dataAkunPiutang = [
                'jenis_akun_id' => 1,
                'kode_akun' => '1.1.4',
                'nama' => 'PIUTANG DAGANG - CABANG BBM '.  strtoupper($master->nama),
                'saldo_normal' => 'DEBIT',
                'komponen' => '1.1.4',
                'cabang_id' => 0,
            ];
            $akunPiutang = Http::post(keuanganBaseUrl().'akun/store', $dataAkunPiutang)->json();
            $kontak->akun_piutang_id = $akunPiutang['id'];

            // PEMBUATAN AKUN UTANG UNTUK CABANG INI
            $dataAkunUtang = [
                'jenis_akun_id' => 2,
                'kode_akun' => '2.1.1',
                'nama' => 'UTANG DAGANG - CABANG BBM '.  strtoupper($master->nama),
                'saldo_normal' => 'KREDIT',
                'komponen' => '2.1.1',
                'cabang_id' => 0,
            ];
            $akunUtang = Http::post(keuanganBaseUrl().'akun/store', $dataAkunUtang)->json();
            $kontak->akun_utang_id = $akunUtang['id'];
            $kontak->save();

            // PEMBUATAN AKUN GUDANG UNTUK CABANG INI
            $dataGudang = [
                'jenis_akun_id' => 1,
                'kode_akun' => '1.1.5',
                'nama' => strtoupper($gudang->nama),
                'saldo_normal' => 'DEBIT',
                'komponen' => '1.1.5',
                'cabang_id' => $master->id,
            ];
            $akunGudang = Http::post(keuanganBaseUrl().'akun/store', $dataGudang)->json();

            $gudang->kode_akun_id =$akunGudang['id'];
            $gudang->kode_akun =$akunGudang['kode_akun'];
            $gudang->save();
        }

        return response()->json($master, 200);
    }

    public function show(Request $payload){
        $id = $payload->input('cabang_id');

        $master = Cabang::find($id);
        $master->pegawai = Pegawai::where('cabang_id', $id)->get();
        $master->akun = Http::get(keuanganBaseUrl().'akun/'. $master->kode_akun_id)->json();

        return response()->json($master, 200);
    }

    public function update(Request $payload){
        $master = Cabang::find($payload->id);

        $master->nama = $payload->nama;
        $master->alamat = $payload->alamat;
        $master->nomor_telepon = $payload->nomor_telepon;

        $master->save();
        return response()->json($master, 200);

    }
}
