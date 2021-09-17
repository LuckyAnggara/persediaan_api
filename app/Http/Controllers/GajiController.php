<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\Gaji;
use App\Models\DetailGaji;

use PDF;

class GajiController extends Controller
{

    public function index($cabang, $year){

    
        if($year == null) {
            $year = 'Y';
        }
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $master = Gaji::where('cabang_id', $cabang)->whereBetween('created_at',[$dateawal, $dateakhir])->get()->sortBy([['created_at','desc']]);
        return response()->json($master, 200);

    }

    public function dataPenggajian(Request $request){


        $cabang_id = $request->input('cabang_id');
        $tipe = $request->input('tipe');
        $absensi = $request->input('absensi');
        $bonus = $request->input('bonus');
        $tanggal = $request->input('tanggal');


        $output= [];
        if($absensi == 1){

            $pegawai = Presensi::where('tanggal', $tanggal)->where('status','MASUK')->where('cabang_id',$cabang_id)->get();

            foreach ($pegawai as $key => $value) {
                $xx = Pegawai::select('master_pegawai.*', 'master_jabatan.nama as jabatan')
                ->join('master_jabatan', 'master_pegawai.jabatan_id', '=', 'master_jabatan.id')
                ->where('master_pegawai.id', $value->pegawai_id)
                ->where('status_gaji', $tipe)
                ->where('cabang_id', $cabang_id)
                ->first();

                if($xx){
                $xx->dibayarkan = true;
                $xx->bonus = 0;
                $output[] = $xx;

                }
            }
        }else if($absensi == 2){

            $data =Pegawai::select('master_pegawai.*', 'master_jabatan.nama as jabatan')
            ->join('master_jabatan', 'master_pegawai.jabatan_id', '=', 'master_jabatan.id')
            ->where('status_gaji', $tipe)
            ->where('cabang_id', $cabang_id)
            ->get();

            foreach ($data as $key => $value) {
                $value['dibayarkan'] = true;
                $value['bonus'] = 0;
            $output[] = $value;

            };

        }else if($absensi == 3){
            $data =Pegawai::select('master_pegawai.*', 'master_jabatan.nama as jabatan')
            ->join('master_jabatan', 'master_pegawai.jabatan_id', '=', 'master_jabatan.id')
            ->where('status_gaji', $tipe)
            ->where('cabang_id', $cabang_id)
            ->get();

            foreach ($data as $key => $value) {
                $value['dibayarkan'] = true;
                $value['bonus'] = 0;
            $output[] = $value;

            };
        }
        return response()->json($output, 200);


    }

    public function store(Request $payload){
        $output = [];
        $output['master'] = Gaji::create([
            'nominal' => $payload->total['grand_total'],
            'kategori' => $payload->form['kategori']['nama'],
            'uang_makan' => $payload->form['uang_makan']['nama'],
            'bonus' => $payload->form['bonus']['nama'],
            'berdasarkan' => $payload->form['absensi']['nama'],
            'jumlah_pegawai' => $payload->total['jumlah_pegawai'],
            'catatan' => $payload->form['catatan'],
            'created_at' => $payload->form['tanggalGaji'],
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
        ]);

        if($output['master']->id){
            foreach ($payload->data as $key => $value) {
                if($value['dibayarkan'] === true){
                    $data = DetailGaji::create([
                        'master_gaji_id' => $output['master']->id,
                        'pegawai_id' => $value['id'],
                        'uang_makan' => $value['uang_makan'],
                        'bonus' => $value['bonus'],
                        'gaji_pokok' => $value['gaji_pokok'],
                        'tambahan_lainnya' => 0,
                        'catatan' => $payload->form['catatan'],
                        'created_at' => $payload->form['tanggalGaji'],
                    ]);

                    $output['detail'][] = $data;
                }
            }

            $jurnal['debit'] = array(
                'akunId'=> '40', // HPP
                'namaJenis'=> 'DEBIT',
                'saldo'=>$payload->total['grand_total'],
                'catatan'=>'PEMBAYARAN GAJI - '. $payload->form['catatan'],
             );
    
             $jurnal['kredit'] = array(
                'akunId'=> $payload->akun['id'], // HPP
                'namaJenis'=> 'KREDIT',
                'saldo'=>$payload->total['grand_total'],
                'catatan'=>'PEMBAYARAN GAJI - '. $payload->form['catatan'],
             );
    
            $post = [
                'catatan' => 'PEMBAYARAN GAJI - '. $payload->form['catatan'],
                'tanggalTransaksi'=>  $payload->form['tanggalGaji'],
                'user_id' => $payload->user['id'],
                'cabang_id'=>$payload->user['cabang_id'],
                'jurnal'=> $jurnal
            ];
    
            $dd = Http::post(keuanganBaseUrl().'jurnal/store/', $post);
    
            $output['jurnal'] = $dd->json();
    
            $output['master']->nomor_jurnal = $output['jurnal']['nomor_jurnal'];
            $output['master']->save();
        }

        return response()->json($output, 200);

    }

    public function destroy($id){
        $master = Gaji::find($id);

        if($master){
            $master->delete();

            $detail = DetailGaji::where('master_gaji_id', $id)->get();
            foreach ($detail as $key => $value) {
                $value->delete();
            }

            http::delete(keuanganBaseUrl().'jurnal/delete/'.$master->nomor_jurnal);
            $response = 200;
            $message = $master;
        } else {
            $response = 404;
            $message = 'Fail';
        }

        return response()->json($message, $response);
    }


}
