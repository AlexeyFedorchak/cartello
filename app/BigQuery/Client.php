<?php

namespace App\BigQuery;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Core\ExponentialBackoff;

class Client implements IClient
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
    public function select(string $table): IClient
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
    public function where(string $where, string $condition = 'AND'): IClient
    {
        if (strpos($this->query, 'where') !== false) {
            $this->query .= ' ' . $condition . ' ' . $where;
        } else {
            $this->query .= ' ' . $where;
        }

        return $this;
    }

    /**
     * set sort order in query -> make it simple from our side and move work to big query :)
     *
     * @param string $order
     * @return $this
     */
    public function order(string $order): IClient
    {
        $this->query .= ' ' . $order;

        return $this;
    }

    /**
     * limit | has to be passed in the end
     * @TODO rewrite it with using chunks
     *
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = 0): IClient
    {
        $this->query .= ' limit ' . $limit . ' offset ' . $offset;

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
            foreach ($row as $column => $value)
                $data[$i][$column] = $value;

            $i++;
        }

        return array_values($data);
    }
}
