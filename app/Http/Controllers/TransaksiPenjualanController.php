<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\Kontak;
use App\Models\TransaksiPenjualan;
use App\Models\DetailPenjualan;
use App\Models\User;


class TransaksiPenjualanController extends Controller
{

    public function index(){
        $master = DB::table('master_penjualan')
        // ->join('satuan_barang', 'barang.satuan_id', '=', 'satuan_barang.id')
        ->get();

        foreach ($master as $key => $value) {
        $invoice = [
            'diskon'=>$value->diskon,
            'grandTotal'=>$value->grand_total,
            'ongkir'=>$value->ongkir,
            'pajak'=>$value->pajak_masukan,
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
            'pajak_masukan' => $request->invoice['pajak'],
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
}
