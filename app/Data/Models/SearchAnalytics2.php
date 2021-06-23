<?php

namespace App\Data\Models;

use App\Data\Traits\CloudAnalytics;
use Illuminate\Database\Eloquent\Model;

class SearchAnalytics2 extends Model
{
    use CloudAnalytics;

    protected $table = 'search_analytics_2';

    /**
     * big query table name
     *
     * @var string
     */
    protected $bigQueryTable = 'searchanalytics_2';

    /**
     * rows per one query
     *
     * @var int
     */
    protected $perQuery = 5000;

    protected $fillable = [
        'domain_id',
        'domain',
        'date',
        'query',
        'device',
        'country',
        'clicks',
        'impressions',
        'position',
    ];
}
