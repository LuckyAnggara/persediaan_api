<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Models\Barang;
use App\Models\KartuPersediaan;
use App\Models\TransferPersediaan;
use App\Models\DetailTransferPersediaan;
use App\Models\Opname;
use App\Models\DetailOpname;
use App\Models\HargaBeli;
use App\Models\Gudang;

class PersediaanController extends Controller
{

    public function index($cabang_id, $gudang_id){

        $data = DB::table('barang')
        ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
        ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
        ->join('gudang', 'barang.gudang_id', '=', 'gudang.id')
        ->select('barang.*', 'gudang.nama as nama_gudang','jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
        ->where('barang.deleted_at', '=',null)
        ->get();

        
        foreach ($data as $key => $value) {

            $xx = DB::table('kartu_persediaan')
            ->where('gudang_id', '=',$gudang_id)
            ->where('cabang_id', '=',$cabang_id)
            ->where('master_barang_id', '=',$value->id)
            ->where('deleted_at')
            ->pluck('gudang_id')->first();

            $saldo_masuk = DB::table('kartu_persediaan')
            ->where('gudang_id', '=',$gudang_id)
            ->where('cabang_id', '=',$cabang_id)
            ->where('master_barang_id', '=',$value->id)
            ->where('deleted_at')
            ->where('jenis', '=','DEBIT')
            ->sum('jumlah');

            $saldo_keluar = DB::table('kartu_persediaan')
            ->where('master_barang_id', '=',$value->id)
            ->where('gudang_id', '=',$gudang_id)
            ->where('cabang_id', '=',$cabang_id)
            ->where('deleted_at')
            ->where('jenis', '=','KREDIT')
            ->sum('jumlah');


            // ->sum('saldo_rupiah');
            $harga_beli = null;
            $saldo = $saldo_masuk - $saldo_keluar;
            if($saldo == 0){
               $barang =  Barang::find($value->id);
               $saldo_rupiah = $saldo * $barang->harga_beli;
            }else if($saldo < 0){
                $saldo_rupiah = $saldo * 0;
            }else{
                $saldo_rupiah = DB::table('harga_beli')
                ->select(DB::raw('sum(saldo * harga_beli) as saldo_rupiah'))
                ->where('gudang_id', '=',$gudang_id)
                ->where('cabang_id', '=',$cabang_id)
                ->where('master_barang_id', '=',$value->id)
                ->where('deleted_at')
                ->where('saldo', '!=','0')->first();
                $saldo_rupiah = $saldo_rupiah->saldo_rupiah;

                $harga_beli = DB::table('harga_beli')
                ->select('harga_beli.*',DB::raw('saldo * harga_beli as total'))
                ->where('master_barang_id', '=',$value->id)
                ->where('cabang_id', '=',$cabang_id)
                ->where('gudang_id', '=',$gudang_id)
                ->where('saldo', '!=','0')
                ->orderBy('created_at','ASC')
                ->get();
            }

            $value->persediaan['saldo'] = $saldo_masuk - $saldo_keluar;
            $value->persediaan['saldo_masuk'] = $saldo_masuk;
            $value->persediaan['saldo_keluar'] = $saldo_keluar;
            $value->persediaan['saldo_rp'] = $saldo_rupiah;
            $value->persediaan['harga_beli'] = $harga_beli;
            $value->persediaan['gudang_id'] = $xx;

            $output[] = $value;
        }
        return response()->json($output, 200);
    }

    public function daftarPenyesuaian($cabang){

        $data = Opname::where('cabang_id', $cabang)->get();
        $output = [];
        foreach ($data as $key => $value) {
            $detailData = DetailOpname::where('master_opname_id', $value->id)->get();
            foreach ($detailData as $key => $data) {
                $barang = Barang::where('id',$data->master_barang_id)->first();
                $data->nama = $barang->nama;
                $data->kode_barang = $barang->kode_barang;
                $detail[] = $data;
            }
            $value->detail = $detail;
            $output[] = $value;
        }

        return response()->json($output, 200);
    }

    public function daftarTransfer($cabang){

        $data = TransferPersediaan::where('cabang_id', $cabang)->get();
        $output = [];
        foreach ($data as $key => $value) {
            $detailData = DetailTransferPersediaan::where('master_transfer_persediaan_id', $value->id)->get();
            $dari = Gudang::find($value->dari);
            $value->dari = $dari;
            $ke = Gudang::find($value->ke);
            $value->ke = $ke;
            foreach ($detailData as $key => $data) {
                $barang = Barang::where('id',$data->master_barang_id)->first();
                $data->nama = $barang->nama;
                $data->kode_barang = $barang->kode_barang;
                $detail[] = $data;
            }
            $value->detail = $detail;
            $output[] = $value;
        }

        return response()->json($output, 200);
    }

    public function show($id, $gudang_id){
      
        $persediaan =[];
        $saldo = 0;
        try{
            $data = DB::table('kartu_persediaan')
            ->select('*')
            ->where('gudang_id', '=',$gudang_id)
            ->where('deleted_at')
            ->where('master_barang_id', '=',$id)
            ->orderBy('id', 'asc')
            ->get();
            foreach ($data as $key => $value) {
                if($value->jenis == 'DEBIT'){
                    $saldo += $value->jumlah; 
                } else if($value->jenis == 'KREDIT'){
                    $saldo -= $value->jumlah; 
                }
                $value->saldo =$saldo;
                $persediaan[$key] = $value;

            }
            
            $response = $persediaan;

            $code = 200;
        }catch(Execption $e){
            if($e instanceof ModelNotFoundException){
                $code = 404;
                $response = 'ID tidak ditemukan';
            }else{
                $code = 500;
                $response = $e->getMessage();
            }
        }

        return response()->json($response, 200);
    }

    public function storeOpname(Request $payload){
            $saldo = 0;
            $persediaan = [];

            // MEMBUAT NOMOR OPNAME
            $data = Opname::groupBy('nomor_opname')->get(); // CEK DATA NOMOR OPNAME DENGAN GROUPING
            $prefix = date("ymd"); // PREFIX AWALAN PAKE TANGGAL TAHUN-BULAN-TANGGAL (EX 210422)
            $output = $data->count(); // DATA DIHITUNG 
            $output++; // DATA DITAMBAH 1
            $nomor_opname ='OPNAME#'.$prefix.$output;
            // END MEMBUAT NOMOR OPNAME
            $master = Opname::create([
                'nomor_opname'=> $nomor_opname,
                'catatan' => $payload->catatan,
                'tipe' => $payload->tipe['value'],
                'kategori' => $payload->kategori['value'],
                'gudang_id' => $payload->gudang['id'],
                'user_id' => $payload->user['id'],
                'cabang_id'=>$payload->user['cabang_id'],
                'created_at' =>$payload->tanggalTransaksi,
                'updated_at' =>$payload->tanggalTransaksi,
            ]);

            if($master->id){
                $nominal = 0;
                foreach ($payload->data as $key => $value) {
                    $jenis = 'KREDIT';
                    if($value['perbedaan'] == 0){
                        continue;
                    }
                    if($value['perbedaan'] > 0){
                        $jenis = 'DEBIT';
                        
                        // NAMBAH HARGA BELI KARENA NAMBAH BARU
                        $hargaBeli = HargaBeli::create([
                            'master_barang_id' => $value['id'],
                            'saldo' => abs($value['perbedaan']),
                            'harga_beli' => round($value['harga'], 0),
                            'jenis' => $nomor_opname,
                            'gudang_id' => $payload->gudang['id'],
                            'user_id' =>  $payload->user['id'],
                            'cabang_id'=> $payload->user['cabang_id'],
                        ]);
                        $nominal += $value['perbedaan'] * $value['harga'];
                    }

                    if($value['perbedaan'] < 0){
                        $jenis = 'KREDIT';
                        $qty = abs($value['perbedaan']);

                        $hargabeli = HargaBeli::where('master_barang_id', $value['id'])
                        ->where('saldo', '!=', 0)
                        ->where('cabang_id','=', $payload->user['cabang_id'])
                        ->where('gudang_id','=', $payload->gudang['id'])
                        ->orderBy('created_at', 'asc')
                        ->get();

                        $count = $hargabeli->count();

                        if($count > 0){

                            foreach ($hargabeli as $key => $val) {

                            $xx = HargaBeli::find($val['id']);
                            $saldo = $xx->saldo;
                            $sisa = $xx->saldo - $qty;

                            if($sisa < 0){
                                $qty = $qty - $xx->saldo;
                                $nominal += $xx->saldo * $xx->harga_beli;
                                $xx->saldo = 0;
                                $xx->save();
                            }else{
                                $nominal += $qty * $xx->harga_beli;
                                $xx->saldo = $saldo - $qty;
                                $xx->save();
                                $qty = 0;
                            }
                            if($qty == 0){
                                break;
                            }
                            }

                        }
                    }
 
                    $data = KartuPersediaan::create([
                        'nomor_transaksi'=> $nomor_opname,
                        'master_barang_id' => $value['id'],
                        'jenis' => $jenis,
                        'jumlah' => abs($value['perbedaan']),
                        'harga' => round($value['harga'], 0),
                        'catatan' => 'PENYESUAIAN #TGL'.$value['tanggalTransaksi'],
                        'gudang_id' => $payload->gudang['id'],
                        'user_id' => $payload->user['id'],
                        'cabang_id'=>$payload->user['cabang_id'],
                        'created_at' =>$value['tanggalTransaksi'],
                        'updated_at' =>$value['tanggalTransaksi'],
                    ]);

                    $detail = DetailOpname::create([
                        'master_opname_id'=> $master->id,
                        'master_barang_id'=> $value['id'],
                        'jumlah_tercatat'=> $value['jumlah_tercatat'],
                        'jumlah_fisik'=> $value['jumlah_fisik'],
                        'perbedaan'=> $value['perbedaan'],
                        'harga'=> $value['harga'],
                        'user_id' => $payload->user['id'],
                        'cabang_id'=>$payload->user['cabang_id'],
                        'created_at' =>$value['tanggalTransaksi'],
                        'updated_at' =>$value['tanggalTransaksi'],
                    ]);    
                    $persediaan[] = $data;
                }
    
                $jurnal = $this->postJurnalPersediaan($payload, $nominal, $jenis, $nomor_opname);
                return $jurnal;
                $persediaan['jurnal'] = $jurnal;
                $master->nomor_jurnal = $jurnal['nomor_jurnal'];
                $master->save();

                return response()->json($persediaan, 200);
            }

            return response()->json('ERROR', 201);

           
    }

    function postJurnalPersediaan($payload, $saldo, $jenis, $catatan){

        $jenisPersediaan = 'DEBIT';
        $jenisHpp = 'KREDIT';
        
        if($jenis == 'KREDIT'){
            $jenisPersediaan = 'KREDIT';
            $jenisHpp = 'DEBIT';
        }
        
        $persediaan = array(
            'akunId'=>$payload->gudang['kode_akun_id'], // PERSEDIAAN DAGANG
            'namaJenis'=>$jenisPersediaan,
            'saldo'=> abs($saldo),
            'catatan'=> $catatan,
        );
        $jurnal['persediaan'] = $persediaan;

        $hpp = array(
            'akunId'=>'44', // HPP
            'namaJenis'=>$jenisHpp,
            'saldo'=> abs($saldo),
            'catatan'=> $catatan,
        );
        $jurnal['hpp'] = $hpp;

        $post = [
            // 'nomor_jurnal' => '',
            'catatan' => $catatan,
            'tanggalTransaksi'=>  $payload->tanggalTransaksi,
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
            'jurnal'=> $jurnal
        ];

        $output = Http::post('http://127.0.0.1:8080/api/jurnal/store/', $post);

        return $output->json();

    }

    function cekSaldo($id){
        $data = DB::table('kartu_persediaan')
        ->select(
            DB::raw('SUM(debit) as saldo_masuk, SUM(kredit) as saldo_keluar'),
            )
        ->where('master_barang_id','=',$id)    
        ->where('saldo', '!=', 0)
        ->where('master_jurnal.deleted_at')
        ->first();
        $saldo = $data->saldo_masuk - $data->saldo_keluar;
        return $saldo;
    }

    // TRANSFER GUDANG

    function storeTransfer(Request $payload){
        $output = array();
        // MEMBUAT NOMOR OPNAME
        $data = TransferPersediaan::groupBy('nomor_transfer')->get(); // CEK DATA NOMOR OPNAME DENGAN GROUPING
        $prefix = date("ymd"); // PREFIX AWALAN PAKE TANGGAL TAHUN-BULAN-TANGGAL (EX 210422)
        $dataCount = $data->count(); // DATA DIHITUNG 
        $dataCount++; // DATA DITAMBAH 1
        $nomor_transfer = $prefix.$dataCount;
        // END MEMBUAT NOMOR OPNAME

        $master = TransferPersediaan::create([
            'nomor_transfer'=> 'TRANFSER_PERSEDIAAN'.$nomor_transfer,
            'catatan' => $payload->data['catatan'],
            'dari' => $payload->data['dataGudang']['dari']['id'],
            'ke' => $payload->data['dataGudang']['ke']['id'],
            'user_id' => $payload->user['id'],
            'cabang_id'=>$payload->user['cabang_id'],
            'created_at' =>$payload->data['tanggalTransaksi'],
        ]);

        $output['master'] = $master;

        if($master->id){
            foreach ($payload->persediaan as $key => $value) {

                $qty = $value['transfer'];
                $nominal = 0;

                $detail = DetailTransferPersediaan::create([
                    'master_transfer_persediaan_id'=> $master->id,
                    'master_barang_id'=> $value['id_barang'],
                    'jumlah'=> $value['transfer'],
                    'user_id' => $payload->user['id'],
                    'cabang_id'=>$payload->user['cabang_id'],
                    'created_at' =>$payload->data['tanggalTransaksi'],
                    'updated_at' =>$payload->data['tanggalTransaksi'],
                ]);
    
                $output['detail'][] = $detail;

                $barang = Barang::find($value['id_barang']);
                $harga_modal = $barang->harga_beli;
                
                $hargabeli = HargaBeli::where('master_barang_id', $value['id_barang'])
                ->where('saldo', '!=', 0)
                ->where('cabang_id','=', $payload->user['cabang_id'])
                ->where('gudang_id','=', $payload->data['dataGudang']['dari']['id'])
                ->orderBy('created_at', 'asc')
                ->get();

                $count = $hargabeli->count();

                if($count > 0){
                        foreach ($hargabeli as $key => $val) {
                            
                            $xx = HargaBeli::find($val['id']);
                            $saldo = $xx->saldo;
                            $sisa = $xx->saldo - $qty;
                            if($sisa < 0){
                                $hargaBeli = HargaBeli::create([
                                    'master_barang_id' => $value['id_barang'],
                                    'saldo' => $xx->saldo,
                                    'harga_beli' => round($xx->harga_beli, 0),
                                    'jenis' => 'TRANSFER'.$payload->data['catatan'],
                                    'user_id' =>  $payload->user['id'],
                                    'cabang_id'=> $payload->user['cabang_id'],
                                    'gudang_id'=> $payload->data['dataGudang']['ke']['id'],
                                ]);
                                $qty = $qty - $xx->saldo;
                                $nominal += $xx->saldo * $xx->harga_beli;
                                $xx->saldo = 0;
                                $xx->save();
                            }else{
                                $nominal += $qty * $xx->harga_beli;
                                $xx->saldo = $saldo - $qty;
                                $xx->save();
                                $hargaBeli = HargaBeli::create([
                                    'master_barang_id' => $value['id_barang'],
                                    'saldo' => $qty,
                                    'harga_beli' => round($xx->harga_beli, 0),
                                    'jenis' => 'TRANSFER'.$payload->data['catatan'],
                                    'user_id' =>  $payload->user['id'],
                                    'cabang_id'=> $payload->user['cabang_id'],
                                    'gudang_id'=> $payload->data['dataGudang']['ke']['id'],
                                ]);
                                $qty = 0;
                            }
                            if($qty == 0){
                                break;
                            }
                        }
                        //DI KREDIT // MOTONG
                        $kredit = KartuPersediaan::create([
                            'nomor_transaksi'=> 'TRANSFER_PERSEDIAAN'.$nomor_transfer,
                            'master_barang_id' => $value['id_barang'],
                            'jenis' => 'KREDIT',
                            'jumlah' => abs($value['transfer']),
                            'harga' => $hargaBeli->harga_beli,
                            'catatan' => 'TRANSFER BARANG #TGL'.$payload->data['tanggalTransaksi'],
                            'user_id' => $payload->user['id'],
                            'gudang_id' => $payload->data['dataGudang']['dari']['id'],
                            'cabang_id'=>$payload->user['cabang_id'],
                        ]);
                                // DI DEBIT // NAMBAH
                        $debit = KartuPersediaan::create([
                            'nomor_transaksi'=> 'TRANSFER_PERSEDIAAN'.$nomor_transfer,
                            'master_barang_id' => $value['id_barang'],
                            'jenis' => 'DEBIT',
                            'jumlah' => abs($value['transfer']),
                            'harga' => $hargaBeli->harga_beli,
                            'catatan' => 'TRANSFER BARANG #TGL'.$payload->data['tanggalTransaksi'],
                            'user_id' => $payload->user['id'],
                            'gudang_id' => $payload->data['dataGudang']['ke']['id'],
                            'cabang_id'=>$payload->user['cabang_id'],
                        ]);
                }
    
                $output['persediaan']['kredit'][] = $kredit;
                $output['persediaan']['debit'][] = $debit;

                $output['harga_beli'][] = $hargabeli;

            }

            $jurnal = [];
            $catatan = 'TRANSFER PERSEDIAAN #'.$nomor_transfer;

            $master->nominal = $nominal;
            $master->save();
    
            $kredit = array(
                'akunId'=> $payload->data['dataGudang']['dari']['kode_akun_id'],
                'namaJenis'=> 'KREDIT',
                'saldo'=>$nominal,
                'catatan'=>$catatan,
             );
            $jurnal['kredit'] = $kredit;
            
            $debit = array(
                'akunId'=> $payload->data['dataGudang']['ke']['kode_akun_id'],
                'namaJenis'=> 'DEBIT',
                'saldo'=>$nominal,
                'catatan'=>$catatan,
             );

            $jurnal['debit'] = $debit;
    
            $post = [
                'catatan' => $catatan,
                'tanggalTransaksi'=>  date("Y-m-d h:i:s"),
                'user_id' => $payload->user['id'],
                'cabang_id'=>$payload->user['cabang_id'],
                'jurnal'=> $jurnal
            ];

            $jurnal = Http::post(keuanganBaseUrl().'jurnal/store/', $post)->json();
            $output =$jurnal;
            $master->nomor_jurnal = $jurnal['nomor_jurnal'];
            $master->save();

            return response()->json($output, 200);
        }



        return response()->json('ERROR', 201);
    }

    function destroyTransfer($id){
        $output = [];
        $master = TransferPersediaan::findOrFail($id);
        $master->delete();
        // DETAIL PENJUALAN
        $detail = DetailTransferPersediaan::where('master_transfer_persediaan_id', $master->id)->get();
        foreach ($detail as $key => $value) {
           $dd = DetailTransferPersediaan::findOrFail($value->id);
           $dd->delete();
        }
        // PERSEDIAAN
        $persediaan = KartuPersediaan::where('nomor_transaksi', $master->nomor_transfer)->get();
        foreach ($persediaan as $key => $value) {
            $dd = KartuPersediaan::findOrFail($value->id);
            $dd->delete();

            $xx = HargaBeli::create([
                'master_barang_id' => $value->master_barang_id,
                'saldo' => $value->jumlah,
                'harga_beli' => $value->harga,
                'jenis' => 'RETUR_'.$master->nomor_transfer,
                'user_id' => $value->user_id,
                'cabang_id'=>$value->cabang_id,
                'gudang_id'=> $master->dari,
                'created_at' =>date("Y-m-d h:i:s"),
                'updated_at' =>$value->updated_at,
            ]);
            $hargabeli[] = $xx;
        }
        // PEMBAYARAN
        $jurnal = Http::delete(keuanganBaseUrl().'jurnal/delete/'.$master->nomor_jurnal);
     
        // JURNAL
        $output['master'] = $master;
        $output['detail'] = $detail;
        $output['persediaan'] = $persediaan;
        $output['hargabeli'] = $hargabeli;
        $output['jurnal'] = $jurnal;

        return response()->json($output, 200);

        // BAGIAN JURNAL LANGSUNG API DI FRONTEND VUE NYA
        // NGGA VIA LARAVEL YG INI
    }
    
}
