<?php

namespace App\Sessions\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions';

    protected $fillable = [
        'date',
        'brand_clicks',
        'brand_impressions',
        'non_brand_clicks',
        'non_brand_impressions',
        'total_clicks',
        'total_impressions',
    ];

    /**
     * get sessions for current year
     *
     * @return mixed
     */
    public static function currentYear()
    {
        return self::where('date', '>=', now()->subYear())->get();
    }

    /**
     * get sessions for prev year
     *
     * @return mixed
     */
    public static function prevYear()
    {
        return self::where('date', '<=', now()->subYear())->get();
    }
}
