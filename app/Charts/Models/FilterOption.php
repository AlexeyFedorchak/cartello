<?php

namespace App\Charts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FilterOption extends Model
{
    protected $table = 'filter_options';

    protected $fillable = [
        'chart_id',
        'options',
        'domain',
    ];

    /**
     * @return HasOne
     */
    public function chart(): HasOne
    {
        return $this->hasOne(Chart::class, 'id', 'chart_id');
    }
}
