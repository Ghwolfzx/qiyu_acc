<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verified extends Model
{
    protected $table = 't_verified';

    public $timestamps = false;

    protected $primaryKey = 'uid';
}
