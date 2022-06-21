<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

    use HasFactory;
    protected $table = 'role';
    protected $guarded = [
        'id',
    ];

    protected $dates = ['created_at'];

    protected $casts = [
        'ability' => 'array',
    ];

}
