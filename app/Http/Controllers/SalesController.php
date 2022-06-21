<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\TransaksiPenjualan;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function allPerformance(Request $payload){
        $year = $payload->input('tahun');
        $month = $payload->input('bulan');
        $day = $payload->input('hari');
        $cabang_id = $payload->input('cabang_id');
        
        
        if($year != null){
            $dateawal = date($year.'-01-01 00:00:00');
            $dateakhir = date($year.'-12-31 23:59:59');
        }
        if($month != null){
            $dateawal =  date('Y-'.$month.'-01 00:00:00');
            $dateakhir = date('Y-'.$month.'-31 23:59:59');
        }
        if($day != null){
            $dateawal = date('2021-m-d 00:00:00', strtotime($day));
            $dateakhir = date('Y-m-d 23:59:59', strtotime($day));
        }
        //5 Itu Id Jabatan Sales liat di Database
        $sales = Pegawai::where('jabatan_id', 5)->where('cabang_id', $cabang_id)->get();
        foreach ($sales as $key => $value) {
            $penjualan = TransaksiPenjualan::where('sales_id', $value->id)
            ->where('created_at','>=',$dateawal)    
            ->where('created_at','<=',$dateakhir)
            ->where('cabang_id','<=',$cabang_id)
            ->sum('grand_total');
            $value->total_penjualan = $penjualan;
        }

        return response()->json($sales, 200);
    }
}
