<?php
namespace Lead\Queue\Adapter;

use InvalidArgumentException;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageOptions;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use Lead\Queue\Message;

/**
 *
 * AzureQueue Broker
 *
 * `delay` is supported through push
 * `ttr` is supported through pull
 *
 */
class AzureQueue extends \Lead\Queue\Broker
{
    /**
     * Azure Queue Storage constructor.
     *
     * @param string $name
     * @param QueueRestProxy $queueRestProxy
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
        $createMessageOptions = new CreateMessageOptions();

        if (!empty($options['delay'])){
            $createMessageOptions->setVisibilityTimeoutInSeconds((int) $options['delay']); // Max 7 days
        }

        $this->_client->createMessage(
            $this->_name,
            json_encode($job->payload()),
            $createMessageOptions
        );
    }

    /**
     * @inheritDoc
     */
    public function fetch($max, $options = [])
    {
        $listMessageOptions = new ListMessagesOptions();
        $listMessageOptions->setNumberOfMessages($max);

        if (!empty($options['timeout'])){
            $listMessageOptions->setTimeout($options['timeout']);
        }

        if (!empty($options['ttr'])){
            $listMessageOptions->setVisibilityTimeoutInSeconds((int) $options['ttr']); // Max 7 days
        }

        $listMessageResult = $this->_client->listMessages(
            $this->_name,
            $listMessageOptions
        );

        $messages = $listMessageResult->getQueueMessages();

        return array_map(
            function($message) {
                $payload = json_decode($message->getMessageText(), true);
                return $this->createJob($payload, $message, $message->getDequeueCount());
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
        $this->_client->deleteMessage(
            $this->_name,
            $message->getMessageId(),
            $message->getPopReceipt()
        );
    }

    /**
     * @inheritDoc
     */
    public function release($job, $options = [])
    {
        $message = $job->message();
        $this->_client->updateMessage(
            $this->_name,
            $message->getMessageId(),
            $message->getPopReceipt(),
            json_encode($job->payload()),
            $options['delay'] ?? 0
        );
    }
}