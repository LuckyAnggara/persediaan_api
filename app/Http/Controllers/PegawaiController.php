<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\Cabang;
use App\Models\Divisi;
use App\Models\Jabatan;

class PegawaiController extends Controller
{
    public function index(){
        $data = Pegawai::
        select('master_pegawai.*','master_jabatan.nama as jabatan','master_divisi.nama as divisi','master_cabang.nama as cabang')
        ->join('master_divisi','master_pegawai.divisi_id','=','master_divisi.id')
        ->join('master_cabang','master_pegawai.cabang_id','=','master_cabang.id')
        ->join('master_jabatan','master_pegawai.jabatan_id','=','master_jabatan.id')
        ->get();

        $output;
        
        foreach ($data as $key => $value) {
            $value->lama_bekerja = $value->age;
            $value->jabatan_id = (int)$value->jabatan_id;
        }

        return response()->json($data, 200);
    }

    public function update(Request $payload, $id){
        $master = Pegawai::find($id);

        if($master){
            $master->ktp = $payload->ktp;
            $master->nama = $payload->nama;
            $master->jenis_kelamin = $payload->jenis_kelamin;
            $master->alamat = $payload->alamat;
            $master->kelurahan = $payload->kelurahan;
            $master->kecamatan = $payload->kecamatan;
            $master->kota = $payload->kota;
            $master->tanggal_lahir = $payload->tanggal_lahir;        
            $master->tanggal_masuk = $payload->tanggal_masuk;
            $master->pendidikan_terakhir = $payload->pendidikan_terakhir;
            $master->nomor_telepon = $payload->nomor_telepon;
            $master->nomor_rekening = $payload->nomor_rekening;
            $master->npwp = $payload->npwp;
            $master->divisi_id = $payload->divisi_id;
            $master->jabatan_id = $payload->jabatan_id;
            $master->cabang_id = $payload->cabang_id;
            $master->user_id = $payload->user_id;

            $master->save();
        }
        return response()->json($master, 200);
    }

    public function store(Request $payload){

        $data = Pegawai::create([
            'ktp'=> $payload->ktp,
            'nama' => $payload->nama,
            'jenis_kelamin' => $payload->jenis_kelamin,
            'alamat' => $payload->alamat,
            'kelurahan' => $payload->kelurahan,
            'kecamatan' => $payload->kecamatan,
            'kota' => $payload->kota,
            'tanggal_lahir' => $payload->tanggal_lahir,
            'tanggal_masuk' => $payload->tanggal_masuk,
            'pendidikan_terakhir' => $payload->pendidikan_terakhir,
            'nomor_telepon' => $payload->nomor_telepon,
            'nomor_rekening' => $payload->nomor_rekening,
            'npwp' => $payload->npwp,
            'divisi_id' => $payload->divisi_id,
            'jabatan_id' => $payload->jabatan_id,
            'cabang_id' => $payload->cabang_id,
        ]);
        return response()->json($data, 200);
    }

    public function jabatandivisicabang(){
        $jabatan = Jabatan::all();
        $divisi = Divisi::all();
        $cabang = Cabang::all();

        $ouput['jabatan'] = $jabatan;
        $ouput['divisi'] = $divisi;
        $ouput['cabang'] = $cabang;
        return response()->json($ouput, 200);
    }

    public function presensi($id, $m){
        $dateawal = date("Y-".$m."-01 00:00:01");
        $dateakhir = date("Y-".$m."-d 23:59:59");
        $data = Presensi::where('pegawai_id', $id)->whereBetween('tanggal',[$dateawal, $dateakhir])->get();
        return response()->json($data, 200);
    }
}
