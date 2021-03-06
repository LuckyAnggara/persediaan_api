<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Pembayaran;
use App\Models\Kontak;
use App\Models\TransaksiPembelian;
use App\Models\DetailPembelian;
use App\Models\KartuPersediaan;
use App\Models\User;
use App\Models\HargaBeli;
use App\Models\Gudang;


class TransaksiPembelianController extends Controller
{

    
    public function index($cabang, $dd,$ddd){
        
        $output=[];

        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));
        $data = DB::table('master_pembelian')
        // ->where('created_at','>',$dateawal)    
        // ->where('created_at','<',$dateakhir)
        ->where('cabang_id', $cabang == 0 ? '!=' : '=', $cabang)    
        ->where('deleted_at');
        if($dd == "null" && $ddd == "null"){
            $master = $data->get();
        }else{
            $master = $data->whereBetween('created_at', [$dateawal, $dateakhir])->get();
        }

        $output = $this->detailDataBatch($master);

        return response()->json($output, 200);
    }

    public function getTransaksi($id, $dd = false){
        $data = TransaksiPembelian::findorfail($id);
        $output = $this->detailDataSingle($data);

        if($dd){
            return response()->json($output, 200);
        }
        return $output;

    }

    public function detailDataBatch($master){
        $output = [];
        foreach ($master as $key => $value) {
            $invoice = [
                'diskon'=>$value->diskon,
                'grandTotal'=>$value->grand_total,
                'ongkir'=>$value->ongkir,
                'pajak'=>$value->pajak_masukan,
                'total'=>$value->total,
            ];
    
            $user = User::join('master_pegawai','users.pegawai_id','=','master_pegawai.id')
            ->where('users.id','=',$value->user_id)->first(['users.*', 'master_pegawai.nama']);
    
            $orders = DB::table('detail_pembelian')
            ->select('detail_pembelian.*', 'barang.nama as nama_barang')
            ->join('barang','detail_pembelian.barang_id','=','barang.id')    
            ->where('master_pembelian_id','=',$value->id)    
            ->get();
    
            $supplier = DB::table('master_kontak')
            ->where('id','=',$value->kontak_id)
            ->first();

            $list_pembayaran = Pembayaran::where('pembelian_id', $value->id)->get();
    

            $pembayaran = [
                'downPayment'=>$value->down_payment,
                'sisaPembayaran'=>$value->sisa_pembayaran,
                'jenisPembayaran' => $this->caraPembayaran($value->cara_pembayaran),
                'kredit'=>$value->kredit,
                'statusPembayaran'=>$this->metodePembayaran($value->metode_pembayaran),
                'tanggalJatuhTempo'=>$value->tanggal_jatuh_tempo,
                'listPembayaran' => $list_pembayaran,
            ];
    
            $data = [
                'id'=>$value->id,
                'retur'=>$value->retur,
                'catatan'=>$value->catatan,
                'nomorTransaksi'=>$value->nomor_transaksi,
                'tanggalTransaksi'=>$value->created_at,
                'nomorJurnal' => $value->nomor_jurnal,
                'invoice'=> $invoice,
                'orders'=>$orders,
                'supplier'=>$supplier,
                'pembayaran'=>$pembayaran,
                'user'=> $user
    
            ];
    
            $output[] = $data;
            }
            
            return $output;
    }

    public function detailDataSingle($value){ // DETAIL DATA

        $invoice = [
            'diskon'=>$value->diskon,
            'grandTotal'=>$value->grand_total,
            'ongkir'=>$value->ongkir,
            'pajak'=>$value->pajak_masukan,
            'total'=>$value->total,
        ];

        $user = User::join('master_pegawai','users.pegawai_id','=','master_pegawai.id')
        ->where('users.id','=',$value->user_id)->first(['users.*', 'master_pegawai.nama']);

        $orders = DB::table('detail_pembelian')
        ->select('detail_pembelian.*', 'barang.nama as nama_barang')
        ->join('barang','detail_pembelian.barang_id','=','barang.id')    
        ->where('master_pembelian_id','=',$value->id)    
        ->get();

        $supplier = DB::table('master_kontak')
        ->where('id','=',$value->kontak_id)
        ->first();

        $list_pembayaran = Pembayaran::where('pembelian_id', $value->id)->get();

        $pembayaran = [
            'downPayment'=>$value->down_payment,
            'sisaPembayaran'=>$value->sisa_pembayaran,
            'jenisPembayaran' => $this->caraPembayaran($value->cara_pembayaran),
            'kredit'=>$value->kredit,
            'statusPembayaran'=>$this->metodePembayaran($value->metode_pembayaran),
            'tanggalJatuhTempo'=>$value->tanggal_jatuh_tempo,
            'listPembayaran' => $list_pembayaran,
        ];

        $data = [
            'id'=>$value->id,
            'retur'=>$value->retur,
            'nomorTransaksi'=>$value->nomor_transaksi,
            'catatan'=>$value->catatan,
            'tanggalTransaksi'=>$value->created_at,
            'nomorJurnal' => $value->nomor_jurnal,
            'invoice'=> $invoice,
            'orders'=>$orders,
            'supplier'=>$supplier,
            'pembayaran'=>$pembayaran,
            'user'=> $user

        ];

        $data;
        return $data;
}

    public function getDetailTransaksiByBarang(Request $payload){
        $id_barang = $payload->input('id_barang');
        $cabang = $payload->input('cabang');

        $master = DB::table('detail_pembelian')
        ->select('master_pembelian.*')
        ->where('barang_id','=',$id_barang)    
        ->where('cabang_id', $cabang == 0 ? '!=' : '=', $cabang)
        ->join('master_pembelian','detail_pembelian.master_pembelian_id','=','master_pembelian.id')    
        ->get(); 

        $output = $this->detailDataBatch($master);
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
        
        public function store(Request $payload){

            $gudang = Gudang::where('cabang_id', $payload->user['cabang_id'])->where('utama', '1')->first();
    
            if($payload->pembayaran['statusPembayaran']['value'] == 2){
                $sisa_pembayaran = $payload->invoice['grandTotal'];
            }else if($payload->pembayaran['statusPembayaran']['value'] == 1){
                $sisa_pembayaran = (float)$payload->invoice['grandTotal'] - (float)$payload->pembayaran['downPayment'];
            }else{
                $sisa_pembayaran = 0;
            }

            $cek = TransaksiPembelian::where('nomor_transaksi', $payload->nomorTransaksi)->get()->count();
            if($cek > 0){
                return response()->json(200, 'ada');
            }

            // POSTING JURNAL
            $jurnalPembelian = $this->postJurnal($payload, $sisa_pembayaran, $payload->nomorTransaksi, $gudang);
            // JIKA SUKSES LANJUT
            if($jurnalPembelian['nomor_jurnal']){
                $data = TransaksiPembelian::create([
                    'nomor_transaksi'=> $payload->nomorTransaksi,
                    'kontak_id' => $this->cekSupplier($payload->supplier),
                    'total' => $payload->invoice['total'],
                    'diskon' => $payload->invoice['diskon'],
                    'ongkir' => $payload->invoice['ongkir'],
                    'pajak_masukan' => $payload->invoice['pajak'],
                    'grand_total' => $payload->invoice['grandTotal'],
                    'metode_pembayaran' => $payload->pembayaran['statusPembayaran']['title'],
                    'kredit' => $payload->pembayaran['kredit'],
                    'down_payment' => $payload->pembayaran['downPayment'],
                    'sisa_pembayaran' => $sisa_pembayaran,
                    'cara_pembayaran' => $payload->pembayaran['jenisPembayaran']['title'],
                    'tanggal_jatuh_tempo' => $payload->pembayaran['tanggalJatuhTempo'],
                    'retur' => 2, // 1 IYA 2 TIDAK
                    'user_id' => $payload->user['id'],
                    'cabang_id'=>$payload->user['cabang_id'],
                    'nomor_jurnal'=> $jurnalPembelian['nomor_jurnal'],
                    'created_at'=> $payload->tanggalTransaksi,
                    'catatan'=> $payload->catatan,
                    'updated_at'=> $payload->tanggalTransaksi,
                ]);
                if($payload->pembayaran['kredit'] == false){
                    $nominal = $payload->invoice['total'] + $payload->invoice['pajak'] +$payload->invoice['ongkir'];
                    $catatan = 'LUNAS';
                }else{
                    $nominal = $payload->pembayaran['downPayment'];
                    $catatan = 'PEMBAYARAN DOWN PAYMENT';
                }
                $id = $data->id;
                $pembayaran = Pembayaran::create([
                    'pembelian_id'=>$id,
                    'nominal'=> $nominal,
                    'catatan'=>$catatan,
                    'cara_pembayaran'=> $payload->pembayaran['jenisPembayaran']['title'],
                    'cabang_id'=>$payload->user['cabang_id'],
                    'user_id'=>$payload->user['id'],
                    'nomor_jurnal'=> $jurnalPembelian['nomor_jurnal'],
                ]);
                if($id){
                    foreach ($payload->orders as $key => $value) {
                        $detail = DetailPembelian::create([
                            'master_pembelian_id'=> $id,
                            'barang_id' => $value['id_barang'],
                            'jumlah' => $value['jumlah'],
                            'harga' => $value['harga'],
                            'diskon' => $value['diskon'],
                            'total' => ($value['jumlah'] * $value['harga']) - $value['diskon'],
                        ]);
                        $this->debitPersediaan($value, $payload, $gudang);
                    }
    
                    foreach ($payload->orders as $key => $value) {
                        $hargaBeli = HargaBeli::create([
                            'master_barang_id' => $value['id_barang'],
                            'saldo_awal' => $value['jumlah'],
                            'saldo' => $value['jumlah'],
                            'harga_beli' => $value['harga'],
                            'jenis' => 'PEMBELIAN_'.$id,
                            'user_id' =>  $payload->user['id'],
                            'cabang_id'=> $payload->user['cabang_id'],
                            'gudang_id'=> $gudang->id
                        ]);
                    }
                }
                $response = 200;
            }else{
                $response = 404;
            }

    
            return response()->json($data, $response);
        }

        public function destroy($id){
            $output = [];
            $master = TransaksiPembelian::findOrFail($id);
            $master->delete();
            // DETAIL PENJUALAN
            $detail = DetailPembelian::where('master_pembelian_id', $master->id)->get();
            foreach ($detail as $key => $value) {
               $dd = DetailPembelian::findOrFail($value->id);
               $dd->delete();
            }
            // PERSEDIAAN
            $persediaan = KartuPersediaan::where('nomor_transaksi', 'PEMBELIAN#'.$master->nomor_transaksi)->get();
            foreach ($persediaan as $key => $value) {
                $dd = KartuPersediaan::findOrFail($value->id);
                $dd->delete();
            }

            // HARGA BELI
            $harga_beli = HargaBeli::where('jenis', 'PEMBELIAN_'.$master->id)->get();
            foreach ($harga_beli as $key => $harga_beli) {
                $dd = HargaBeli::findOrFail($harga_beli->id);
                $dd->delete();
            }
            // JURNAL

            $pembayaran = Pembayaran::where('pembelian_id', $master->id)->get();
            foreach ($pembayaran as $key => $pembayaran) {
                $dd = Pembayaran::findOrFail($pembayaran->id);
                $dd->delete();
                Http::delete(keuanganBaseUrl().'jurnal/delete/'.$pembayaran->nomor_jurnal);
            }


            $output['master'] = $master;
            return response()->json($output, 200);
        }
    
        public function retur(Request $payload){
            $output = [];
            $newpersediaan = [];
            $master = TransaksiPembelian::findOrFail($payload->id);
            $master->retur = 'Iya';
            $master->save();
    
            // PERSEDIAAN
            $persediaan = KartuPersediaan::where('nomor_transaksi', 'PEMBELIAN#'.$master->nomor_transaksi)->get();
            foreach ($persediaan as $key => $value) {
                $jenis = 'DEBIT';
                if($value->jenis == 'DEBIT'){
                    $jenis = 'KREDIT';
                }
                $dd = KartuPersediaan::create([
                    'nomor_transaksi'=> 'RETUR_'.$value->nomor_transaksi,
                    'master_barang_id' => $value->master_barang_id,
                    'jenis' => $jenis,
                    'jumlah' => $value->jumlah,
                    'harga' => $value->harga,
                    'catatan' => 'RETUR '.$value->catatan,
                    'user_id' => $value->user_id,
                    'gudang_id'=>$value->gudang_id,
                    'cabang_id'=>$value->cabang_id,
                    'created_at' =>$value->created_at,
                    'updated_at' =>$value->updated_at,
                ]);
                $newpersediaan[] = $dd;
            }

            // HARGA BELI
            $harga_beli = HargaBeli::where('jenis', 'PEMBELIAN_'.$master->id)->get();

            foreach ($harga_beli as $key => $harga_beli) {
                $harga_beli->delete();
                $newHargaBeli[] = $harga_beli;
            }

            $pembayaran = Pembayaran::where('pembelian_id', $master->id)->get();

            $retur = [];
            foreach ($pembayaran as $key => $pembayaran) {
                $do = Http::get(keuanganBaseUrl().'jurnal/retur/'.$pembayaran->nomor_jurnal);
                $retur[] = $do->json();
            }

            // JURNAL
            $output['master'] = $master;
            $output['harga_beli'] = $newHargaBeli;
            $output['persediaan'] = $newpersediaan;
            $output['jurnal'] = $retur;
            return response()->json($output, 200);
        }

        public function postJurnal($payload, $sisa_pembayaran = 0, $nomor_transaksi, $gudang){
            $jurnal = [];
            $catatan = 'PEMBELIAN NOMOR TRANSAKSI #'. $nomor_transaksi;
            $utang = $payload->pembayaran['kredit'];
            $dp =  $payload->pembayaran['downPayment'];
            $sisa_pembayaran= $sisa_pembayaran;
            $kas = $payload->invoice['total'] + $payload->invoice['pajak'] +$payload->invoice['ongkir'];
    
            // TRANSAKSI PEMBELIAN NYA
            if($utang == false){
                $kas = array(
                    'akunId'=> $payload->user['cabang']['kode_akun_id'], // KAS BESAR
                    'namaJenis'=> 'KREDIT',
                    'saldo'=>$kas,
                    'catatan'=>'KAS KELUAR '. $catatan,
                );
                $jurnal['kas'] = $kas;
            }else{
                if($dp !== 0){
                    $kas = array(
                        'akunId'=> $payload->user['cabang']['kode_akun_id'], // KAS BESAR
                        'namaJenis'=>'KREDIT',
                        'saldo'=>$dp,
                        'catatan'=>'DOWN PAYMENT '. $catatan,
                    );
                $jurnal['kas'] = $kas;
                }
                $utang = array(
                    'akunId'=> $payload->supplier['akun_utang_id'], // UTANG DAGANG
                    'namaJenis'=>'KREDIT',
                    'saldo'=>$sisa_pembayaran,
                    'catatan'=>'utang '. $catatan,
                );
                $jurnal['utang'] = $utang;
            }
            $persediaan = array(
                'akunId'=>$gudang->kode_akun_id, // PERSEDIAAN
                'namaJenis'=>'debit',
                'saldo'=>$payload->invoice['total'],
                'catatan'=> $catatan,
            );
            $jurnal['persediaan'] = $persediaan;
    
            if($payload->invoice['pajak'] !== 0){
                $pajak = array(
                    'akunId'=>'9', // PAJAK MASUKAN
                    'namaJenis'=>'DEBIT',
                    'saldo'=>$payload->invoice['pajak'],
                    'catatan'=>'PAJAK MASUKAN '. $catatan,
                );
                $jurnal['pajak'] = $pajak;
            }
            if($payload->invoice['ongkir']  !== 0){
                $ongkir = array(
                    'akunId'=>'43', // ONGKIR BEBAN LAIN - LAIN
                    'namaJenis'=>'DEBIT',
                    'saldo'=>$payload->invoice['ongkir'],
                    'catatan'=>'ONGKIR '. $catatan,
                );
                $jurnal['ongkir'] = $ongkir;
            }
            if($payload->invoice['diskon'] !== 0){
                $diskon = array(
                    'akunId'=>'39', // DISKON PEMBELIAN
                    'namaJenis'=>'KREDIT',
                    'saldo'=>$payload->invoice['diskon'],
                    'catatan'=>'DISKON '. $catatan,
                );
                $jurnal['diskon'] = $diskon;
            }

            // END TRANSAKSI PEMBELIAN NYA

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

        public function cekSupplier($data){
            if($data['id'] == null || $data['id'] == '' ){
                $kontak = Kontak::create([
                    'nama'=> $data['nama'],
                    'tipe'=> 'SUPPLIER',
                    'alamat'=> $data['alamat'],
                    'telepon'=> $data['nomorTelepon'],
                    'wic'=> 1,
                ]);
                return $kontak->id;
            }
            return $data['id'];
        }

        public function debitPersediaan($data, $payload, $gudang){
            $detail = KartuPersediaan::create([
                'nomor_transaksi'=> 'PEMBELIAN#'.$payload->nomorTransaksi,
                'master_barang_id' => $data['id_barang'],
                'jenis' => 'DEBIT',
                'jumlah' => $data['jumlah'],
                'harga' => $data['harga'],
                'catatan' => 'PEMBELIAN BARANG NOMOR TRANSAKSI #'. $payload->nomorTransaksi,
                'gudang_id'=> $gudang->id,
                'user_id' => $payload->user['id'],
                'cabang_id'=>$payload->user['cabang_id'],
            ]);
        }

    public function cekNomorTransaksi(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $nomor_transaksi = $payload->input('nomor_transaksi');

        $data = TransaksiPembelian::where('cabang_id', $cabang_id)->where('nomor_transaksi', $nomor_transaksi)->get();
        
        if($data->count() > 0){
            $messages['title'] = 'Nomor Transaksi #'.$nomor_transaksi. ' sudah ada di Database';
            $messages['value'] = true;
        }else{
            $messages['title'] = '';
            $messages['value'] = false;
        }

        return response()->json($messages, 200);

    }
}
