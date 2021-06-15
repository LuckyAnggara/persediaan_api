<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Models\Barang;
use App\Models\KartuPersediaan;
use App\Models\Opname;
use App\Models\DetailOpname;

class PersediaanController extends Controller
{

    public function index(){

        $data = DB::table('barang')
        ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
        ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
        ->join('gudang', 'barang.gudang_id', '=', 'gudang.id')
        ->select('barang.*', 'gudang.nama as nama_gudang','jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
        ->where('barang.deleted_at', '=',null)
        ->get();

        
        foreach ($data as $key => $value) {

            $saldo_masuk = DB::table('kartu_persediaan')
            ->where('master_barang_id', '=',$value->id)
            ->where('deleted_at')
            ->where('jenis', '=','DEBIT')
            ->sum('jumlah');

            $saldo_keluar = DB::table('kartu_persediaan')
            ->where('master_barang_id', '=',$value->id)
            ->where('deleted_at')
            ->where('jenis', '=','KREDIT')
            ->sum('jumlah');

            $value->persediaan['saldo'] = $saldo_masuk - $saldo_keluar;
            $value->persediaan['saldo_masuk'] = $saldo_masuk;
            $value->persediaan['saldo_keluar'] = $saldo_keluar;
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

    public function show($id){
      
        $persediaan =[];
        $saldo = 0;
        try{
            $data = DB::table('kartu_persediaan')
            ->select('*')
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


    public function store(Request $payload){
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
                'user_id' => $payload->user['id'],
                'cabang_id'=>$payload->user['cabang']['id'],
                'created_at' =>$payload->tanggalTransaksi,
                'updated_at' =>$payload->tanggalTransaksi,
            ]);

            if($master->id){
                foreach ($payload->data as $key => $value) {

                    $total = $value['perbedaan'] * $value['harga'];
                    $saldo += $total;
                    $jenis = 'KREDIT';
    
                    if($value['perbedaan'] == 0){
                        continue;
                    }
                    if($value['perbedaan'] > 0){
                        $jenis = 'DEBIT';
                    }
                    $catatan = 'OPNAME#'.$value['tanggalTransaksi'];
                    $data = KartuPersediaan::create([
                        'nomor_transaksi'=> $catatan,
                        'master_barang_id' => $value['id'],
                        'jenis' => $jenis,
                        'jumlah' => abs($value['perbedaan']),
                        'harga' => round($value['harga'], 0),
                        'catatan' => 'PENYESUAIAN #TGL'.$value['tanggalTransaksi'],
                        'user_id' => $payload->user['id'],
                        'cabang_id'=>$payload->user['cabang']['id'],
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
                        'cabang_id'=>$payload->user['cabang']['id'],
                        'created_at' =>$value['tanggalTransaksi'],
                        'updated_at' =>$value['tanggalTransaksi'],
                    ]);
    
                    $persediaan[] = $data;
                }
    
                $jurnal = $this->postJurnalPersediaan($payload, $saldo, $catatan);
                $persediaan['jurnal'] = $jurnal;
                return response()->json($persediaan, 200);
            }

            return response()->json('ERROR', 201);

           
    }

    function postJurnalPersediaan($payload, $saldo, $catatan){
        $jenisPersediaan = 'DEBIT';
        $jenisHpp = 'KREDIT';
        if($saldo < 0){
            $jenisPersediaan = 'KREDIT';
            $jenisHpp = 'DEBIT';
        }
        
        $persediaan = array(
            'akunId'=>'6', // PIUTANG DAGANG
            'namaJenis'=>$jenisPersediaan,
            'saldo'=> abs($saldo),
            'catatan'=> $catatan,
        );
        $jurnal['persediaan'] = $persediaan;

        $hpp = array(
            'akunId'=>'44', // PIUTANG DAGANG
            'namaJenis'=>$jenisHpp,
            'saldo'=> abs($saldo),
            'catatan'=> $catatan,
        );
        $jurnal['hpp'] = $hpp;

        $post = [
            'nomor_jurnal' => '',
            'catatan' => $catatan,
            'tanggalTransaksi'=>  date("Y-m-d h:i:s"),
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
}
