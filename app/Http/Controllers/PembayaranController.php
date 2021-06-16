<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Models\Pembayaran;
use App\Models\TransaksiPenjualan;


class PembayaranController extends Controller
{
    public function storePiutang(Request $payload){
        $data = Pembayaran::create([
            'penjualan_id'=>$payload->penjualan_id,
            'nominal'=>$payload->nominal,
            'created_at'=>$payload->tanggal,
            'catatan'=>$payload->catatan,
            'cara_pembayaran'=>$payload->caraPembayaran['title'],
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
        ]);

        $master = TransaksiPenjualan::find($payload->penjualan_id);
        $nomor_transaksi = $master->nomor_transaksi;
        $catatan = 'NOMOR TRANSAKSI '. $nomor_transaksi;

        $postJurnal = $this->postJurnal($payload, $catatan);

        $data->nomor_jurnal = $postJurnal['nomor_jurnal'];
        $data->save();

        if($postJurnal){
            $master->sisa_pembayaran = $master->sisa_pembayaran - $payload->nominal;
            $master->save();
        }

        return response()->json($data, 200);
    }

    public function getDetailPembayaranPiutang($id){
        $data = Pembayaran::select('detail_pembayaran.*', 'master_pegawai.nama')->where('detail_pembayaran.penjualan_id',$id)->join('master_pegawai','detail_pembayaran.user_id','=','master_pegawai.id')->get();
        return response()->json($data, 200);
    }

    function postJurnal($payload, $catatan){
        $transfer = $payload->caraPembayaran['value'];
        $nominal = $payload->nominal;

        if($transfer == 1){
            $bank = array(
                'akunId'=> $payload->bank['kode_akun_id'], // KAS BANK
                'namaJenis'=> 'DEBIT',
                'saldo'=>$nominal,
                'catatan'=>'TRANSFER PEMBAYARAN PIUTANG '. $catatan,
            );
            $jurnal['bank'] = $bank;
        }else{
            $kas = array(
                'akunId'=> $payload->user['kode_akun_id'], // KAS KECIL KASIR
                'namaJenis'=> 'DEBIT',
                'saldo'=>$nominal,
                'catatan'=>'KAS MASUK PEMBAYARAN PIUTANG '. $catatan,
            );
            $jurnal['kas'] = $kas;
        }
        
        $piutang = array(
            'akunId'=>'5', // PIUTANG DAGANG
            'namaJenis'=>'KREDIT',
            'saldo'=>$nominal,
            'catatan'=>'PEMBAYARAN PIUTANG '. $catatan,
        );
        $jurnal['piutang'] = $piutang;
        
        $post = [
            'catatan' => $catatan,
            'tanggalTransaksi'=>  date("Y-m-d h:i:s"),
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
            'jurnal'=> $jurnal
        ];

        $output = Http::post('http://127.0.0.1:8080/api/jurnal/store/', $post);

        return $output->json();
    }
}
