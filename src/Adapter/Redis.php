<?php
namespace Lead\Queue\Adapter;

use InvalidArgumentException;
use Predis\Client;

/**
 *
 * Redis Broker
 *
 * `delay` is supported through push
 * `ttr` is not supported
 *
 */
class Redis extends \Lead\Queue\Broker
{
    /**
     * Redis adapter constructor
     *
     * @param string $name
     * @param Client $redis
     */
    public function __construct($config = [])
    {
        if (empty($config['name'])) {
            throw new InvalidArgumentException('Missing `name` config.');
        }
        if (empty($config['client'])) {
            throw new InvalidArgumentException('Missing `client` config.');
        }
        $this->_name = $config['name'];
        $this->_client = $config['client'];
    }

    /**
     * @inheritDoc
     */
    public function push($job, $options = [])
    {
        if (!empty($options['delay'])){
            $this->_client->zadd($this->_name . ':delayed', json_encode($job->payload()), time() + (int) ($options['delay'] ?? 0));
        } else {
            $this->_client->rpush($this->_name, json_encode($job->payload()));
        }
    }

    /**
     * @inheritDoc
     */
    public function fetch($max, $options = [])
    {
        $jobs = [];

        for ($i = 0; $i < $max; $i++) {
            $timeout = (int) ($options['timeout'] ?? 0);
            if (!$timeout) {
                $message = $this->_client->lpop($this->_name);
            } else {
                $result = $this->_client->blpop($this->_name, $timeout);
                $message = $result[1] ?? null;
            }

            if ($message) {
                $payload = json_decode($message, true);
                $jobs[] = $this->createJob($payload, $message, $payload['attempts'] ?? 0);
            }
        }
        return $jobs;
    }

    /**
     * @inheritDoc
     */
    public function delete($job)
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function release($job, $options = [])
    {
        $job->attempts($job->attempts + 1);
        $this->_client->rpush($this->_name, json_encode($job->payload()));
    }
}