<?php

namespace App\Data\Models;

use Illuminate\Database\Eloquent\Model;

class SearchAnalytics extends Model
{
    protected $table = 'search_analytics';

    protected $fillable = [
        'clientname',
        'domain',
        'firstdate',
        'lastdate',
    ];
}
