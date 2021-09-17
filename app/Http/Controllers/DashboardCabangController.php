<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\TransaksiPenjualan;
use Carbon\Carbon;

class DashboardCabangController extends Controller
{
    public function omsetHarian(Request $payload){
        $cabang_id = $payload->input('cabang_id');

        return $cabang_id;
        $total = 0;
        $master = TransaksiPenjualan::where('cabang_id',$cabang_id)->whereDate('created_at', Carbon::today())->get()->pluck('grand_total');
        $output['chart']['name'] = 'Total Penjualan';
        $output['chart']['data'] = $master;
        foreach ($master as $key => $value) {
            $total += $value;
        }
        $output['total'] = $total;

        return response()->json($output, 200);
    }
}
