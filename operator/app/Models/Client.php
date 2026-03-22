<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clienti';
    protected $primaryKey = 'cod_cl';
    public $timestamps = false;
}
