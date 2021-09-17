<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Models\Pembayaran;
use App\Models\TransaksiPembelian;
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

        $postJurnal = $this->postJurnalPiutang($payload, $catatan);

        $data->nomor_jurnal = $postJurnal['nomor_jurnal'];
        $data->save();

        if($postJurnal){
            $master->sisa_pembayaran = $master->sisa_pembayaran - $payload->nominal;
            $master->save();
        }

        return response()->json($data, 200);
    }

    public function storeUtang(Request $payload){
        $data = Pembayaran::create([
            'pembelian_id'=>$payload->pembelian_id,
            'nominal'=>$payload->nominal,
            'created_at'=>$payload->tanggal,
            'catatan'=>$payload->catatan,
            'cara_pembayaran'=>$payload->caraPembayaran['title'],
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
        ]);

        $master = TransaksiPembelian::find($payload->pembelian_id);
        $nomor_transaksi = $master->nomor_transaksi;
        $catatan = 'PEMBELIAN#'.$nomor_transaksi;

        $postJurnal = $this->postJurnalUtang($payload, $catatan);

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

    public function getDetailPembayaranUtang($id){
        $data = Pembayaran::select('detail_pembayaran.*', 'master_pegawai.nama')->where('detail_pembayaran.pembelian_id',$id)->join('master_pegawai','detail_pembayaran.user_id','=','master_pegawai.id')->get();
        return response()->json($data, 200);
    }

    function postJurnalPiutang($payload, $catatan){
        $transfer = $payload->caraPembayaran['value'];
        $nominal = $payload->nominal;

        if($transfer == 1){
            $bank = array(
                'akunId'=> $payload->bank['kode_akun_id'], // KAS BANK
                'namaJenis'=> 'DEBIT',
                'saldo'=>$nominal,
                'catatan'=>'TRANSFER MASUK PEMBAYARAN PIUTANG '. $catatan,
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
            'akunId'=> $payload->akun_piutang_id, // PIUTANG DAGANG PELANGGAN
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

    function postJurnalUtang($payload, $catatan){
        $transfer = $payload->caraPembayaran['value'];
        $nominal = $payload->nominal;

        if($transfer == 1){
            $bank = array(
                'akunId'=> $payload->bank['kode_akun_id'], // KAS BANK
                'namaJenis'=> 'KREDIT',
                'saldo'=>$nominal,
                'catatan'=>'TRANSFER KELUAR PEMBAYARAN UTANG '. $catatan,
            );
            $jurnal['bank'] = $bank;
        }else{
            $kas = array(
                'akunId'=> $payload->kas['kode_akun_id'], // KAS KECIL KASIR
                'namaJenis'=> 'KREDIT',
                'saldo'=>$nominal,
                'catatan'=>'KAS KELUAR PEMBAYARAN UTANG '. $catatan,
            );
            $jurnal['kas'] = $kas;
        }
        
        $piutang = array(
            'akunId'=> $payload->akun_utang_id, // UTANG DAGANG SUPPLIER
            'namaJenis'=>'DEBIT',
            'saldo'=>$nominal,
            'catatan'=>'PEMBAYARAN UTANG '. $catatan,
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

    public function deleteUtang($id){
        $master = Pembayaran::findOrFail($id);
        $response = 404;
        if($master){
            $master->delete();
            $response = 200;
            $pembelian = TransaksiPembelian::find($master->pembelian_id);
            $pembelian->sisa_pembayaran = $pembelian->sisa_pembayaran + $master->nominal;
            $pembelian->save();
            Http::delete(keuanganBaseUrl().'jurnal/delete/'.$master->nomor_jurnal);
        }
        return response()->json($master, $response);
    }

    public function deletePiutang($id){
        $master = Pembayaran::findOrFail($id);
        $response = 404;
        if($master){
            $master->delete();
            $response = 200;
            $penjualan = TransaksiPenjualan::find($master->penjualan_id);
            $penjualan->sisa_pembayaran = $penjualan->sisa_pembayaran + $master->nominal;
            $penjualan->save();
            Http::delete(keuanganBaseUrl().'jurnal/delete/'.$master->nomor_jurnal);
        }
        return response()->json($master, $response);
    }
}
