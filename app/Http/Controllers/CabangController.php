<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Cabang;


class CabangController extends Controller
{
    public function index(){

        try{
            // $barang = Barang::findOrFail($id);
            $data= Cabang::all();
            $code = 200;
            $response = $data;

        }catch(Execption $e){
            if($e instanceof ModelNotFoundException){
                $code = 404;
                $response = 'Data Tidak Ditemukan';
            }else{
                $code = 500;
                $response = $e->getMessage();
            }
        }

        return response()->json($response, $code);
    }
}
