<?php

namespace App\BigQuery;

use Google\Cloud\BigQuery\BigQueryClient;

interface IClient
{
    public function select(string $table): IClient;
    public function where(string $where, string $condition = 'AND'): IClient;
    public function order(string $order): IClient;
    public function sql(): string;
    public function client(): BigQueryClient;
    public function dataset(): string;
    public function limit(int $limit): IClient;
    public function startFromIndex(int $startFromIndex): IClient;
    public function get(): array;
}
