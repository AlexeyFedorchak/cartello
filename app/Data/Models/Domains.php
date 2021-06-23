<?php

namespace App\Data\Models;

use App\BigQuery\IClient;
use Illuminate\Database\Eloquent\Model;

class Domains extends Model
{
    protected $table = 'domains';

    protected $fillable = [
        'clientname',
        'domain',
        'firstdate',
        'lastdate',
    ];

    public function syncWithCloud()
    {
        $domains = app(IClient::class)
            ->select('domains')
            ->limit(100)
            ->get();

        foreach ($domains as $domain)
            self::updateOrCreate(['domain' => $domain['domain']], $domain);
    }
}
