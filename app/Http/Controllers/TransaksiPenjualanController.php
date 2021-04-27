<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Barang;
use App\Models\Kontak;
use App\Models\TransaksiPenjualan;
use App\Models\DetailPenjualan;
use App\Models\User;



class TransaksiPenjualanController extends Controller
{
    public function index($dd, $ddd){
        $output = [];
        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));
        $master = DB::table('master_penjualan')
        ->where('created_at','>',$dateawal)    
        ->where('created_at','<',$dateakhir)  
        ->where('deleted_at')    
        ->get();

        foreach ($master as $key => $value) {
        $invoice = [
            'diskon'=>$value->diskon,
            'grandTotal'=>$value->grand_total,
            'ongkir'=>$value->ongkir,
            'pajak'=>$value->pajak_keluaran,
            'total'=>$value->total,
        ];

        $user = $barang = User::find($value->user_id);

        $orders = DB::table('detail_penjualan')
        ->select('detail_penjualan.*', 'barang.nama as nama_barang')
        ->join('barang','detail_penjualan.kode_barang_id','=','barang.kode_barang')    
        ->where('master_penjualan_id','=',$value->id)    
        ->get();

        $pelanggan = DB::table('master_kontak')
        ->where('id','=',$value->kontak_id)
        ->first();

        $bank = DB::table('master_bank')
        ->where('id','=',$value->bank_id)
        ->first();

        $pembayaran = [
            'bank'=>$bank,
            'downPayment'=>$value->down_payment,
            'sisaPembayaran'=>$value->sisa_pembayaran,
            'jenisPembayaran' => $this->caraPembayaran($value->cara_pembayaran),
            'kredit'=>$value->kredit,
            'statusPembayaran'=>$this->metodePembayaran($value->metode_pembayaran),
            'tanggalJatuhTempo'=>$value->tanggal_jatuh_tempo,
        ];

        $data = [
            'id'=>$value->id,
            'nomorTransaksi'=>$value->nomor_transaksi,
            'tanggalTransaksi'=>$value->created_at,
            'invoice'=> $invoice,
            'orders'=>$orders,
            'pelanggan'=>$pelanggan,
            'pembayaran'=>$pembayaran,
            'user'=> $user

        ];

        $output[] = $data;
        }

        return response()->json($output, 200);
    }
 
    public function store(Request $request){
        $nomor_transaksi = $this->makeNomorTrx();

        if($request->pembayaran['statusPembayaran']['value'] == 2){
            $sisa_pembayaran = $request->invoice['grandTotal'];
        }else if($request->pembayaran['statusPembayaran']['value'] == 1){
            $sisa_pembayaran = (float)$request->invoice['grandTotal'] - (float)$request->pembayaran['downPayment'];
        }else{
            $sisa_pembayaran = 0;
        }

        $data = TransaksiPenjualan::create([
            'nomor_transaksi'=> $nomor_transaksi,
            'kontak_id' => $this->cekPelanggan($request->pelanggan),
            'total' => $request->invoice['total'],
            'diskon' => $request->invoice['diskon'],
            'ongkir' => $request->invoice['ongkir'],
            'pajak_keluaran' => $request->invoice['pajak'],
            'grand_total' => $request->invoice['grandTotal'],
            'metode_pembayaran' => $request->pembayaran['statusPembayaran']['title'],
            'kredit' => $request->pembayaran['kredit'],
            'down_payment' => $request->pembayaran['downPayment'],
            'sisa_pembayaran' => $sisa_pembayaran,
            'cara_pembayaran' => $request->pembayaran['jenisPembayaran']['title'],
            'bank_id' => $request->pembayaran['bank'] ? $request->pembayaran['bank']['value'] : null,
            'tanggal_jatuh_tempo' => $request->pembayaran['tanggalJatuhTempo'],
            'retur' => 2,
            'user_id' => 1,
        ]);
        $id = $data->id;
        if($id){
            foreach ($request->orders as $key => $value) {
                $detail = DetailPenjualan::create([
                    'master_penjualan_id'=> $id,
                    'kode_barang_id' => $value['kode_barang'],
                    'jumlah' => $value['jumlah'],
                    'harga' => $value['harga'],
                    'diskon' => $value['diskon'],
                    'total' => ($value['jumlah'] * $value['harga']) - $value['diskon'],
                ]);
            }
        }
        $this->postJurnal(
            $nomor_transaksi,
            'PENJUALAN NOMOR INVOICE #'.$nomor_transaksi,
            $request->invoice['total'],
            $request->invoice['pajak'],
            $request->invoice['ongkir'],
            $request->invoice['diskon'],
            $request->pembayaran['kredit'],
            $request->pembayaran['downPayment'],
            $sisa_pembayaran,
        );

        return response()->json($data, 200);
    }

    
    public function getDetailTransaksiByBarang($kode_barang){
        $master = DB::table('detail_penjualan')
        ->select('detail_penjualan.*', 'master_penjualan.nomor_transaksi as nomor_transaksi','master_penjualan.sisa_pembayaran','master_kontak.nama as nama_pelanggan')
        ->where('kode_barang_id','=',$kode_barang)    
        ->join('master_penjualan','detail_penjualan.master_penjualan_id','=','master_penjualan.id')    
        ->join('master_kontak','master_penjualan.kontak_id','=','master_kontak.id')    
        ->get();

        return response()->json($master, 200);
    }

    //  FUNGSI STANDARD
    public function metodePembayaran($text){
        $title = '';
        $value = 0;
        if($text == 'Lunas'){
            $title = $text;
            $value = 0;
        }else if($text == 'Kredit'){
            $title = $text;
            $value = 1;
        }else if($text == 'Cash On Delivery (COD)'){
            $title = $text;
            $value = 2;
        }
        return [
            'title'=> $title,
            'value' => $value
        ];
    }

    public function caraPembayaran($text){
        $title = '';
        $value = 0;
        if($text == 'Tunai'){
            $title = $text;
            $value = 0;
        }else if($text == 'Transfer'){
            $title = $text;
            $value = 1;
        }
        return [
            'title'=> $title,
            'value' => $value
        ];
    }

    public function makeNomorTrx(){
        $data = TransaksiPenjualan::all();
        $output = collect($data)->last();
        $date = date("dmy");

        if($output){
            $dd = $output->nomor_transaksi;
            $str = explode('-', $dd);

            if($str[1] == $date){
                $last_prefix = $str[2]+ 1;
                return 'BBM-'.$date.'-'.$last_prefix;
            }

            return 'BBM-'.$date.'-'.'1';
           
        }
        return 'BBM-'.$date.'-'.'1';      
    }

    public function cekPelanggan($data){
        if($data['id'] == null || $data['id'] == '' ){
            $kontak = Kontak::create([
                'nama'=> $data['nama'],
                'tipe'=> 'PELANGGAN',
                'alamat'=> $data['alamat'],
                'telepon'=> $data['nomorTelepon'],
                'wic'=> 1,
            ]);
            return $kontak->id;
        }
        return $data['id'];
    }


    // JURNAL
    
    public function postJurnal($nomor_transaksi,$keterangan, $penjualan, $pajak = 0, $ongkir = 0, $diskon = 0, $piutang = false, $dp=0, $sisa_pembayaran=0){
        $reqJurnal = Http::get('http://127.0.0.1:8080/api/jurnal/reqnomorjurnal');
        $nomorJurnal = $reqJurnal->json();
        $kas = $penjualan + $pajak + $ongkir;
        if($piutang == false){
            $kas = Http::post('http://127.0.0.1:8080/api/jurnal/store/', [
                'reff'=>$nomor_transaksi,
                'nomor_jurnal'=>$nomorJurnal,
                'master_akun_id'=>'3', // KAS
                'nominal'=>$kas,
                'jenis'=>'DEBIT',
                'keterangan'=>'PENERIMAAN KAS '. $keterangan,
            ]);
            $response['kas'] = $kas->json();

        }else{
            if($dp !== 0){
                $kas = Http::post('http://127.0.0.1:8080/api/jurnal/store/', [
                    'reff'=>$nomor_transaksi,
                    'nomor_jurnal'=>$nomorJurnal,
                    'master_akun_id'=>'3', // KAS
                    'nominal'=>$dp,
                    'jenis'=>'DEBIT',
                    'keterangan'=>'PENERIMAAN KAS DOWN PAYMENT '. $keterangan,
                ]);
                $response['kas'] = $kas->json();

            }
            $piutang = Http::post('http://127.0.0.1:8080/api/jurnal/store/', [
                'reff'=>$nomor_transaksi,
                'nomor_jurnal'=>$nomorJurnal,
                'master_akun_id'=>'5', // PIUTANG DAGANG
                'nominal'=>$sisa_pembayaran,
                'jenis'=>'DEBIT',
                'keterangan'=>'PIUTANG '. $keterangan,
            ]);
            $response['piutang'] = $piutang->json();

        }

        $penjualan = Http::post('http://127.0.0.1:8080/api/jurnal/store/', [
            'reff'=>$nomor_transaksi,
            'nomor_jurnal'=>$nomorJurnal,
            'master_akun_id'=>'32', // PENJUALAN
            'nominal'=>$penjualan,
            'jenis'=>'KREDIT',
            'keterangan'=>$keterangan,
        ]);
        $response['penjualan'] = $penjualan->json();


        if($pajak !== 0){
            $pajak = Http::post('http://127.0.0.1:8080/api/jurnal/store/', [
                'reff'=>$nomor_transaksi,
                'nomor_jurnal'=>$nomorJurnal,
                'master_akun_id'=>'26', // PAJAK KELUARAN
                'nominal'=>$pajak,
                'jenis'=>'KREDIT',
                'keterangan'=>'PAJAK KELUARAN '. $keterangan,
            ]);
            $response['pajak'] = $pajak->json();
        }
        if($ongkir !== 0){
            $ongkir = Http::post('http://127.0.0.1:8080/api/jurnal/store/', [
                'reff'=>$nomor_transaksi,
                'nomor_jurnal'=>$nomorJurnal,
                'master_akun_id'=>'33', // AKUN PENDAPATAN LAIN LAIN
                'nominal'=>$ongkir,
                'jenis'=>'KREDIT',
                'keterangan'=>'ONGKIR '. $keterangan,
            ]);
            $response['ongkir'] = $ongkir->json();
        }
        if($diskon !== 0){
            $diskon = Http::post('http://127.0.0.1:8080/api/jurnal/store/', [
                'reff'=>$nomor_transaksi,
                'nomor_jurnal'=>$nomorJurnal,
                'master_akun_id'=>'35', // AKUN DISKON PENJUALAN
                'nominal'=>$diskon,
                'jenis'=>'DEBIT',
                'keterangan'=>'DISKON '. $keterangan,
            ]);
            $response['diskon'] = $diskon->json();
        }

        return response()->json($response, 200);
    }
}
