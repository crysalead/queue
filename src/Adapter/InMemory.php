<?php
namespace Lead\Queue\Adapter;

use InvalidArgumentException;

/**
 *
 * InMemory Broker
 *
 * `delay` is not supported
 * `ttr` is not supported
 *
 */
class InMemory extends \Lead\Queue\Broker
{
    /**
     * @inheritDoc
     */
    protected $_name;

    /**
     * Source messages.
     *
     * @var array<mixed>
     */
    protected $_jobs;

    /**
     * InMemory constructor
     *
     * @param string $name
     * @param array<mixed> $messages
     */
    public function __construct($config = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function push($job, $options = [])
    {
        $this->_jobs[$job->id()] = $job;
    }

    /**
     * @inheritDoc
     */
    public function fetch($max, $options = [])
    {
        return array_slice($this->_jobs, 0, $max);
    }

    /**
     * @inheritDoc
     */
    public function delete($job)
    {
        unset($this->_jobs[$job->id()]);
    }

    /**
     * @inheritDoc
     */
    public function release($job, $options = [])
    {
        $job->attempts($job->attempts + 1);
        $this->_jobs[$job->id()] = $job;
    }
}