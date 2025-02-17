<?php
namespace Lead\Queue;

class Job
{
    /**
     * The name of the broker the job belongs to.
     *
     * @var string
     */
    protected $_broker;

    /**
     * The broker message.
     *
     * @var mixed
     */
    protected $_message;

    /**
     * Indicates the number of times the job must be ran before aborted.
     *
     * @var mixed
     */
    protected $_maxTries = null;

    /**
     * The timeout value.
     *
     * @var mixed
     */
    protected $_timeout = null;

    /**
     * Indicates the Time to Live
     *
     * @var mixed
     */
    protected $_ttl = null;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected $_released = false;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $_deleted = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected $_failed = false;

    /**
     * The payload.
     *
     * @var array
     */
    protected $_payload = [];

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function id()
    {
        return $this->_payload['uuid'];
    }

    /**
     * Job constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->_broker = $config['broker'] ?? null;
        $this->_payload = ($config['payload'] ?? []) + [
            'uuid' => (string) Job::uuid(),
            'class' => get_class($this),
            'maxTries' => (int) $this->_maxTries,
            'timeout' => $this->_timeout,
            'expiresAt' => $this->_ttl !== null ? time() + $this->_ttl : null,
            'attempts' => 0,
            'data' => []
        ];
        $this->_message = $config['message'] ?? null;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function run()
    {

    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->_deleted = true;
        $this->_broker->delete($this);
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->_deleted;
    }

    /**
     * Release the message back onto the broker.
     *
     * @param array<string, mixed> $options
     * @return void
     */
    public function release($options = [])
    {
        $this->_released = true;
        $this->_broker->release($this, $options);
    }

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->_released;
    }

    /**
     * Determine if the job has been marked as a failure.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->_failed;
    }

    /**
     * Mark the job as "failed".
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->_failed = true;
    }

    /**
     * Get the number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries()
    {
        return $this->_payload['maxTries'];
    }

    /**
     * Get the number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout($timeout = null)
    {
        if (func_get_args() === 1) {
            $this->_payload['timeout'] = $timeout;
            return $this;
        }
        return $this->_payload['timeout'];
    }

    /**
     * Get the number of seconds the job can run.
     *
     * @return int|null
     */
    public function ttl($ttl = null)
    {
        if (func_get_args() === 1) {
            $this->_payload['expiresAt'] = time() + $ttl;
            return $this;
        }
        $expiresAt = $this->_payload['expiresAt'] - time();
        return $expiresAt > 0 ? $expiresAt : 0;
    }

    /**
     * Check if a task has expired.
     *
     * @return int|null
     */
    public function expired()
    {
        return $this->_payload['expiresAt'] >= time();
    }

    /**
     * Get the number of times the job has been fetched.
     *
     * @return int|null
     */
    public function attempts($attempts = null)
    {
        if (func_get_args() === 1) {
            $this->_payload['attempts'] = $attempts;
            return $this;
        }
        return $this->_payload['attempts'];
    }

    /**
     * Get the name of the broker the job belongs to.
     *
     * @return string
     */
    public function broker()
    {
        return $this->_broker;
    }

    /**
     * Return the payload data.
     *
     * @return mixed
     */
    public function payload()
    {
        return $this->_payload;
    }

    /**
     * Return the job data.
     *
     * @return mixed
     */
    public function data($data = [])
    {
        if (func_num_args() === 1) {
            $this->_payload['data'] = (array) $data;
        }
        return $this->_payload['data'];
    }

    /**
     * Return the broker original message.
     *
     * @return mixed
     */
    public function message()
    {
        return $this->_message;
    }

    /**
     * Generates a random uuid.
     *
     * @return integer A generated UUID.
     */
    static function uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}
