<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\Setor;
use App\Models\Cabang;

class SetorController extends Controller
{
    public function index(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $jenis = $payload->input('jenis');
        $tanggal_awal = $payload->input('tanggal_awal');
        $tanggal_akhir = $payload->input('tanggal_akhir');

        if($tanggal_awal == "null"){
            $tanggal_awal = Date('Y-01-01');
        }

        if($tanggal_akhir == "null"){
            $tanggal_akhir = Date('Y-12-31');
        }
        if($jenis == 'KIRIM'){
            $data = Setor::where('cabang_id',$cabang_id)->whereDate('created_at','>=', $tanggal_awal)->whereDate('created_at','<=', $tanggal_akhir)->get();
        }else{
            $data = Setor::where('cabang_id_ke',$cabang_id)->whereDate('created_at','>=', $tanggal_awal)->whereDate('created_at','<=', $tanggal_akhir)->get();
        }

        foreach ($data as $key => $value) {
            $value->cabang_id_dari = Cabang::find($value->cabang_id_dari);
            $value->cabang_id_ke = Cabang::find($value->cabang_id_ke);
            $value->kode_akun_id_dari =  Http::get(keuanganBaseUrl().'akun/'.$value->kode_akun_id_dari)->json();
            $value->kode_akun_id_ke =  Http::get(keuanganBaseUrl().'akun/'.$value->kode_akun_id_ke)->json();
        }

        return response()->json($data, 200);
    }
    
    public function store(Request $payload){

       $data =  Setor::create([
            'cabang_id_dari' => $payload->cabang_id_dari['id'],
            'cabang_id_ke' =>  $payload->cabang_id_ke['id'],
            'kode_akun_id_dari' => $payload->cabang_id_dari['kode_akun_id'],
            'kode_akun_id_ke' =>  $payload->lawan_akun_id_ke['id'],
            'nominal' => $payload->jumlah,
            'catatan' => $payload->catatan,
            'user_id' => $payload->user['id'],
            'cabang_id' => $payload->user['cabang_id'],
            'status'=> 'SEND'
        ]);

        return response()->json($data, 200);
    }

    public function confirm(Request $payload){
        $master = Setor::findOrFail($payload->id);

        if($payload->confirm == 'APPROVED'){

            $post = $this->postJurnalConfirm($master, $payload); 
            if($post){
                $master->status_terima = 'APPROVED';
                $master->status_kirim = 'APPROVED';
                $master->nomor_jurnal_dari = $post['jurnal_1']['nomor_jurnal'];
                $master->nomor_jurnal_ke = $post['jurnal_2']['nomor_jurnal'];
                $master->save();
            }
        }else if ($payload->confirm == 'REJECTED'){
            $master->status_terima = 'REJECTED';
            $master->status_kirim ='REJECTED';
            $master->save();
        }

        return response()->json($post, 200);

    }

    public function postJurnalConfirm($master, $payload){

        /// JURNAL DARI
        $kredit_1 = array(
            'akunId'=>$payload->kode_akun_id_dari['id'], // KAS INDUK
            'namaJenis'=>'KREDIT',
            'saldo'=>$master->nominal,
            'catatan'=> $master->catatan,
        );
        $jurnal_1['kredit_1'] = $kredit_1;

        $debit_1 = array(
            'akunId'=> '29', // PRIVE
            'namaJenis'=>'DEBIT',
            'saldo'=>$master->nominal,
            'catatan'=> $master->catatan,
        );
        $jurnal_1['debit_1'] = $debit_1;

        $post_1 = [
            'catatan' => $master->catatan,
            'tanggalTransaksi'=>  date("Y-m-d h:i:s"),
            'user_id' => $master->user_id,
            'cabang_id'=>$master->cabang_id,
            'jurnal'=> $jurnal_1
        ];
        $output_1 = Http::post(keuanganBaseUrl().'jurnal/store/', $post_1);

        /// JURNAL KE
        $kredit_2 = array(
            'akunId'=> '28',
            'namaJenis'=>'KREDIT',
            'saldo'=>$master->nominal,
            'catatan'=> $master->catatan,
        );
        $jurnal_2['kredit_2'] = $kredit_2;

        $debit_2 = array(
            'akunId'=>$payload->kode_akun_id_ke['id'], // KAS INDUK atau KAS BANK
            'namaJenis'=>'DEBIT',
            'saldo'=>$master->nominal,
            'catatan'=> $master->catatan,
        );
        $jurnal_2['debit_2'] = $debit_2;

        $post_2 = [
            'catatan' => $master->catatan,
            'tanggalTransaksi'=>  date("Y-m-d h:i:s"),
            'user_id' => $payload->user_terima['id'],
            'cabang_id'=> $payload->user_terima['cabang_id'],
            'jurnal'=> $jurnal_2
        ];

        $output_2 = Http::post(keuanganBaseUrl().'jurnal/store/', $post_2);

        return [
            'jurnal_1' => $output_1->json(),
            'jurnal_2' => $output_2->json(),
        ];
    }
}
