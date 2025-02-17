<?php
namespace Lead\Queue\Spec\Adapter;

use Google\Cloud\PubSub\PubSubClient;
use Lead\Queue\Job;
use Lead\Queue\Adapter\GooglePubSub;

describe("GooglePubSub", function() {

    beforeAll(function() {
        // docker run -publish 8681:8681 google/cloud-sdk:emulators /bin/bash -c "gcloud beta emulators pubsub start --project=some-project-id --host-port='0.0.0.0:8681'"
        // docker run --rm --tty --interactive --publish 8681:8681 google/cloud-sdk:emulators /bin/bash -c "gcloud beta emulators pubsub start --project=some-project-id --host-port='0.0.0.0:8681'"


        $this->broker = new GooglePubSub([
            'name' => 'default',
            'client' => new PubSubClient([
                'hasEmulator' => true,
                'emulatorHost' => 'localhost:8681',
                'credentialsConfig' => [
                    'keyFile' => [
                        "client_id"     => "fake-fake-fake.apps.googleusercontent.com",
                        "client_secret" => "fake-fake-fake",
                        "refresh_token" => "fake-fake-fake",
                        "type"          => "authorized_user",
                    ]
                ]
            ])
        ]);
        $this->topic = $this->broker->client()->topic('default');
        if (!$this->topic->exists()) {
            $this->topic->create();
        }
        $this->subscription = $this->topic->subscription('default', [
            'retryPolicy' => [
                'minimumBackoff' => '30.0s',
                'maximumBackoff' => '600.0s'
            ]
        ]);
        if (!$this->subscription->exists()) {
            $this->subscription->create();
        }
    });

    afterAll(function() {
        if (isset($this->topic)) {
            $this->topic->delete();
        }
        if (isset($this->subscription)) {
            $this->subscription->delete();
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
