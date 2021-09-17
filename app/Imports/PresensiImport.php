<?php

namespace App\Imports;

use App\Models\Presensi;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PresensiImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    private $tanggal;
    private $user_id;
    private $cabang_id;
    private $output = [];

    public function __construct($tanggal, $user_id, $cabang_id)
    {
        $this->tanggal = $tanggal;
        $this->user_id = $user_id;
        $this->cabang_id = $cabang_id;
    }

    public function model(array $row)
    {

        $exist = Presensi::where('pegawai_id', $row['nip'])->where('tanggal', date("y-m-d", strtotime($this->tanggal)))->first();
        if($exist){
            $exist->jam_masuk =  $row['scan_1'] == '' ? null  : gmdate("H:i:s", ($row['scan_1'] - 25569) * 86400);
            $exist->jam_keluar =   $row['scan_2'] == '' ?  null  : gmdate("H:i:s", ($row['scan_2'] - 25569) * 86400);
            $exist->catatan =  'masuk';
            $exist->user_id = $this->user_id;
            $exist->cabang_id = $this->cabang_id;
            $exist->save();
            $this->output[] = $exist;
        }else{
            $data = new Presensi([
                'pegawai_id'   => $row['nip'],
                'tanggal'    => $this->tanggal,
                'jam_masuk' => $row['scan_1'] == '' ? null  : gmdate("H:i:s", ($row['scan_1'] - 25569) * 86400),
                'jam_keluar' =>$row['scan_2'] == '' ?  null  : gmdate("H:i:s", ($row['scan_2'] - 25569) * 86400),
                'catatan' => 'masuk', 
                'user_id' => $this->user_id,
                'cabang_id' => $this->cabang_id
            ]);
            $this->output[] = $data;
        }

        return $this->output;


    }

    public function getOutput()
    {
        return $this->output;
    }

    public function headingRow(): int
    {
        return 1;
    }
}
