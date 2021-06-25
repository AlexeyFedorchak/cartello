<?php

namespace App\BigQuery;

use Google\Cloud\BigQuery\BigQueryClient;

interface IClient
{
    public function select(string $table, array $columns = ['*']): IClient;
    public function where(string $where, string $condition = 'AND'): IClient;
    public function orderBy(string $order): IClient;
    public function groupBy(string $order): IClient;
    public function openGroupCondition(string $condition = 'AND'): IClient;
    public function closeGroupCondition(): IClient;
    public function raw(string $query): IClient;
    public function limit(int $limit, int $offset): IClient;
    public function sql(): string;
    public function client(): BigQueryClient;
    public function dataset(): string;
    public function get(): array;
}
