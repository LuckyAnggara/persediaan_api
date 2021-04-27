<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Barang;
use App\Models\Kontak;
use App\Models\TransaksiPembelian;
use App\Models\DetailPembelian;
use App\Models\KartuPersediaan;
use App\Models\User;

class TransaksiPembelianController extends Controller
{
    public function index2(){
        return response()->json($data, 200);
    }
    
    public function index($dd,$ddd){
        
        $output=[];

        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));
        $master = DB::table('master_pembelian')
        ->where('created_at','>',$dateawal)    
        ->where('created_at','<',$dateakhir)  
        ->where('deleted_at')   
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

        $orders = DB::table('detail_pembelian')
        ->select('detail_pembelian.*', 'barang.nama as nama_barang')
        ->join('barang','detail_pembelian.kode_barang_id','=','barang.kode_barang')    
        ->where('master_pembelian_id','=',$value->id)    
        ->get();

        $supplier = DB::table('master_kontak')
        ->where('id','=',$value->kontak_id)
        ->first();


        $pembayaran = [
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
            'supplier'=>$supplier,
            'pembayaran'=>$pembayaran,
            'user'=> $user

        ];

        $output[] = $data;
        }

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
        
        public function store(Request $request){
    
            if($request->pembayaran['statusPembayaran']['value'] == 2){
                $sisa_pembayaran = $request->invoice['grandTotal'];
            }else if($request->pembayaran['statusPembayaran']['value'] == 1){
                $sisa_pembayaran = (float)$request->invoice['grandTotal'] - (float)$request->pembayaran['downPayment'];
            }else{
                $sisa_pembayaran = 0;
            }
    
            $data = TransaksiPembelian::create([
                'nomor_transaksi'=> $request->nomorTransaksi,
                'kontak_id' => $this->cekSupplier($request->supplier),
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
                'tanggal_jatuh_tempo' => $request->pembayaran['tanggalJatuhTempo'],
                'retur' => 2,
                'user_id' => 1,
            ]);
            $id = $data->id;
            if($id){
                foreach ($request->orders as $key => $value) {
                    $detail = DetailPembelian::create([
                        'master_pembelian_id'=> $id,
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

        public function debitPersediaan($data){
            $detail = KartuPersediaan::create([
                'master_pembelian_id'=> $id,
                'kode_barang_id' => $value['kode_barang'],
                'jumlah' => $value['jumlah'],
                'harga' => $value['harga'],
                'diskon' => $value['diskon'],
                'total' => ($value['jumlah'] * $value['harga']) - $value['diskon'],
            ]);
        }
}
