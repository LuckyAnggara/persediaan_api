<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Jabatan extends Model
{
    use HasFactory;
    use SoftDeletes;
 
    protected $table = 'master_jabatan';
    protected $guarded = [
        'id',
    ];
    protected $casts = [
        'id' => 'integer',
    ];
}
