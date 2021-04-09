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
            $table->char('nomor_transaksi', 255)->unique();
            $table->string('kontak_id');
            $table->double('total');
            $table->double('diskon');
            $table->double('ongkir');
            $table->double('pajak_masukan')->nullable();
            $table->double('grand_total');
            $table->enum('metode_pembayaran', ['Lunas','Kredit','COD']);
            $table->enum('kredit',['Iya','Tidak'])->nullable();
            $table->double('down_payment')->nullable();
            $table->double('sisa_pembayaran')->nullable();
            $table->enum('cara_pembayaran', ['Tunai','Transfer']);
            $table->string('bank_id')->nullable();
            $table->dateTime('tanggal_jatuh_tempo')->nullable();
            $table->enum('retur',['Iya','Tidak'])->nullable();
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
