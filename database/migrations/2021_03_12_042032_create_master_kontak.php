<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterKontak extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_kontak', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('nama', 255);
            $table->enum('tipe', ['PELANGGAN','SUPPLIER','KARYAWAN']);
            $table->double('telepon');
            $table->char('identitas', 255);
            $table->char('email', 255);
            $table->char('info_lain', 255);
            $table->char('nama_perusahaan', 255);
            $table->char('npwp', 255);
            $table->char('alamat', 255);
            $table->string('akun_piutang_id');
            $table->string('akun_utang_id');
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
        Schema::dropIfExists('master_kontak');
    }
}
