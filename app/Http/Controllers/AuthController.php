<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Pegawai;

class AuthController extends Controller
{
    public function login(Request $request){

            $user = User::where('username', '=', $request->username)->first();
            if(!$user){
                $code = 201;
                $response = 'User Tidak Ditemukan';
                return response()->json($response, $code);
            }
            if($user->password == $request->password){
                if($user->cabang_id == $request->cabang['id']){
                    $code = 200;

                    $pegawai = Pegawai::where('id','=', $user->pegawai_id)->first();
                    $user->fullName = $pegawai->full_name;
                    $user->avatar = $pegawai->avatar;
                    

                    $response = $user;
                }else{
                    $code = 203;
                    $response = 'Cabang tidak sesuai';
                }
          
            }else{
                $code = 202; // PASSWORD SALAH
                $response = 'Password Tidak Sesuai';
            }
           
        return response()->json($response, $code);
    }

    public function logout(){
        return keuanganBaseUrl();
    }
}
