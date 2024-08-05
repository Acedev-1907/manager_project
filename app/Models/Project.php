<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Project extends Model
{
    use HasFactory;

    const NOT_STARTED=0;

    const PEDDING=1;

    const COMPLETED=1;

    protected $guarded=[];

    public static function createSlug($name){
        $code = Str::random(10).time();
        $slug =  Str::slug($name).'-'.$code;
        return $slug;
    }
}
