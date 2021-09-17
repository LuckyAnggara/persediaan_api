<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presensi;
use App\Imports\PresensiImport;
use Maatwebsite\Excel\Facades\Excel;

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

    public function updateMasuk(Request $payload){
        $jam = date("H:i:s", strtotime($payload->jam));

        $data = Presensi::find($payload->id);
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

    public function updateKeluar(Request $payload){
        $jam = date("H:i:s", strtotime($payload->jam));

        $data = Presensi::find($payload->id);
        if($data){
            $response = 200;
            $data->jam_keluar = $jam;
            $data->save();
        }else{
            $response = 404;
            $data = 'Tidak ada Data';
        }
        return response()->json($data, $response);
    }

    public function storeManual(Request $payload){
        $output = [];
        foreach ($payload->data as $key => $value) {

            if(array_key_exists('jamMasuk', $value)){
                $dd = 'jamMasuk';
                $ddd = 'jamKeluar';
            }else{
                $dd = 'jam_masuk';
                $ddd = 'jam_keluar';
            }

            $exist = Presensi::where('pegawai_id', $value['id'])->where('tanggal', date("y-m-d", strtotime($payload->tanggal)))->first();
            if($exist){
                $exist->jam_masuk =  $value[$dd] == '' ? null  : date("H:i:s", strtotime($value[$dd]));
                $exist->jam_keluar =   $value[$ddd] == '' ? null  : date("H:i:s", strtotime($value[$ddd]));
                $exist->catatan = $value['catatan'];
                $exist->user_id = $payload->user['id'];
                $exist->cabang_id = $payload->user['cabang_id'];
                $exist->save();
                $output[] = $exist;
            }else{
                $data = Presensi::create([
                    'pegawai_id' => $value['id'],
                    'tanggal' =>  date("y-m-d", strtotime($payload->tanggal)),
                    'jam_masuk' => $value[$dd] == '' ? null  : date("H:i:s", strtotime($value[$dd])),
                    'jam_keluar' =>   $value[$ddd] == '' ? null  : date("H:i:s", strtotime($value[$ddd])),
                    'catatan' => $value['catatan'],
                    'user_id' => $payload->user['id'],
                    'cabang_id' => $payload->user['cabang_id'],
                ]);
                $output[] = $data;
            }
        }
        
        return response()->json($output, 200);
    }

    // ABSENSI MASUK
    public function storeMasuk(Request $payload){
        if(date("y-m-d h:i:s") > date("y-m-d 08:00:00"))
        {
            return "Oopss";
        }

        if($payload->jam = null){
            $jam = gmdate("H:i:s");
        }else{
            $jam =gmdate("H:i:s", ($payload->jam - 25569) * 86400);
        }

        $tanggal = date("y-m-d", strtotime($payload->tanggal));
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

    public function import(Request $payload) 
    {
        $validator = $payload->validate([
            'file' => 'required|mimes:xlsx,xls|max:4096',
        ]);  
       
        if ($files = $payload->file('file')) {
             
            // // $file = $payload->file->store('public/documents');
            $file = $payload->file->storeAs('public/documents','upload_presensi.'.$payload->file->extension());

            $import = new PresensiImport($payload->tanggal, $payload->user_id, $payload->cabang_id);

            Excel::import($import, storage_path('app/'.$file));
            $data = '';

            $data = Presensi::select('master_presensi.*', 'master_presensi.pegawai_id as id', 'master_pegawai.nama as nama')
            ->join('master_pegawai', 'master_presensi.pegawai_id', '=', 'master_pegawai.id')
            ->where('master_presensi.tanggal', $payload->tanggal)
            ->get();

            return response()->json([
                "success" => true,
                "message" => "File successfully uploaded",
                "file" =>  $data
            ]);
        }

        
        // return redirect('/')->with('success', 'All good!');
    }
}
