<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_penjualan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('nomor_transaksi', 255);
            $table->string('kontak_id');
            $table->double('total');
            $table->double('diskon');
            $table->double('ongkir');
            $table->double('pajak_masukan');
            $table->double('grand_total');
            $table->string('syarat_pembayaran_id', 10);
            $table->enum('status_pembayaran',['Dibayar','COD','Belum Dibayar']);
            $table->enum('status_kredit',['Lunas','Kredit']);
            $table->dateTime('jatuh_tempo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_penjualan');
    }
}
