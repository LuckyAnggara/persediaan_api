<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Pegawai extends Model
{
    use HasFactory;
    use SoftDeletes;
 
    protected $table = 'master_pegawai';
    protected $guarded = [
        'id',
    ];
}

