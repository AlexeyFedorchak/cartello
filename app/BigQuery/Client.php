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
    public function select(string $table, array $columns = ['*']): IClient
    {
        $this->query = 'SELECT ' . implode(',', $columns) . ' FROM `' . $this->dataset . '.' . $table .'`';

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
        $where = str_replace('where', '', $where);

        if (substr($this->query, -1) === '(')
            $condition = '';

        if (strpos($this->query, 'where') !== false) {
            $this->query .= ' ' . $condition . ' ' . $where;
        } else {
            $this->query .= ' where ' . $where;
        }

        return $this;
    }

    /**
     * having condition
     *
     * @param string $where
     * @param string $condition
     * @return $this
     */
    public function having(string $where, string $condition = 'AND'): IClient
    {
        $where = str_replace('having', '', $where);

        if (substr($this->query, -1) === '(')
            $condition = '';

        if (strpos($this->query, 'having') !== false) {
            $this->query .= ' ' . $condition . ' ' . $where;
        } else {
            $this->query .= ' having ' . $where;
        }

        return $this;
    }

    /**
     * set sort order in query -> make it simple from our side and move work to big query :)
     *
     * @param string $order
     * @return $this
     */
    public function orderBy(string $order): IClient
    {
        $order = str_replace(['order', 'by'], '', $order);

        if (strpos($this->query, 'order') !== false) {
            $this->query .= ', ' . $order;
        } else {
            $this->query .= ' order by ' . $order;
        }

        return $this;
    }

    /**
     * group data
     *
     * @param string $group
     * @return IClient
     */
    public function groupBy(string $group): IClient
    {
        $group = str_replace(['group', 'by'], '', $group);

        if (strpos($this->query, 'group') !== false) {
            $this->query .= ', ' . $group;
        } else {
            $this->query .= ' group by ' . $group;
        }

        return $this;
    }

    /**
     * group some part of conditions
     *
     * @param string $condition
     * @return IClient
     */
    public function openGroupCondition(string $condition = 'AND'): IClient
    {
        $this->query .= ' ' . $condition . ' (';

        return $this;
    }

    /**
     * close grouped condition
     *
     * @return IClient
     */
    public function closeGroupCondition(): IClient
    {
        $this->query .= ')';

        return $this;
    }

    /**
     * set raw query
     *
     * @param string $query
     * @return IClient
     */
    public function raw(string $query): IClient
    {
        $this->query = $query;
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
