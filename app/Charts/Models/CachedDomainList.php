<?php

namespace App\Charts\Models;

use Illuminate\Database\Eloquent\Model;

class CachedDomainList extends Model
{
    protected $table = 'cashed_domain_list';

    protected $fillable = [
        'domain',
        'clicks',
    ];
}
