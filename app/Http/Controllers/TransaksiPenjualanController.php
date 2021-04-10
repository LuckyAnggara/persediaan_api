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
            'metode_pembayaran' => $request->catatan,
            'kredit' => $request->catatan,
            'down_payment' => $request->catatan,
            'sisa_pambayaran' => $request->catatan,
            'cara_pembayaran' => $request->catatan,
            'bank_id' => $request->catatan,
            'tanggal_jatuh_tempo' => $request->catatan,
            'retur' => 2,
        ]);

        return response()->json($request->invoice['total']);
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
        // return 'BBM-'.$date.'-'.'1';


      
    }
}
