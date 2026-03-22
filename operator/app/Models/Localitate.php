<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Localitate extends Model
{
    protected $table = 'localitati';
    protected $primaryKey = 'cod_lc';
    public $timestamps = false;
}
