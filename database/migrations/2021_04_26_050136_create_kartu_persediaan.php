<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKartuPersediaan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kartu_persediaan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi');
            $table->string('master_barang_id');
            $table->double('debit');
            $table->double('harga_beli');
            $table->double('kredit');
            $table->double('harga_jual');
            $table->double('saldo');
            $table->text('catatan');
            $table->char('fullName');
            $table->char('fullName');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kartu_persediaan');
    }
}
