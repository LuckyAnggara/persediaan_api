<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\PO;
use App\Models\DetailPO;
use App\Models\Cabang;
use App\Models\DetailPembelian;
use App\Models\DetailPenjualan;
use App\Models\Gudang;
use App\Models\HargaBeli;
use App\Models\KartuPersediaan;
use App\Models\Kontak;
use App\Models\Pegawai;
use App\Models\TransaksiPenjualan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class POController extends Controller
{
    public function keluar(Request $payload)
    {
        $id = $payload->input('cabang_id');
        try{
            $output = [];
            $master = PO::where('cabang_id', $id)->get();

            foreach ($master as $key => $value) {
                $detail = DetailPO::where('master_po_id', $value->id)->get();
                foreach ($detail as $key => $v) {
                    $barang =  Barang::find($v->barang_id)->first();
                    $v->nama_barang =$barang->nama;
                    $v->kode_barang = $barang->kode_barang;
                    $v->id_barang = $v->barang_id;
                    $v->harga = 0;
                    $v->diskon = 0;
                    $v->total = 0;

                }
                $value->cabang_tujuan = Cabang::where('id', $value->tujuan_cabang_id)->first();
                $value->cabang_tujuan->kontak = Kontak::where('id', $value->cabang_tujuan->kontak_id)->first();
                $value->cabang_asal = Cabang::where('id', $value->cabang_id)->first();
                $value->user = User::find($value->user_id);
                $value->user->nama_lengkap = Pegawai::find($value->user->pegawai_id)->first()->nama;
                $value->user->cabang = Cabang::where('id',$value->user->cabang_id)->first();
                $value->detail = $detail;
                
                $value->jumlah_barang = $detail->count();
                $output [] = $value;
            }
            $code = 200;
            $response = $output;

        }catch(Execption $e){
            if($e instanceof ModelNotFoundException){
                $code = 404;
                $response = 'tidak ditemukan';
            }else{
                $code = 500;
                $response = $e->getMessage();
            }
        }

        return response()->json($response, $code);
    }

    public function masuk(Request $payload)
    {
        $id = $payload->input('cabang_id');
        try{
            $output = [];
            $master = PO::where('tujuan_cabang_id', $id)->where('status_po','!=','DIBATALKAN')->get();

            foreach ($master as $key => $value) {
                $detail = DetailPO::where('master_po_id', $value->id)->get();
                foreach ($detail as $key => $v) {
                    $barang =  Barang::find($v->barang_id)->first();
                    $v->nama_barang =$barang->nama;
                    $v->kode_barang = $barang->kode_barang;
                    $v->id_barang = $v->barang_id;
                    $v->harga = 0;
                    $v->diskon = 0;
                    $v->total = 0;

                }
                $value->cabang_tujuan = Cabang::where('id', $value->tujuan_cabang_id)->first();
                $value->cabang_asal = Cabang::where('id', $value->cabang_id)->first();
                $value->cabang_asal->kontak = Kontak::where('id', $value->cabang_asal->kontak_id)->first();
                $value->user = User::find($value->user_id);
                $value->user->nama_lengkap = Pegawai::find($value->user->pegawai_id)->first()->nama;
                $value->user->cabang = Cabang::where('id',$value->user->cabang_id)->first();
                $value->detail = $detail;
                
                $value->jumlah_barang = $detail->count();
                $output [] = $value;
            }
            $code = 200;
            $response = $output;

        }catch(Execption $e){
            if($e instanceof ModelNotFoundException){
                $code = 404;
                $response = 'tidak ditemukan';
            }else{
                $code = 500;
                $response = $e->getMessage();
            }
        }

        return response()->json($response, $code);
    }

    public function show(Request $payload){
        $id = $payload->input('id');
        try{
            $master = PO::find($id);
            $detail = DetailPO::where('master_po_id', $master->id)->get();
            foreach ($detail as $key => $v) {
                $barang =  Barang::find($v->barang_id)->first();
                $v->nama_barang =$barang->nama;
                $v->kode_barang = $barang->kode_barang;
                $v->id_barang = $v->barang_id;
                $v->modal = $barang->harga_beli;
                $v->jenis = $barang->jenis;
                $v->harga = 0;
                $v->diskon = 0;
                $v->total = 0;
            }
            $master->cabang_tujuan = Cabang::where('id', $master->tujuan_cabang_id)->first();
            $master->cabang_asal = Cabang::where('id', $master->cabang_id)->first();
            $master->cabang_asal->kontak = Kontak::where('id', $master->cabang_asal->kontak_id)->first();
            $master->user = User::find($master->user_id);
            $master->user->nama_lengkap = Pegawai::find($master->user->pegawai_id)->first()->nama;
            $master->user->cabang = Cabang::where('id',$master->user->cabang_id)->first();
            $master->detail = $detail;

            $code = 200;
            $response = $master;

        }catch(Execption $e){
            if($e instanceof ModelNotFoundException){
                $code = 404;
                $response = 'tidak ditemukan';
            }else{
                $code = 500;
                $response = $e->getMessage();
            }
        }

        return response()->json($response, $code);
    }


    public function store(Request $payload){
        $code = 404;
        $response = [];
        $kodePO = $this->makeNomorPO();
        $master = PO::create([
            'kode_po' => $kodePO,
            'tujuan_cabang_id' => $payload->cabang_tujuan['id'],
            'status_po' => 'TERKIRIM',
            'status_po_masuk' => 'BELUM DIBACA',
            'cabang_id'=>$payload->user['cabang_id'],
            'catatan'=>$payload->catatan,
            'user_id' => $payload->user['id'],
            'created_at'=> $payload->tanggal,
        ]);

        if($master->id){
            foreach ($payload->orders as $key => $value) {
                $detail = DetailPO::create([
                    'master_po_id'=> $master->id,
                    'barang_id' => $value['id_barang'],
                    'jumlah' => $value['jumlah'],
                ]);
                $response['detail'][] =  $detail;
            }
            $response['master'] = $master;
            $code = 200;

            $id = $master->id;
            try{
                $master = PO::find($id);
                $detail = DetailPO::where('master_po_id', $master->id)->get();
                foreach ($detail as $key => $v) {
                    $barang =  Barang::find($v->barang_id)->first();
                    $v->nama_barang =$barang->nama;
                    $v->kode_barang = $barang->kode_barang;
                    $v->harga = 0;
                    $v->total = 0;
                }
                $master->cabang_tujuan = Cabang::where('id', $master->tujuan_cabang_id)->first();
                $master->user = User::find($master->user_id);
                $master->user->nama_lengkap = Pegawai::find($master->user->pegawai_id)->first()->nama;
                $master->user->cabang = Cabang::where('id',$master->user->cabang_id)->first();
                $master->detail = $detail;
    
                $code = 200;
                $response = $master;
    
            }catch(Execption $e){
                if($e instanceof ModelNotFoundException){
                    $code = 404;
                    $response = 'tidak ditemukan';
                }else{
                    $code = 500;
                    $response = $e->getMessage();
                }
            }
    
            return response()->json($response, $code);
        }
        return response()->json($response, $code);


    }

    public function batal(Request $payload){
        $id = $payload->input('id');

        $master = PO::findOrFail($id);
        $master->status_po = 'DIBATALKAN';
        $master->save();

        if($master){
            return response()->json($master, 200);
        }
        return response()->json($master, 404);

    }

    public function selesai(Request $payload){
        $gudang = Gudang::where('cabang_id', $payload->user['cabang_id'])->where('utama', '1')->first();
        $detail = DetailPenjualan::where('master_penjualan_id', $payload->id)->get();

        foreach ($detail as $key => $value) {
            KartuPersediaan::create([
                'nomor_transaksi'=> $payload->data_po['kode_po'],
                'master_barang_id' => $value->barang_id,
                'jenis' => 'DEBIT',
                'jumlah' => $value->jumlah,
                'harga' => $value->harga,
                'catatan' => 'PURCHASE_ORDER#'.$payload->data_po['kode_po'],
                'gudang_id'=> $gudang->id,
                'user_id' => $payload->user['id'],
                'cabang_id'=>$payload->user['cabang_id'],
            ]);

            HargaBeli::create([
                'master_barang_id' =>$value->barang_id,
                'saldo' => $value->jumlah,
                'harga_beli' =>$value->harga,
                'jenis' => 'PURCHASE_ORDER_'.$payload->data_po['kode_po'],
                'user_id' =>  $payload->user['id'],
                'cabang_id'=> $payload->user['cabang_id'],
                'gudang_id'=> $gudang->id
            ]);
        }

        $utang = array(
            'akunId'=> $payload->data_po['cabang_tujuan']['kontak']['akun_utang_id'], // UTANG DAGANG
            'namaJenis'=>'KREDIT',
            'saldo'=>$payload->data_invoice['invoice']['grandTotal'],
            'catatan'=>'UTANG '. $payload->data_po['kode_po'],
        );
        $jurnal['utang'] = $utang;

        $persediaan = array(
            'akunId'=>$gudang->kode_akun_id, // PERSEDIAAN
            'namaJenis'=>'debit',
            'saldo'=>$payload->data_invoice['invoice']['grandTotal'],
            'catatan'=> 'UTANG '. $payload->data_po['kode_po'],
        );
        $jurnal['persediaan'] = $persediaan;

        $post = [
            'catatan' => 'UTANG '. $payload->data_po['kode_po'],
            'tanggalTransaksi'=>  date("Y-m-d h:i:s"),
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
            'jurnal'=> $jurnal
        ];
        
        $output = Http::post(keuanganBaseUrl().'jurnal/store/', $post);

        $jurnal =  $output->json();

        $master = PO::find($payload->data_po['id']);
        $master->nomor_jurnal = $jurnal;
        $master->save();

        return response()->json($master, 200);
        

    }





    public function updateStatus(Request $payload){
        $id = $payload->id;
        $status = $payload->status;
        $nomorTransaksi = $payload->nomorTransaksi;

        $master = PO::findOrFail($id);
        $master->status_po_masuk = $status;
        $master->status_po = $status;
        if($nomorTransaksi){
            $master->nomor_transaksi = $nomorTransaksi;
        }
        $master->save();


        if($master){
            return response()->json($master, 200);
        }
        return response()->json($master, 404);

    }

    public function updateStatusMasuk(Request $payload){
        $master = PO::find($payload->id);

        $master->status_po_masuk = $payload->status;
        $master->save();
        if($master){
            return response()->json($master, 200);
        }
        return response()->json($master, 404);

    }
    
    public function destroy($id){
        $master = PO::findOrFail($id);
        $master->delete();
        if($master){
            return response()->json($master, 200);
        }
        return response()->json($master, 404);

    }

    public function showInvoice(Request $payload){
        $nomor_transaksi = $payload->input('nomor_transaksi');

        $master = TransaksiPenjualan::where('nomor_transaksi', $nomor_transaksi)->first();

        if($master){
            return response()->json($master, 200);
        }
        return response()->json($master, 404);
    }


    public function makeNomorPO(){
        $data = PO::all();
        $output = collect($data)->last();
        $date = date("dmy");
        // return $output->kode_po;
        if($output){
            $dd = $output->kode_po;
            $str = explode('-', $dd);
            // return $str[1] + 1;
            if($str[0] = 'PO#'.$date){
                $last_prefix = $str[1]+ 1;
                return 'PO#'.$date.'-'.$last_prefix;
            }
            return 'PO#'.$date.'-'.'1';
        }
        return 'PO#'.$date.'-'.'1';      
    }
}
