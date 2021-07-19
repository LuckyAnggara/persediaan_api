<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Models\Gudang;


class GudangController extends Controller
{
    function index($cabang_id){

        // if($cabang_id === 1){
        //     $data = Gudang::all();
        // }else{
            $data = Gudang::where('cabang_id', $cabang_id)->get();
        // }

        return response()->json($data, 200);
    }
}
