<?php

namespace App\Providers;

use App\BigQuery\Client;
use App\BigQuery\IClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(
            IClient::class,
            Client::class
        );
    }
}
