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
use App\Models\KartuPersediaan;
use App\Models\Pegawai;



class TransaksiPenjualanController extends Controller
{
    public function index($cabang, $dd, $ddd){
        $output = [];
        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));
        $master = DB::table('master_penjualan')
        ->where('created_at','>',$dateawal)    
        ->where('created_at','<',$dateakhir)
        ->where('cabang_id', $cabang == 0 ? '!=' : '=', $cabang)    
        ->where('deleted_at')    
        ->get();

        $output = $this->detailData($master);
        return response()->json($output, 200);
    }

    public function detailData($master){ // DETAIL DATA
        $output = [];
        foreach ($master as $key => $value) {
            $invoice = [
                'diskon'=>$value->diskon,
                'grandTotal'=>$value->grand_total,
                'ongkir'=>$value->ongkir,
                'pajak'=>$value->pajak_keluaran,    
                'total'=>$value->total,
            ];
    
            $user = User::join('master_pegawai','users.pegawai_id','=','master_pegawai.id')
            ->where('users.id','=',$value->user_id)->first(['users.*', 'master_pegawai.nama']);

            $sales = Pegawai::where('id',$value->sales_id)->first();
    
            $orders = DB::table('detail_penjualan')
            ->select('detail_penjualan.*', 'barang.nama as nama_barang','detail_penjualan.kode_barang_id as kode_barang','barang.harga_beli as modal','barang.jenis','barang.id as id_barang')
            ->join('barang','detail_penjualan.kode_barang_id','=','barang.kode_barang')    
            ->where('detail_penjualan.deleted_at')    
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
                'retur'=>$value->retur,
                'nomorTransaksi'=>$value->nomor_transaksi,
                'tanggalTransaksi'=>$value->created_at,
                'nomorJurnal' => $value->nomor_jurnal,
                'invoice'=> $invoice,
                'orders'=>$orders,
                'pelanggan'=>$pelanggan,
                'pembayaran'=>$pembayaran,
                'user'=> $user,
                'sales'=>$sales
    
            ];
    
            $output[] = $data;
            }

            return $output;
    }
 
    public function store(Request $payload){
        $nomor_transaksi = $this->makeNomorTrx();
        if($payload->pembayaran['statusPembayaran']['value'] == 2){
            $sisa_pembayaran = $payload->invoice['grandTotal'];
        }else if($payload->pembayaran['statusPembayaran']['value'] == 1){
            $sisa_pembayaran = (float)$payload->invoice['grandTotal'] - (float)$payload->pembayaran['downPayment'];
        }else{
            $sisa_pembayaran = 0;
        }
        // POSTING JURNAL
        $jurnalPenjualan = $this->postJurnalPenjualan($payload, $sisa_pembayaran, $nomor_transaksi);
        // $jurnalPenjualan['nomor_jurnal'] = 11111;
        // JIKA SUKSES LANJUT
        if($jurnalPenjualan['nomor_jurnal']){
            $data = TransaksiPenjualan::create([
                'nomor_transaksi'=> $nomor_transaksi,
                'kontak_id' => $this->cekPelanggan($payload->pelanggan),
                'total' => $payload->invoice['total'],
                'diskon' => $payload->invoice['diskon'],
                'ongkir' => $payload->invoice['ongkir'],
                'pajak_keluaran' => $payload->invoice['pajak'],
                'grand_total' => $payload->invoice['grandTotal'],
                'metode_pembayaran' => $payload->pembayaran['statusPembayaran']['title'],
                'kredit' => $payload->pembayaran['kredit'],
                'down_payment' => $payload->pembayaran['downPayment'],
                'sisa_pembayaran' => $sisa_pembayaran,
                'cara_pembayaran' => $payload->pembayaran['jenisPembayaran']['title'],
                'bank_id' => $payload->pembayaran['bank'] ? $payload->pembayaran['bank']['value'] : null,
                'tanggal_jatuh_tempo' => $payload->pembayaran['tanggalJatuhTempo'],
                'retur' => 2,
                'sales_id' => $payload->sales['value'],
                'user_id' => $payload->user['id'],
                'cabang_id'=>$payload->user['cabang_id'],
                'nomor_jurnal'=> $jurnalPenjualan['nomor_jurnal'],
            ]);
            $id = $data->id;
            // $id = 5;

            if($id){
                $hargaPokokPenjualan = 0;
                foreach ($payload->orders as $key => $value) {
                    $detail = DetailPenjualan::create([
                        'master_penjualan_id'=> $id,
                        'kode_barang_id' => $value['kode_barang'],
                        'jumlah' => $value['jumlah'],
                        'harga' => $value['harga'],
                        'diskon' => $value['diskon'],
                        'total' => ($value['jumlah'] * $value['harga']) - $value['diskon'],
                    ]);
                    $hargaPokokPenjualan += $this->kreditPersediaan($value, $payload, $nomor_transaksi);
                }
            }

            // POST JURNAL HPP
            $jurnalHPP = $this->postJurnalHpp($payload, $hargaPokokPenjualan,$jurnalPenjualan['nomor_jurnal'], $nomor_transaksi);
        }
        // $data = '';
        // $data->jurnal = $jurnal;
        return response()->json($data, 200);
    }

    public function update(Request $payload, $id){

        if($payload->pembayaran['statusPembayaran']['value'] == 2){
            $sisa_pembayaran = $payload->invoice['grandTotal'];
        }else if($payload->pembayaran['statusPembayaran']['value'] == 1){
            $sisa_pembayaran = (float)$payload->invoice['grandTotal'] - (float)$payload->pembayaran['downPayment'];
        }else{
            $sisa_pembayaran = 0;
        }

        if(TransaksiPenjualan::where('id', $id)->exists()){


            // DELETE JURNAL
            $cek = Http::delete(keuanganBaseUrl().'jurnal/delete/'.$payload->nomorJurnal);
            $jurnalBaru = $this->postJurnalPenjualan($payload, $sisa_pembayaran, $payload->nomorTransaksi);
            if($jurnalBaru['nomor_jurnal']){
                $master = TransaksiPenjualan::find($id);
                $master->nomor_transaksi = $payload->nomorTransaksi;
                $master->kontak_id = $this->cekPelanggan($payload->pelanggan);
                $master->total = $payload->invoice['total'];
                $master->diskon = $payload->invoice['diskon'];
                $master->ongkir = $payload->invoice['ongkir'];
                $master->pajak_keluaran = $payload->invoice['pajak'];
                $master->grand_total = $payload->invoice['grandTotal'];
                $master->metode_pembayaran = $payload->pembayaran['statusPembayaran']['title'];
                $master->kredit = $payload->pembayaran['kredit'];
                $master->down_payment = $payload->pembayaran['downPayment'];
                $master->sisa_pembayaran = $sisa_pembayaran;
                $master->cara_pembayaran = $payload->pembayaran['jenisPembayaran']['title'];
                $master->bank_id = $payload->pembayaran['bank'] ? $payload->pembayaran['bank']['value'] : null;
                $master->tanggal_jatuh_tempo = $payload->pembayaran['tanggalJatuhTempo'];
                $master->retur = 2;
                $master->sales_id = $payload->sales['id'];
                $master->user_id = $payload->user['id'];
                $master->cabang_id = $payload->user['cabang_id'];
                $master->nomor_jurnal = $jurnalBaru['nomor_jurnal'];
                $master->save();
            }

            $detailPenjualan_del = DetailPenjualan::where('master_penjualan_id', $id)->get();
            foreach ($detailPenjualan_del as $key => $value) {
                $dd = DetailPenjualan::findOrFail($value->id);
                $dd->delete();
             }
            $kartuPersediaan_del = KartuPersediaan::where('nomor_transaksi', $payload->nomorTransaksi)->get();
            foreach ($kartuPersediaan_del as $key => $value) {
                $dd = KartuPersediaan::findOrFail($value->id);
                $dd->delete();
            }

            $hargaPokokPenjualan = 0;
                foreach ($payload->orders as $key => $value) {
                    $detail = DetailPenjualan::create([
                        'master_penjualan_id'=> $id,
                        'kode_barang_id' => $value['kode_barang'],
                        'jumlah' => $value['jumlah'],
                        'harga' => $value['harga'],
                        'diskon' => $value['diskon'],
                        'total' => ($value['jumlah'] * $value['harga']) - $value['diskon'],
                    ]);
                    $hargaPokokPenjualan += $this->kreditPersediaan($value, $payload, $payload->nomorTransaksi);
                }
            // POST JURNAL HPP
            $jurnalHPP = $this->postJurnalHpp($payload, $hargaPokokPenjualan,$jurnalBaru['nomor_jurnal'], $payload->nomorTransaksi);


            return response()->json(['message'=> $cek->json()], 200);
        }else{
            return response()->json(['message'=> 'Gagal'], 404);
        }
    }

    public function destroy($id){
        $output = [];
        $master = TransaksiPenjualan::findOrFail($id);
        $master->delete();
        // DETAIL PENJUALAN
        $detail = DetailPenjualan::where('master_penjualan_id', $master->id)->get();
        foreach ($detail as $key => $value) {
           $dd = DetailPenjualan::findOrFail($value->id);
           $dd->delete();
        }
        // PERSEDIAAN
        $persediaan = KartuPersediaan::where('nomor_transaksi', $master->nomor_transaksi)->get();
        foreach ($persediaan as $key => $value) {
            $dd = KartuPersediaan::findOrFail($value->id);
            $dd->delete();
        }
        // JURNAL
        $output['master'] = $master;
        $output['detail'] = $detail;
        $output['persediaan'] = $persediaan;
        $output['jurnal'] = $master->nomor_jurnal;
        return response()->json($output, 200);

        // BAGIAN JURNAL LANGSUNG API DI FRONTEND VUE NYA
        // NGGA VIA LARAVEL YG INI

    }

    public function retur(Request $payload){
        $output = [];
        $newpersediaan = [];
        $master = TransaksiPenjualan::findOrFail($payload->id);
        $master->retur = 'Iya';
        $master->save();

        // PERSEDIAAN
        $persediaan = KartuPersediaan::where('nomor_transaksi', $master->nomor_transaksi)->get();
        foreach ($persediaan as $key => $value) {
            $jenis = 'DEBIT';
            if($value->jenis == 'DEBIT'){
                $jenis = 'KREDIT';
            }
            $dd = KartuPersediaan::create([
                'nomor_transaksi'=> $value->nomor_transaksi,
                'master_barang_id' => $value->master_barang_id,
                'jenis' => $jenis,
                'jumlah' => $value->jumlah,
                'harga' => $value->harga,
                'catatan' => 'RETUR '.$value->catatan,
                'user_id' => $value->user_id,
                'cabang_id'=>$value->cabang_id,
                'created_at' =>$value->created_at,
                'updated_at' =>$value->updated_at,
            ]);
            $newpersediaan[] = $dd;
        }
        // JURNAL
        $output['master'] = $master;
        $output['persediaan'] = $newpersediaan;
        $output['jurnal'] = $master->nomor_jurnal;
        return response()->json($output, 200);

    }
    
    // JURNAL PENJUALAN
    public function postJurnalPenjualan($payload, $sisa_pembayaran = 0, $nomor_transaksi){
        $jurnal = [];
        $catatan = 'PENJUALAN NOMOR TRANSAKSI #'. $nomor_transaksi;
        $piutang = $payload->pembayaran['kredit'];
        $dp =  $payload->pembayaran['downPayment'];
        $sisa_pembayaran= $sisa_pembayaran;
        $kas = $payload->invoice['total'] + $payload->invoice['pajak'] +$payload->invoice['ongkir'];

        if($piutang == false){
            $kas = array(
                'akunId'=> $payload->user['kode_akun_id'], // KAS KECIL KASIR
                'namaJenis'=> 'DEBIT',
                'saldo'=>$kas,
                'catatan'=>'KAS MASUK '. $catatan,
            );
            $jurnal['kas'] = $kas;
        }else{
            if($dp !== 0){
                $kas = array(
                    'akunId'=> $payload->user['kode_akun_id'], // KAS KECIL KASIR
                    'namaJenis'=>'DEBIT',
                    'saldo'=>$dp,
                    'catatan'=>'DOWN PAYMENT '. $catatan,
                );
            $jurnal['kas'] = $kas;
            }
            $piutang = array(
                'akunId'=>'5', // PIUTANG DAGANG
                'namaJenis'=>'DEBIT',
                'saldo'=>$sisa_pembayaran,
                'catatan'=>'PIUTANG '. $catatan,
            );
            $jurnal['piutang'] = $piutang;
        }
        $penjualan = array(
            'akunId'=>'32', // PENJUALAN
            'namaJenis'=>'KREDIT',
            'saldo'=>$payload->invoice['total'] + $payload->invoice['diskon'],
            'catatan'=> $catatan,
        );
        $jurnal['penjualan'] = $penjualan;

        if($payload->invoice['pajak'] !== 0){
            $pajak = array(
                'akunId'=>'26', // PAJAK KELUARAN
                'namaJenis'=>'KREDIT',
                'saldo'=>$payload->invoice['pajak'],
                'catatan'=>'PAJAK KELUARAN '. $catatan,
            );
            $jurnal['pajak'] = $pajak;
        }
        if($payload->invoice['ongkir']  !== 0){
            $ongkir = array(
                'akunId'=>'33', // PAJAK KELUARAN
                'namaJenis'=>'KREDIT',
                'saldo'=>$payload->invoice['ongkir'],
                'catatan'=>'ONGKIR '. $catatan,
            );
            $jurnal['ongkir'] = $ongkir;
        }
        if($payload->invoice['diskon'] !== 0){
            $diskon = array(
                'akunId'=>'35', // PAJAK KELUARAN
                'namaJenis'=>'DEBIT',
                'saldo'=>$payload->invoice['diskon'],
                'catatan'=>'DISKON '. $catatan,
            );
            $jurnal['diskon'] = $diskon;
        }
        $post = [
            'catatan' => $catatan,
            'tanggalTransaksi'=>  date("Y-m-d h:i:s"),
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
            'jurnal'=> $jurnal
        ];

        $output = Http::post(keuanganBaseUrl().'jurnal/store/', $post);

        return $output->json();
    }

    // JURNAL HPP
    public function postJurnalHpp($payload, $hargaPokokPenjualan, $nomor_jurnal, $nomor_transaksi){
        $jurnal = [];
        $catatan = 'PENJUALAN NOMOR TRANSAKSI #'. $nomor_transaksi;

        $hpp = array(
            'akunId'=> '44', // HPP
            'namaJenis'=> 'DEBIT',
            'saldo'=>$hargaPokokPenjualan,
            'catatan'=>$catatan,
         );
        $jurnal['hpp'] = $hpp;

        $persediaan = array(
            'akunId'=>'6', // PERSEDIAAN
            'namaJenis'=>'KREDIT',
            'saldo'=>$hargaPokokPenjualan,
            'catatan'=> $catatan,
        );
        $jurnal['persediaan'] = $persediaan;

        $post = [
            'nomor_jurnal' => $nomor_jurnal,
            'catatan' => $catatan,
            'tanggalTransaksi'=>  date("Y-m-d h:i:s"),
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
            'jurnal'=> $jurnal
        ];

        $output = Http::post(keuanganBaseUrl().'jurnal/store/', $post);

        return $output->json();
    }
    

    public function kreditPersediaan($data, $payload, $nomor_transaksi){


        $qty = $data['jumlah'];
        $hpp = 0;
        $harga_modal = $data['modal'];

        // CEK FIFO ATAU AVERAGE
        if($data['jenis'] == 'FIFO'){
            $barang = KartuPersediaan::where('master_barang_id', $data['id_barang'])
                                            ->where('jenis','=','DEBIT')
                                            ->whereBetween('created_at', [date("Y-01-01 00:00:01"), date("Y-12-31 23:59:59")])
                                            ->where('cabang_id','=', $payload->user['cabang_id'])
                                            ->orderBy('created_at', 'desc')
                                            ->limit(3);
            $persediaan = $barang->first();
            $count = $barang->count();
            if($count < 1){
                $hpp += $qty * $harga_modal;
            }else{
                $harga_modal = $persediaan->harga;
                $hpp += $qty * $harga_modal;
            }
        }else{
            $harga =  KartuPersediaan::where('master_barang_id', $data['id_barang'])
                                        ->where('jenis','=','DEBIT')
                                        ->whereBetween('created_at', [date("Y-01-01 00:00:01"), date("Y-12-31 23:59:59")])
                                        ->where('cabang_id','=', $payload->user['cabang_id'])
                                        ->limit(5)
                                        ->avg('harga');
            $harga_modal = $harga;
            $hpp += $qty * $harga_modal;
        }

        $detail = KartuPersediaan::create([
            'nomor_transaksi'=> $nomor_transaksi,
            'master_barang_id' => $data['id_barang'],
            'jenis' => 'KREDIT',
            'jumlah' => $qty,
            'harga' => round($harga_modal, 0),
            'catatan' => 'PENJUALAN BARANG NOMOR TRANSAKSI #'. $nomor_transaksi,
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
        ]);

        return round($hpp, 0); // RETURN HARGA POKOK PENJUALAN
    }


    
    public function getDetailTransaksiByBarang($kode_barang, $cabang){
        $master = DB::table('detail_penjualan')
        ->select('master_penjualan.*')
        ->where('kode_barang_id','=',$kode_barang)    
        ->where('cabang_id', $cabang == 0 ? '!=' : '=', $cabang)
        ->join('master_penjualan','detail_penjualan.master_penjualan_id','=','master_penjualan.id')    
        ->get(); 

        $output = $this->detailData($master);
        return response()->json($output, 200);
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

    public function index2(){
        return keuanganBaseUrl();
    }
}
