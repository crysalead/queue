<?php
namespace Lead\Queue\Adapter;

use InvalidArgumentException;
use Aws\Result;

/**
 *
 * AmazonSqs Broker
 *
 * `delay` is supported through push
 * `ttr` is supported through pull
 *
 */
class AmazonSqs extends \Lead\Queue\Broker
{
    /**
     * SQS adapter constructor
     *
     * @param string $name
     * @param SqsClient $sqsClient
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
        $messageOptions = [
            'QueueUrl' => $this->_name,
            'MessageBody' => json_encode($job->payload()),
            'DelaySeconds' => (int) ($options['delay'] ?? 0)
        ];

        if (!empty($options['messageId'])) {
            $messageOptions['MessageDeduplicationId'] = $options['messageId'];
        }

        if (!empty($options['groupId'])) {
            $messageOptions['MessageGroupId'] = $options['groupId'];
        }
        $this->_client->sendMessage($messageOptions);
    }

    /**
     * @inheritDoc
     */
    public function fetch($max, $options = [])
    {
        $messageOptions = [
            'QueueUrl' => $this->_name,
            'MaxNumberOfMessages' => (int) $max,
            'WaitTimeSeconds' => (int) ($options['timeout'] ?? 0),
            'AttributeNames' => ['ApproximateReceiveCount']
        ];

        if (!empty($options['ttr'])) {
            $messageOptions['VisibilityTimeout'] = (int) ($options['ttr'] ?? 0);
        }

        $response = $this->_client->receiveMessage($messageOptions);

        return array_map(
            function($message) {
                return $this->createJob(json_decode($message['Body'], true), $message, (int) $message['Attributes']['ApproximateReceiveCount']);
            },
            $response->get('Messages') ?: []
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($job)
    {
        $message = $job->message();
        $this->_client->deleteMessage([
            'QueueUrl' => $this->_name,
            'ReceiptHandle' => $message['ReceiptHandle'],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function release($job, $options = [])
    {
        $message = $job->message();
        $this->_client->changeMessageVisibility([
            'QueueUrl' => $this->_name,
            'ReceiptHandle' => $message['ReceiptHandle'],
            'VisibilityTimeout' => 0,
        ]);
    }
}