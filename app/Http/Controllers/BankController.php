<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Bank;

class BankController extends Controller
{
    public function index(){
        $output = Bank::all();
        $bank = [];
        foreach ($output as $key => $value) {
            $data = [
                'value'=> $value['id'],
                'title'=> $value['nama_bank']. ' - ' . $value['nomor_rekening'],
                'kode_akun_id'=> $value['kode_akun_id']
            ];
            $bank[] = $data;
        }
       
        return response()->json($bank, 200);
    }

    
}
