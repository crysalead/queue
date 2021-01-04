<?php
namespace Lead\Queue\Spec\Adapter;

use Aws\Sqs\SqsClient;
use Lead\Queue\Job;
use Lead\Queue\Adapter\InMemory;

describe("InMemory", function() {

    beforeAll(function() {
        $this->broker = new InMemory();
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

    describe("->pull()", function() {

        it("returns null when no job exists", function() {

            $job = $this->broker->pull();
            expect($job)->toBe(null);

        });

    });

});
