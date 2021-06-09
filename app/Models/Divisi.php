<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Divisi extends Model
{
    use HasFactory;
    use SoftDeletes;
 
    protected $table = 'master_divisi';
    protected $guarded = [
        'id',
    ];
}