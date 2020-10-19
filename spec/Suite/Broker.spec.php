<?php
namespace Lead\Queue\Spec;

use Lead\Queue\Adapter\InMemory;
use Lead\Queue\Job;

describe("Broker", function() {

    describe("->push()", function() {

        it("pushes a job", function() {
            $broker = new InMemory();

            $job = new Job(['broker' => $broker]);
            $job->data(['event' => 'EventName']);

            $broker->push($job);
            $job = $broker->pull();

            expect($job->payload())->toBe([
                'uuid' => $job->id(),
                'class' => Job::class,
                'maxTries' => 0,
                'timeout' => null,
                'expiresAt' => null,
                'attempts' => 0,
                'data' => ['event' => 'EventName']
            ]);
        });

    });

});
