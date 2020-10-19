<?php
namespace Lead\Queue\Spec\Adapter;

use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use Lead\Queue\Job;
use Lead\Queue\Adapter\AzureQueue;

describe("AzureQueue", function() {

    beforeAll(function() {
        // docker run -p 10000:10000 -p 10001:10001 -d mcr.microsoft.com/azure-storage/azurite
        // docker run --rm --tty --interactive -p 10000:10000 -p 10001:10001 mcr.microsoft.com/azure-storage/azurite

        $this->client = QueueRestProxy::createQueueService('DefaultEndpointsProtocol=http;AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;QueueEndpoint=http://127.0.0.1:10001/devstoreaccount1;');
        $this->client->createQueue('default');
        $this->broker = new AzureQueue([
            'name' => 'default',
            'client' => $this->client
        ]);
    });

    afterAll(function() {
        if (isset($this->client)) {
            $this->client->deleteQueue('default');
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
