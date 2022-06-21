<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\Role;
use App\Models\User;

class UserController extends Controller
{
    public function index(){
        $user = User::all();
        foreach ($user as $key => $value) {
            $value->pegawai = Pegawai::find($value->pegawai_id)->nama;
            $value->role = Role::find($value->role_id)->nama;
        }

        return response()->json($user, 200);
    }
}
