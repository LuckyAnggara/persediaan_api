<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Kontak;
use App\Models\TransaksiPenjualan;
use App\Models\TransaksiPembelian;
use App\Models\Pembayaran;


class UtangPiutangController extends Controller
{

    // PIUTANG
    public function getPiutang(Request $payload){
        $output = [];
        $cabang = $payload->input('cabang');
        $dd= $payload->input('dd');
        $ddd = $payload->input('ddd');


        $dateawal = date("Y-m-d 00:00:00", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));
        // return $dateakhir;

        if($dd == "null"){
            $dateawal = date("2021-01-01 00:00:00");
        }
        if($ddd =="null"){
            $dateakhir = date("Y-m-d 23:59:59");
        }

        $data = TransaksiPenjualan::select('master_penjualan.*','master_kontak.nama as nama_pelanggan')
        ->join('master_kontak','master_penjualan.kontak_id','=','master_kontak.id') 
        ->whereDate('master_penjualan.created_at','>=',$dateawal)    
        ->whereDate('master_penjualan.created_at','<=   ',$dateakhir)
        ->where('master_penjualan.kredit','1')
        ->orderBy('created_at','asc')
        ->get();

        foreach ($data as $key => $data) {
            $data->list_pembayaran = Pembayaran::where('penjualan_id', $data->id)->get();
            $data->jumlah_pembayaran = 0;
            $data->lunas_check = false;
            $output[] = $data;
        }
        return response()->json($output, 200);
    }

    public function getListPelanggan(Request $payload){
        $cabang = $payload->input('cabang');
        $dd= $payload->input('dd');
        $ddd = $payload->input('ddd');

        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));

        if($dd = "null"){
            $dateawal = date("2021-01-01 00:00:01");
        }
        if($ddd ="null"){
            $dateakhir = date("Y-m-d 23:59:59");
        }

        $data = TransaksiPenjualan::select('master_kontak.*')
        ->join('master_kontak','master_penjualan.kontak_id','=','master_kontak.id') 
        ->where('master_penjualan.cabang_id', $cabang)
        ->whereDate('master_penjualan.created_at','>',$dateawal)    
        ->whereDate('master_penjualan.created_at','<',$dateakhir)
        ->where('master_penjualan.kredit','1')
        ->groupBy('master_kontak.nama')
        ->get();



        return response()->json($data, 200);
    }

    public function storePiutang(Request $payload){
        $output = [];
        foreach ($payload->data_piutang as $key => $piutang) {
            if($piutang['jumlah_pembayaran'] != 0){
                $data = Pembayaran::create([
                    'penjualan_id'=>$piutang['id'],
                    'nominal'=> $piutang['jumlah_pembayaran'],
                    'catatan'=>'Pembayaran piutang secara '. $payload->transfer === true ? 'Transfer melalui Bank '. $payload->bank['title'] : 'Tunai ' . ' Tanggal '. date("d-m-Y"),
                    'cara_pembayaran'=>$payload->transfer === true ? 'TRANSFER' : 'TUNAI',
                    'user_id' => $payload->user['id'],
                    'cabang_id'=> $payload->user['cabang_id'],
                ]);

            $master = TransaksiPenjualan::find($piutang['id']);
            $nomor_transaksi = $master->nomor_transaksi;
            $catatan = 'PEMBELIAN#'.$nomor_transaksi;

            $postJurnal = $this->postJurnalPiutang($payload, $piutang, $catatan);

            $data->nomor_jurnal = $postJurnal['nomor_jurnal'];
            $data->save();

            if($postJurnal){
                $master->sisa_pembayaran = $master->sisa_pembayaran - $piutang['jumlah_pembayaran'];
                $master->save();
            }
            }

            $output[] = $data;
        }

        return response()->json($output, 200);
    }

    function postJurnalPiutang($payload, $piutang, $catatan){
        $nominal = $piutang['jumlah_pembayaran'];
        if($payload->transfer == true){
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
            'akunId'=> $payload->pelanggan['akun_piutang_id'], // PIUTANG DAGANG SUPPLIER
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

    // UTANG

    public function getUtang(Request $payload){
        $output = [];
        $cabang = $payload->input('cabang');
        $dd= $payload->input('dd');
        $ddd = $payload->input('ddd');

        $dateawal = date("Y-m-d 00:00:00", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));
        // return $dateakhir;

        if($dd == "null"){
            $dateawal = date("2021-01-01 00:00:00");
        }
        if($ddd =="null"){
            $dateakhir = date("Y-m-d 23:59:59");
        }

        $data = TransaksiPembelian::select('master_pembelian.*','master_kontak.nama as nama_supplier')
        ->join('master_kontak','master_pembelian.kontak_id','=','master_kontak.id') 
        ->where('master_pembelian.cabang_id', $cabang)
        ->whereDate('master_pembelian.created_at','>=',$dateawal)    
        ->whereDate('master_pembelian.created_at','<=   ',$dateakhir)
        ->where('master_pembelian.kredit','1')
        ->orderBy('created_at','asc')
        ->get();

        foreach ($data as $key => $data) {
            $data->list_pembayaran = Pembayaran::where('pembelian_id', $data->id)->get();
            $data->jumlah_pembayaran = 0;
            $data->lunas_check = false;
            $output[] = $data;
        }
        return response()->json($output, 200);
    }

    public function getListSupplier(Request $payload){
        $cabang = $payload->input('cabang');
        $dd= $payload->input('dd');
        $ddd = $payload->input('ddd');

        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));

        if($dd = "null"){
            $dateawal = date("2021-01-01 00:00:01");
        }
        if($ddd ="null"){
            $dateakhir = date("Y-m-d 23:59:59");
        }

        $data = TransaksiPembelian::select('master_kontak.*')
        ->join('master_kontak','master_pembelian.kontak_id','=','master_kontak.id') 
        ->where('master_pembelian.cabang_id', $cabang)
        ->whereDate('master_pembelian.created_at','>',$dateawal)    
        ->whereDate('master_pembelian.created_at','<',$dateakhir)
        ->where('master_pembelian.kredit','1')
        ->groupBy('master_kontak.nama')
        ->get();



        return response()->json($data, 200);
    }

    public function storeUtang(Request $payload){


        $output = [];
        foreach ($payload->data_utang as $key => $utang) {
            if($utang['jumlah_pembayaran'] != 0){
                $data = Pembayaran::create([
                    'pembelian_id'=>$utang['id'],
                    'nominal'=> $utang['jumlah_pembayaran'],
                    'catatan'=>'Pembayaran utang secara '. $payload->transfer === true ? 'Transfer melalui Bank '. $payload->bank['title'] : 'Tunai ' . ' Tanggal '. date("d-m-Y"),
                    'cara_pembayaran'=>$payload->transfer === true ? 'TRANSFER' : 'TUNAI',
                    'user_id' => $payload->user['id'],
                    'cabang_id'=> $payload->user['cabang_id'],
                ]);

            $master = TransaksiPembelian::find($utang['id']);
            $nomor_transaksi = $master->nomor_transaksi;
            $catatan = 'PEMBELIAN#'.$nomor_transaksi;

            $postJurnal = $this->postJurnalUtang($payload, $utang, $catatan);

            $data->nomor_jurnal = $postJurnal['nomor_jurnal'];
            $data->save();

            if($postJurnal){
                $master->sisa_pembayaran = $master->sisa_pembayaran - $utang['jumlah_pembayaran'];
                $master->save();
            }
            }

            $output[] = $data;
        }

        return response()->json($output, 200);

       


    }
    function postJurnalUtang($payload, $utang, $catatan){
        $nominal = $utang['jumlah_pembayaran'];
        if($payload->transfer == true){
            $bank = array(
                'akunId'=> $payload->bank['kode_akun_id'], // KAS BANK
                'namaJenis'=> 'KREDIT',
                'saldo'=>$nominal,
                'catatan'=>'TRANSFER KELUAR PEMBAYARAN UTANG '. $catatan,
            );
            $jurnal['bank'] = $bank;
        }else{
            $kas = array(
                'akunId'=> $payload->user['kode_akun_id'], // KAS KECIL KASIR
                'namaJenis'=> 'KREDIT',
                'saldo'=>$nominal,
                'catatan'=>'KAS KELUAR PEMBAYARAN UTANG '. $catatan,
            );
            $jurnal['kas'] = $kas;
        }
        $utang = array(
            'akunId'=> $payload->supplier['akun_utang_id'], // UTANG DAGANG SUPPLIER
            'namaJenis'=>'DEBIT',
            'saldo'=>$nominal,
            'catatan'=>'PEMBAYARAN UTANG '. $catatan,
        );
        $jurnal['utang'] = $utang;
        
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
