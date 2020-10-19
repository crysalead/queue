<?php
namespace Lead\Queue\Spec\Adapter;

use Aws\Sqs\SqsClient;
use Lead\Queue\Job;
use Lead\Queue\Adapter\AmazonSqs;

describe("AmazonSqs", function() {

    beforeAll(function() {
        // docker run -p 9324:9324 -d softwaremill/elasticmq
        // docker run --rm --tty --interactive -p 9324:9324 softwaremill/elasticmq

        $this->client = new SqsClient([
            'version' => 'latest',
            'region' => 'eu-central-1',
            'endpoint' => 'http://127.0.0.1:9324',
            'credentials' => [
                'key'=> 'notValidKey',
                'secret'=>'notValidSecret'
            ]
        ]);
        $this->client->createQueue(['QueueName' => 'default']);
        $result = $this->client->getQueueUrl(['QueueName' => 'default']);
        $this->queueUrl = $result->get('QueueUrl');

        $this->broker = new AmazonSqs([
            'name' => $this->queueUrl,
            'client' => $this->client
        ]);
    });

    afterAll(function() {
        if (isset($this->client) && isset($this->queueUrl)) {
            $this->client->deleteQueue(['QueueUrl' => $this->queueUrl]);
        }
    });

    describe("->push()", function() {

        it("pushes a job", function() {

            $job = new Job(['broker' => $this->broker]);
            $job->data(['event' => 'EventName']);

            $this->broker->push($job);
            $job = $this->broker->pull();

            expect($job->payload())->toBe([
                'uuid' => $job->id(),
                'class' => Job::class,
                'maxTries' => 0,
                'timeout' => null,
                'expiresAt' => null,
                'attempts' => 0,
                'data' => ['event' => 'EventName']
            ]);


            $job->delete();

            $job = $this->broker->pull();
            expect($job)->toBe(null);
        });

    });

});
