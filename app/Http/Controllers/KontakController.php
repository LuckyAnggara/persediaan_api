<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Kontak;

class KontakController extends Controller
{
    public function index(){

        // if($cabang_id == 0)
        // {
        //     $data = Kontak::where('wic', '=',0)->get();
        // }else{
            $data = DB::table('master_kontak')
            ->select('*')
            ->where('wic', '=',0)
            // ->where('cabang_id', '=',$cabang_id)
            ->where('deleted_at', '=',null)
            ->get();
        // }

        return response()->json($data, 200);



    }

    public function store(Request $request){
        $data = Kontak::create([
            'tipe'=> $request->tipe,
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'telepon' => $request->telepon,
            'wic' => 0,
        ]);
        return response()->json($data, 200);
    }
}
