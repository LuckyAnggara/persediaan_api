<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;

class PegawaiController extends Controller
{
    public function index(){
        $data = Pegawai::all();
        return response()->json($data, 200);
    }
}
