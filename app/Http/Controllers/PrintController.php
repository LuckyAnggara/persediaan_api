<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintBuffers\ImagePrintBuffer;
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;
use Mike42\Escpos\CapabilityProfiles\SimpleCapabilityProfile;
use Mike42\Escpos\PrintConnectors\RawbtPrintConnector;
use Mike42\Escpos\CapabilityProfile;

class PrintController extends Controller
{
    function rupiah($angka, $prefix = ''){
        $hasil_rupiah = $prefix . number_format($angka,0,',','.');
        return $hasil_rupiah;
    }

    
    public function invoice_penjualan(){

        $data = (app('App\Http\Controllers\TransaksiPenjualanController')->getTransaksi(1));
        // return $data['orders'];
        // return $data;
        $nama_cabang = 'PT. BBM MAKMUR LIMBANGAN';
        $alamat = 'JL. RAYA ASKDJAKSJDKA DKSAJDKAJSKDAJS asdasddsadasasdassssssssssdasdasdas';
        $nama_pelanggan = 'Lucky Anggara';
        $alamat1 = 'Jl Limbangan Timur Nomor 50 xxx';
        $jenis_pembayaran = 'Tunai';
        $status_pembayaran = 'Lunas';

        $connector = new WindowsPrintConnector("LX310");
        $printer = new Printer($connector);
        $printer->initialize();
        $printer->selectPrintMode(Printer::MODE_FONT_B);
        // $printer->feed(14);
        $printer->text(STR_PAD($nama_cabang, 60));
        $printer->text(STR_PAD('INVOICE#'.$data['nomorTransaksi'],0) . "\n");        
        if(strlen($alamat) > 50 ){
            $sisa = 50 - strlen($alamat);
            $complete = strlen($alamat) - 50;
            $printer->text(STR_PAD(substr($alamat, 0, $sisa), 60));
            $printer->text(STR_PAD('Tanggal : '.$data['tanggalTransaksi'],0) . "\n");
            $printer->text(STR_PAD(substr($alamat, 51, $complete), 60). "\n");
        }
        $printer->text(STR_PAD('Nomor Telepon : 0xxxxxxxx',0));
        $printer->feed(1);
        // ---------------------------------------------------------------------------
        for ($x = 1; $x <= 95; $x++) {
            $printer -> text("-");
          }
        $printer -> text("\n");
        // -----------------------------------------------------------------------------
        $printer->text(STR_PAD('Invoice Ke : ', 60));
        $printer->text(STR_PAD('Detail Pembayaran',0));
        $printer->text("\n");
        $printer->text(STR_PAD($data['pelanggan']->nama, 60));
        $printer->text(STR_PAD('Jenis', 15));
        $printer->text(STR_PAD(':', 1));
        $printer->text(STR_PAD($data['pembayaran']['jenisPembayaran']['title'], 0));
        $printer->text("\n");
        $printer->text(STR_PAD($data['pelanggan']->alamat, 60));
        $printer->text(STR_PAD('Status', 15));
        $printer->text(STR_PAD(':', 1));
        $printer->text(STR_PAD($data['pembayaran']['statusPembayaran']['title'], 0));
        $printer->feed(2);

        //------------------------------------------------------------------------------
        for ($x = 1; $x <= 95; $x++) {
          $printer -> text("-");
        }
        $printer->text("\n");

        $printer->text(STR_PAD('No', 5));
        $printer->text(STR_PAD('Nama Barang', 30));
        $printer->text(STR_PAD('Harga', 15));
        $printer->text(STR_PAD('Jumlah', 10));
        $printer->text(STR_PAD('Diskon', 15));
        $printer->text(STR_PAD('Total', 20));
        $printer->text("\n");
        
        for ($x = 1; $x <= 95; $x++) {
            $printer -> text("-");
        }
        $printer->text("\n");

        $no = 1;
        foreach ($data['orders'] as $key => $order) {
            for ($x = 1; $x <= 6; $x++) {
                $printer->text(STR_PAD($no++, 5));
                $printer->text(STR_PAD($order->kode_barang_id.'-'.$order->nama_barang, 30));
                $printer->text(STR_PAD($this->rupiah($order->harga, "Rp. "), 15));
                $printer->text(STR_PAD($this->rupiah($order->jumlah, ""), 10));
                $printer->text(STR_PAD($this->rupiah($order->diskon, "Rp. "),15));
                $printer->text(STR_PAD($this->rupiah($order->total, "Rp. "),20));
                $printer->text("\n");
            }
        }
        for ($x = 1; $x <= 95; $x++) {
            $printer -> text("-");
        }
        
        $printer->feed(2);
        $printer->text(STR_PAD('Kasir', 60));
        $printer->text(STR_PAD('Sub Total',15));
        $printer->text(STR_PAD(':', 1));
        $printer->text(STR_PAD($this->rupiah($data['invoice']['total'],"Rp."),0));
        $printer->text("\n");
        $printer->text(STR_PAD('', 60));
        $printer->text(STR_PAD('Diskon',15));
        $printer->text(STR_PAD(':', 1));
        $printer->text(STR_PAD($this->rupiah($data['invoice']['diskon'],"Rp."),0));
        $printer->text("\n");
        $printer->text(STR_PAD('', 60));
        $printer->text(STR_PAD('Pajak',15));
        $printer->text(STR_PAD(':', 1));
        $printer->text(STR_PAD($this->rupiah($data['invoice']['pajak'],"Rp."),0));
        $printer->text("\n");
        $printer->text(STR_PAD('', 60));
        for ($x = 1; $x <= 15; $x++) {
            $printer -> text("-");
        }
        $printer->text("\n");
        $printer->text(STR_PAD($data['user']['nama'], 60));
        $printer->text(STR_PAD('Grand Total',15));
        $printer->text(STR_PAD(':', 1));
        $printer->text(STR_PAD($this->rupiah($data['invoice']['grandTotal'],"Rp."),0));
        $printer->feed(2);
        

        for ($x = 1; $x <= 95; $x++) {
            $printer -> text("-");
        }
        $printer -> text("\n");
        $printer->text(STR_PAD('Catatan',15));
        $printer->text(STR_PAD(':', 1));
        $printer->text(STR_PAD($data['catatan'], 0));

        $printer->feedForm();
        $printer -> close();
        
    }
}
