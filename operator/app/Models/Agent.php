<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $table = 'agenti';
    protected $primaryKey = 'cod_ag';
    public $timestamps = false;
}
