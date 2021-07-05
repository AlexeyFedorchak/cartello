<?php

namespace App\Charts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CachedResponses extends Model
{
    protected $table = 'cached_responses';

    protected $fillable = [
        'chart_id',
        'response',
    ];

    /**
     * get related chart
     *
     * @return HasOne
     */
    public function chart(): HasOne
    {
        return $this->hasOne(Chart::class, 'id', 'chart_id');
    }
}
