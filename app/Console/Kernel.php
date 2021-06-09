<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;
use App\Models\Presensi;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $data = Presensi::where('tanggal',date("y-m-d"));

            $cek = $data->count();
            if($cek > 0){
            }else{
                DB::table('master_presensi')->insert([
                    'tanggal' => date("y-m-d"),
                    'pegawai_id'=> 55,
                    'catatan'=> $cek
                ]);
            }

            $output = $data->get();
            foreach ($output as $key => $value) {
                // CEK JAM MASUK
                if($value->jam_masuk !== null){
                    $value->catatan = date("y-m-d h:i:s", strtotime($value->jam_masuk));
                        $value->save();
                    if(date("y-m-d h:i:s", strtotime($value->jam_masuk)) >= date("y-m-d 07:00:00")){
                        $value->catatan = 'Telat';
                        $value->save();
                    }else{
                        $value->catatan =$value->jam_masuk >= time("07:00:00");
                        $value->save();
                    }
                }else{
                    $value->catatan = $value->jam_masuk;
                    $value->save();
                }
            }

        })->everyMinute();
        // // >timezone('Asia/Jakarta')->at('04:00');
        // $schedule->call(function () {

        //     $data = Presensi::where('tanggal',date("y-m-d"))->get();

        //     foreach ($data as $key => $value) {
        //         // CEK JAM MASUK
        //         if($value->jam_masuk !== null){
        //             if($value->jam_masuk >= date("y-m-d 07:00:00")){
        //                 $value->catatan = 'Telat';
        //                 $value->save();
        //             }
        //         }else{
        //             $value->catatan = date("y-m-d 07:00:00");
        //             $value->save();
        //         }
        //     }
        // })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
