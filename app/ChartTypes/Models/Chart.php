<?php

namespace App\ChartTypes\Models;

use Illuminate\Database\Eloquent\Model;

class ChartType extends Model
{
    protected $table = 'chart_types';

    protected $fillable = [
        'slug',
        'name',
        'type',
    ];
}
