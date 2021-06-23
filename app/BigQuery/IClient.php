<?php

namespace App\BigQuery;

use Google\Cloud\BigQuery\BigQueryClient;

interface IClient
{
    public function select(string $table): IClient;
    public function where(string $where, string $condition = 'AND'): IClient;
    public function order(string $order): IClient;
    public function limit(int $limit, int $offset): IClient;
    public function sql(): string;
    public function client(): BigQueryClient;
    public function dataset(): string;
    public function get(): array;
}
