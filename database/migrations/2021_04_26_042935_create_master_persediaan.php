<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterPersediaan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_persediaan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('nomor_transaksi', 255)->unique();
            $table->enum('jenis_transaksi', ['Penjualan','Pembelian']);
            $table->enum('status', ['Approve','Reject','Retur']);
            $table->text('catatan');
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
        Schema::dropIfExists('master_persediaan');
    }
}
