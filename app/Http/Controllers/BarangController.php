<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\JenisBarang;
use App\Models\MerekBarang;
use App\Models\SatuanBarang;
use App\Models\HargaJual;

class BarangController extends Controller
{
    public function aw(){
        $con = array();
        $doc = new \DOMDocument();

        $doc->loadHTML('https://www.wildriftfire.com/');
        return $doc;
        foreach($doc->getElementsByTagName('https') as $content) {
            $con[] = $content;
        }
        
       return $con;
    }

    public function index(Request $payload){

        $cabang_id = $payload->input('cabang_id');
        $gudang = Gudang::where('cabang_id', $cabang_id)->where('utama', '1')->first();

        $data = DB::table('barang')
        ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
        ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
        // ->join('gudang', 'barang.gudang_id', '=', 'gudang.id')
        ->select('barang.*','jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
        ->where('barang.deleted_at', '=',null)
        ->get();

        
        foreach ($data as $key => $value) {
          

            $saldo_masuk = DB::table('kartu_persediaan')
            ->where('gudang_id', '=',$gudang->id)
            ->where('cabang_id', '=',$cabang_id)
            ->where('master_barang_id', '=',$value->id)
            ->where('jenis', '=','DEBIT')
            ->sum('jumlah');

            $saldo_keluar = DB::table('kartu_persediaan')
            ->where('master_barang_id', '=',$value->id)
            ->where('gudang_id', '=',$gudang->id)
            ->where('cabang_id', '=',$cabang_id)
            ->where('jenis', '=','KREDIT')
            ->sum('jumlah');

            $value->persediaan['saldo'] = $saldo_masuk - $saldo_keluar;
            $value->persediaan['gudang'] = $gudang->nama;

            $output[] = $value;

            
        }
        return response()->json($output, 200);
    }
    
    public function store(Request $request){

        // BUAT KODE BARANG
        $data = Barang::all();
        $output = collect($data)->last();
        $str = $request->nama[0];
        $str = strtoupper($str);
        $kode_barang = $str.str_pad($output['id'] + 1,5,"0",STR_PAD_LEFT);

        // CEK KODE BARANG ADA ATAU TIDAK MENGHINDARI DUPLIKAT
        while(Barang::where('kode_barang', $kode_barang)->exists()) {
            $kode_barang = $str.str_pad($output['id'] + 1,5,"0",STR_PAD_LEFT);
        }

        // BUAT BASIS DATANYA
        $data = Barang::create([
            'kode_barang'=> $kode_barang,
            'nama' => $request->nama,
            'jenis_id' => $request->jenis_id,
            'merek_id' => $request->merek_id,
            'gudang_id' => $request->gudang_id,
            'rak' => $request->rak,
            'catatan' => $request->catatan,
        ]);
        
        // CEK MASUKAN HARGA JUAL ATAU TIDAK
        if($request->harga){
            //JIKA ADA MASUKIN HARGA JUAL KE DATABASE
            foreach ($request->harga as $key => $value) {
                if($value['satuan'] !== null){
                    HargaJual::create([
                        'kode_barang' => $kode_barang,
                        'satuan_id' => $value['satuan'],
                        'harga' => $value['harga'],
                        'catatan' => $value['catatan'],
                    ]);
                }

            }
        }
   
        $data = DB::table('barang')
        ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
        ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
        ->join('gudang', 'barang.gudang_id', '=', 'gudang.id')
        ->select('barang.*', 'gudang.nama as nama_gudang','jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
        ->where('barang.id', '=',$data->id)
        ->first();
        $data->harga = DB::table('harga_jual')
        ->join('satuan_barang', 'harga_jual.satuan_id', '=', 'satuan_barang.id')
        ->select('harga_jual.*', 'satuan_barang.nama as nama_satuan')
        ->where('kode_barang', '=',$data->kode_barang)
        ->get();
        return response()->json($data, 200);
    }

    public function gudangStore(Request $request){
        $data = Gudang::create([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
        ]);
        return response()->json($data, 200);
    }

    public function hargaStore(Request $request){
        $data = HargaJual::create([
            'kode_barang' => $request->kode_barang,
            'satuan_id' => $request->satuan,
            'harga' => $request->harga,
            'catatan' => $request->catatan,
        ]);
        return response()->json($data, 200);
    }

    public function jenisStore(Request $request){
        $data = JenisBarang::create([
            'nama' => $request->nama,
        ]);
        return response()->json($data, 200);
    }

    public function merekStore(Request $request){
        $data = MerekBarang::create([
            'nama' => $request->nama,
        ]);
        return response()->json($data, 200);
    }

    public function satuanStore(Request $request){
        $data = SatuanBarang::create([
            'nama' => $request->nama,
        ]);
        return response()->json($data, 200);
    }

    public function show2($id){
        try{
            // $barang = Barang::findOrFail($id);
            $barang = DB::table('barang')
            ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
            ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
            ->select('barang.*', 'jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
            ->where('barang.id', '=',$id)
            ->first();

            $harga = DB::table('harga_jual')
            ->join('satuan_barang', 'harga_jual.satuan_id', '=', 'satuan_barang.id')
            ->select('harga_jual.*', 'satuan_barang.nama as nama_satuan')
            ->where('kode_barang', '=',$barang->kode_barang)
            ->get();
            $code = 200;

            $response['barang'] = $barang;
            $response['harga'] = $harga;
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

    public function show(Request $payload){
        $id = $payload->input('id');
        try{
            // $barang = Barang::findOrFail($id);
            $barang = DB::table('barang')
            ->join('jenis_barang', 'barang.jenis_id', '=', 'jenis_barang.id')
            ->join('merek_barang', 'barang.merek_id', '=', 'merek_barang.id')
            ->select('barang.*', 'jenis_barang.nama as nama_jenis', 'merek_barang.nama as nama_merek')
            ->where('barang.id', '=',$id)
            ->first();

            $harga = DB::table('harga_jual')
            ->join('satuan_barang', 'harga_jual.satuan_id', '=', 'satuan_barang.id')
            ->select('harga_jual.*', 'satuan_barang.nama as nama_satuan')
            ->where('kode_barang', '=',$barang->kode_barang)
            ->get();
            $code = 200;

            $response['barang'] = $barang;
            $response['harga'] = $harga;
        }catch(Execption $e){
            if($e instanceof ModelNotFoundException){
                $code = 404;
                $response = 'ID tidak ditemukan';
            }else{
                $code = 500;
                $response = $e->getMessage();
            }
        }

        return response()->json($response, $code);
    }

    public function destroy($id)
    { 
        $barang = Barang::find($id);
        $barang->delete();
        return response()->json($barang);
    }

    public function hargaDestroy($id)
    { 
        $barang = HargaJual::find($id);
        $barang->delete();
        return response()->json($barang);
    }

    public function gudang(){
        $data = Gudang::where('id', '!=', 1)->get();
        return response()->json($data, 200);
    }

    public function satuan(){
        $data = SatuanBarang::all();
        return response()->json($data, 200);
    }

    public function jenis(){
        $data = JenisBarang::all();
        return response()->json($data, 200);
    }

    public function merek(){
        $data = MerekBarang::all();
        return response()->json($data, 200);
    }

    public function ubahHargaSemua(Request $payload){
        $return = [];
        foreach ($payload->data as $key => $value) {
            $master = Barang::find($value['id']);

            $master->harga_ritel = $value['harga_ritel'];
            $master->harga_konsumen = $value['harga_konsumen'];
            $master->harga_lainnya = $value['harga_lainnya'];

            $master->save();
            $return[] = $master;
        }
        return response()->json($return, 200);

    }
}
