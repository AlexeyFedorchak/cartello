<?php

namespace App\BigQuery;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Core\ExponentialBackoff;

class Client
{
    /**
     * @var string
     */
    protected $dataset;

    /**
     * @var BigQueryClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var int
     */
    protected $startFromIndex = 0;

    /**
     * set up dataset and client
     *
     * Client constructor.
     * @param string $dataset
     */
    public function __construct(string $dataset = 'cartello_bi')
    {
        $this->client = new BigQueryClient([
            'projectId' => 'normandy-api',
        ]);

        $this->dataset = $dataset;
    }

    /**
     * select table
     *
     * @param string $table
     * @return $this
     */
    public function select(string $table): self
    {
        $this->query = 'SELECT * FROM `' . $this->dataset . '.' . $table .'`';

        return $this;
    }

    /**
     * where condition
     *
     * @param string $where
     * @param string $condition
     * @return $this
     */
    public function where(string $where, string $condition = 'AND'): self
    {
        if (strpos($this->query, 'where') !== false) {
            $this->query .= ' ' . $condition . ' ' . $where;
        } else {
            $this->query .= ' ' . $where;
        }

        return $this;
    }

    /**
     * limit | has to be passed in the end
     * @TODO rewrite it with using chunks
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->query .= ' limit ' . $limit;

        return $this;
    }

    /**
     * set start index
     *
     * @param int $startFromIndex
     * @return $this
     */
    public function startFromIndex(int $startFromIndex): self
    {
        $this->startFromIndex = $startFromIndex;

        return $this;
    }

    /**
     * get query
     *
     * @return string
     */
    public function sql(): string
    {
        return $this->query;
    }

    /**
     * get client
     *
     * @return BigQueryClient
     */
    public function client(): BigQueryClient
    {
        return $this->client;
    }

    /**
     * get dataset
     *
     * @return string
     */
    public function dataset(): string
    {
        return $this->dataset;
    }

    /**
     * get data
     *
     * @return array
     * @throws \Exception
     */
    public function get(): array
    {
        $jobConfig = $this->client->query($this->query);
        $job = $this->client->startQuery($jobConfig);

        $backoff = new ExponentialBackoff(10);
        $backoff->execute(function () use ($job) {

            $job->reload();

            if (!$job->isComplete())
                throw new \Exception('Job has not yet completed', 500);
        });

        $queryResults = $job->queryResults();

        $i = 0;
        $data = [];
        foreach ($queryResults as $row) {
            if ($i < $this->startFromIndex) {
                $i++;
                continue;
            }

            foreach ($row as $column => $value)
                $data[$i][$column] = $value;

            $i++;
        }

        return array_values($data);
    }
}
