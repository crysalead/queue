<?php
namespace Lead\Queue;

abstract class Broker
{
    /**
     * Queue name.
     *
     * @var string
     */
    protected $_name;

    /**
     * Client driver instance
     *
     * @var mixed
     */
    protected $_client;

    /**
     * Installed signal handlers.
     *
     * @var array<int, callable>
     */
    protected $_signalHandlers = [];

    /**
     * Flag signaling the broker should continue to run.
     *
     * @var boolean
     */
    protected $_shouldRun = true;

    /**
     * Create a job.
     *
     * @param array $payload The job payload
     * @param mixed $message The broker orinigal message
     * @return Job
     */
    public function createJob($payload, $message, $attempts = 0)
    {
        $class = $payload['class'] ?? Job::class;
        $payload += ['attempts' => $attempts];
        return new $class(['broker' => $this, 'payload' => $payload, 'message' => $message]);
    }

    /**
     * Put a job on the broker
     *
     * @param Job $job
     * @param array $options
     * @return void
     */
    abstract public function push($job, $options = []);

    /**
     * Get a single job off the broker.
     *
     * @param array $options
     * @return Job|null
     */
    public function pull($options = [])
    {
        $jobs = $this->fetch(1, $options);
        return key($jobs) !== null ? reset($jobs) : null;
    }

    /**
     * Get many job off the broker.
     *
     * @param integer $max
     * @param array $options
     * @return array<Job>
     */
    abstract public function fetch($max, $options = []);

    /**
     * Delete a job off the broker
     *
     * @param Job $job
     * @return void
     */
    abstract public function delete($job);

    /**
     * Release a job back on to the broker
     *
     * @param Job $job
     * @param array $options
     * @return void
     */
    abstract public function release($job, $options = []);

    /**
     * Add a signal handler.
     *
     * @param integer $signal
     * @param callable $handler
     * @return void
     */
    public function addHandler($signal, $handler)
    {
        $this->_signalHandlers[$signal] = $handler;
        pcntl_signal($signal, [$this, 'interrupt']);
    }

    /**
     * Interrupt with a given PCNTL signal.
     *
     * @param int $signal
     * @return void
     */
    public function interrupt($signal)
    {
        $callable = $this->_signalHandlers[$signal] ?? null;

        if( $callable ){
            call_user_func($callable, $signal);
        }
    }

    /**
     * Cease processing messages.
     *
     * @return void
     */
    public function shutdown()
    {
        $this->_shouldRun = false;
    }

    /**
     * Block listen for new messages on broker. Executes callback on new Message arrival.
     *
     * @param callable $callback
     * @param int $pollingTimeout
     * @return void
     */
    public function listen($callback, $pollingTimeout = 20)
    {
        do {
            if ($job = $this->pull(['timeout' => $pollingTimeout])) {
                $callback($job);
            }
            pcntl_signal_dispatch();
        } while($this->_shouldRun);
    }

    /**
     * Get the broker client
     *
     * @return mixed
     */
    public function client()
    {
        return $this->_client;
    }

    /**
     * Get the broker queue name.
     *
     * @return string
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Call a method on the Queue client itself.
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call($method, $params = [])
    {
        return call_user_func_array([$this->_client, $method], $params);
    }
}