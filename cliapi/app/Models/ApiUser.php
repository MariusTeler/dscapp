<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ApiUser extends Authenticatable
{
    protected $table = 'apiusers';
    protected $hidden = ['password_hash'];
}
