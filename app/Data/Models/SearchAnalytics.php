<?php

namespace App\Data\Models;

use App\Data\Traits\CloudAnalytics;
use Illuminate\Database\Eloquent\Model;

class SearchAnalytics extends Model
{
    use CloudAnalytics;

    protected $table = 'search_analytics';

    /**
     * big query table name
     *
     * @var string
     */
    protected $bigQueryTable = 'searchanalytics';

    /**
     * rows per one select query
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
