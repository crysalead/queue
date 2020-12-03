<?php
namespace Lead\Queue\Adapter;

use InvalidArgumentException;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\PubSub\Topic;

/**
 *
 * GooglePubSub Broker
 *
 * `delay` is not supported
 * `ttr` is supported through the topic subscription configuration of the acknowledgment deadline property on subscription creation.
 *       The minimum custom deadline you can specify is 10 seconds (it's the default value).
 *       The maximum custom deadline you can specify is 600 seconds (10 minutes).
 *
 * It's also possible to set a retry policy in addition to `ttr`:
 *
 * $subscription = $topic->subscribe(<subscriptionName>, [
 *     'retryPolicy' => [
 *         'minimumBackoff' => '30.0s',
 *         'maximumBackoff' => '600.0s'
 *     ]
 * ]);
 *
 * The RetryPolicy will be triggered on NACKs or acknowledgement deadline according to minimumBackoff & maximumBackoff.
 *
 * `attempts` Upon the first delivery of a given message, `delivery_attempt` will have a value of 1.
 *            The value is calculated at best effort and is approximate. If a DeadLetterPolicy is not set on the subscription, this will be 0.
 *
 * Notes:
 * - A NACK is any call to ModifyAckDeadline with a 0 deadline.
 * - An ack_deadline exceeds event is whenever a message is not acknowledged within ack_deadline.
 */
class GooglePubSub extends \Lead\Queue\Broker
{
    /**
     * Google Cloud PubSub adapter.
     *
     * @param string $subscription
     * @param PubSubClient $client
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
        $this->_topic = $config['topic'] ?? $config['name'];
        $this->_client = $config['client'];
    }

    /**
     * @inheritDoc
     */
    public function push($job, $options = [])
    {
        $topic = $this->_client->topic($this->_topic);

        $message = ['data' => base64_encode(json_encode($job->payload()))];
        if (!empty($options)) {
            $message['attributes'] = $options;
        }
        $topic->publish($message);
    }

    /**
     * @inheritDoc
     */
    public function fetch($max, $options = [])
    {
        $pullOptions = [
            'maxMessages' => (int) $max,
            'returnImmediately' => empty($options['timeout']),
            'autoCreateSubscription' => true
        ];

        $autoCreateSubscription = $pullOptions['autoCreateSubscription'];
        unset($pullOptions['autoCreateSubscription']);

        $subscription = $this->_client->subscription($this->_name, $this->_topic);
        if ($autoCreateSubscription && !$subscription->exists()) {
            $subscription->create();
        }
        $messages = $subscription->pull($pullOptions);

        return array_map(
            function($message) {
                $payload = json_decode(base64_decode($message->data()), true);
                return $this->createJob($payload ? (array) $payload : [], $message, $message->deliveryAttempt() ?? 0);
            },
            $messages ?: []
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($job)
    {
        $message = $job->message();
        $this->_client->subscription($this->_name)->acknowledge($message);
    }

    /**
     * @inheritDoc
     */
    public function release($job, $options = [])
    {
        $message = $job->message();
        $this->_client->subscription($this->_name)->modifyAckDeadline($message, 0);
    }
}