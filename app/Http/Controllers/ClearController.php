<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClearController extends Controller
{
    public function clear(){
        DB::table('detail_gaji')->truncate();
        DB::table('detail_opname')->truncate();
        DB::table('detail_pembayaran')->truncate();
        DB::table('detail_pembelian')->truncate();
        DB::table('detail_penjualan')->truncate();
        DB::table('detail_piutang')->truncate();
        DB::table('detail_po')->truncate();
        DB::table('detail_transfer_persediaan')->truncate();
        DB::table('harga_beli')->truncate();
        DB::table('kartu_persediaan')->truncate();
        DB::table('master_gaji')->truncate();
        // DB::table('master_kontak')->truncate();
        DB::table('master_pembelian')->truncate();
        DB::table('master_penjualan')->truncate();
        DB::table('master_po')->truncate();
        DB::table('master_presensi')->truncate();
        DB::table('master_setor')->truncate();
        DB::table('master_transfer_persediaan')->truncate();
    }
}