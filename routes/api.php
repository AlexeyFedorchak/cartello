<?php

use App\BigQuery\IClient;
use App\Charts\Constants\ChartTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/charts', [\App\Http\Controllers\GetChartsAPIController::class, 'get'])->name('get.charts');
Route::get('/data', [\App\Http\Controllers\GetChartDataAPIController::class, 'get'])->name('get.chart.data');
Route::get('/domains', [\App\Http\Controllers\GetDomainsAPIController::class, 'get'])->name('get.domains');
Route::get('/filter-options', [\App\Http\Controllers\GetChartFilterOptionsAPIController::class, 'get'])->name('get.filter-options');

Route::get('/opportunity-chart/filter', [\App\Http\Controllers\ConsoleAPI\FilterOpportunityChartAPIController::class, 'filter'])
    ->name('opportunity.filter');


