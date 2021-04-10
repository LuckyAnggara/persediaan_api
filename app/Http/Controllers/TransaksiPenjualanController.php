<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\Kontak;
use App\Models\TransaksiPenjualan;
use App\Models\DetailPenjualan;


class TransaksiPenjualanController extends Controller
{
    public function store(Request $request){
        $nomor_transaksi = $this->makeNomorTrx();
        $data = TransaksiPenjualan::create([
            'nomor_transaksi'=> $nomor_transaksi,
            'kontak_id' => 0,
            'total' => $request->invoice['total'],
            'diskon' => $request->invoice['diskon'],
            'ongkir' => $request->invoice['ongkir'],
            'pajak_masukan' => $request->invoice['pajak'],
            'grand_total' => $request->invoice['grandTotal'],
            'metode_pembayaran' => $request->pembayaran['statusPembayaran']['title'],
            'kredit' => $request->pembayaran['kredit'],
            'down_payment' => $request->pembayaran['downPayment'],
            'sisa_pambayaran' => (float)$request->invoice['grandTotal'] - (float)$request->pembayaran['downPayment'],
            'cara_pembayaran' => $request->pembayaran['jenisPembayaran']['title'],
            'bank_id' => $request->pembayaran['bank'] ? $request->pembayaran['bank']['value'] : null,
            'tanggal_jatuh_tempo' => $request->pembayaran['tanggalJatuhTempo'],
            'retur' => 2,
        ]);
        $id = $data->id;
        if($id){
            
            foreach ($request->orders as $key => $value) {
                $detail = DetailPenjualan::create([
                    'master_penjualan_id'=> $id,
                    'kode_barang_id' => $value['kode_barang'],
                    'jumlah' => $value['jumlah'],
                    'harga' => $value['harga_jual'],
                    'diskon' => $value['diskon'],
                    'total' => ($value['jumlah'] * $value['harga_jual']) - $value['diskon'],
                ]);
            }
        }

        return response()->json($data, 200);
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
}
