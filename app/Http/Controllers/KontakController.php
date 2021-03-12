<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Kontak;

class KontakController extends Controller
{
    public function index(){
        $data = DB::table('master_kontak')
        ->select('*')
        ->where('deleted_at', '=',null)
        ->get();
        return response()->json($data, 200);
    }
}
