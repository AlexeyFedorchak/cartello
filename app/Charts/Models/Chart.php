<?php

namespace App\ChartTypes\Models;

use Illuminate\Database\Eloquent\Model;

class Chart extends Model
{
    protected $table = 'charts';

    protected $fillable = [
        'slug',
        'name',
        'type',
    ];
}
