<?php
namespace Lead\Queue\Adapter;

use InvalidArgumentException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Contract\PheanstalkInterface;

/**
 *
 * Beanstalk Broker
 *
 * `delay` is supported through push
 * `ttr` is supported through push
 *
 */
class Beanstalk extends \Lead\Queue\Broker
{
    /**
     * Beanstalkd adapter constructor.
     *
     * @param string $name
     * @param Pheanstalk $pheanstalk
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
        $this->_client
            ->useTube($this->_name)
            ->put(
                json_encode($job->payload()),
                $options['priority'] ?? PheanstalkInterface::DEFAULT_PRIORITY,
                $options['delay'] ?? PheanstalkInterface::DEFAULT_DELAY,
                $options['ttr'] ??  PheanstalkInterface::DEFAULT_TTR // After this delay if the job hasn't been deleted/released it'll be redelivered
            );
    }

    /**
     * @inheritDoc
     */
    public function fetch($max, $options = [])
    {
        $jobs = [];

        for ($i = 0; $i < $max; $i++) {
            $message = $this->_client->useTube($this->_name)->reserveWithTimeout($options['timeout'] ?? 0);

            if ($message) {
                $jobs[] = $this->createJob(json_decode($message->getData(), true), $message, (int) $this->_client->statsJob($message)->reserves);
            }
        }
        return $jobs;
    }

    /**
     * @inheritDoc
     */
    public function delete($job)
    {
        $message = $job->message();
        $this->_client->delete($message);
    }

    /**
     * @inheritDoc
     */
    public function release($job, $options = [])
    {
        $message = $job->message();
        $this->_client->release(
            $message,
            $options['priority'] ?? PheanstalkInterface::DEFAULT_PRIORITY,
            $options['delay'] ?? PheanstalkInterface::DEFAULT_DELAY
        );
    }

}