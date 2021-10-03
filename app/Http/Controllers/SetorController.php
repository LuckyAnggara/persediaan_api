<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\Setor;
use App\Models\Cabang;
use Carbon\Carbon;

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
            'cabang_id_dari' => $payload->user['cabang_id'],
            'cabang_id_ke' => 1,
            'kode_akun_id_dari' => $payload->jenis_penyetoran['value'] === 1 ? $payload->bank['kode_akun_id'] : $payload->user['cabang']['kode_akun_id'],
            'kode_akun_id_ke' =>  65, //KODE AKUN ID SETOR MASTER
            'nominal' => $payload->jumlah,
            'catatan' => $payload->catatan,
            'user_id' => $payload->user['id'],
            'cabang_id' => $payload->user['cabang_id'],
            'status'=> 'SEND',
            'created_at' => $payload->tanggal
        ]);

        return response()->json($data, 200);
    }

    public function batal(Request $payload){
        $id = $payload->input('id');

        $master = Setor::find($id);
        $code = 404;
        if($master){
            $master->delete();
            $code = 200;
        }

        return response()->json($master, $code);

    }
    public function confirm(Request $payload){
        $master = Setor::findOrFail($payload->id);

        if($payload->confirm == 'APPROVED'){

            $post = $this->postJurnalConfirm($master, $payload); 
            if($post){
                $master->status = 'APPROVED';
                $master->nomor_jurnal_dari = $post['jurnal_1']['nomor_jurnal'];
                $master->nomor_jurnal_ke = $post['jurnal_2']['nomor_jurnal'];
                $master->save();
            }
        }else if ($payload->confirm == 'REJECTED'){
            $master->status ='REJECTED';
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

    public function pelaporan(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $year = $payload->input('tahun');
        $month = $payload->input('bulan');
        $day = $payload->input('hari');

        if($year != null){
            $dateawal = date($year.'-01-01 00:00:00');
            $dateakhir = date($year.'-12-31 23:59:59');
        }
        if($month != null){
            $dateawal =  date('Y-'.$month.'-01 00:00:00');
            $dateakhir = date('Y-'.$month.'-31 23:59:59');
        }
        if($day != null){
            $dateawal = date('2021-m-d 00:00:00', strtotime($day));
            $dateakhir = date('Y-m-d 23:59:59', strtotime($day));
        }

        $master = Setor::where('cabang_id', $cabang_id)
        ->where('created_at','>=',$dateawal)    
        ->where('created_at','<=',$dateakhir);
        
        // PELAPORAN
        $output['pelaporan']['data']  = $master->where('status', 'SEND')->get();
        $output['pelaporan']['total']  = $master->where('status', 'SEND')->sum('nominal');
        // TERBUKU
        $output['terbuku']['data']  = $master->where('status', 'APPROVE')->get();
        $output['terbuku']['total']  = $master->where('status', 'APPROVE')->sum('nominal');

        $output['sisa'] = $output['pelaporan']['total'] - $output['terbuku']['total'];

        return response()->json($output, 200);

    }
}
