<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class DetailPO extends Model
{

    use HasFactory;
    use SoftDeletes;
 
    protected $table = 'detail_po';
    protected $guarded = [
        'id',
    ];
    protected $dates = ['created_at','deleted_at'];
}
