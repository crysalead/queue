<?php
namespace Lead\Queue\Spec\Adapter;

use Predis\Client;
use Lead\Queue\Job;
use Lead\Queue\Adapter\Redis;

describe("Redis", function() {

    beforeAll(function() {
        // docker run -p 6379:6379 -d redis
        // docker run --rm --tty --interactive -p 6379:6379 redis

        $this->client = new Client('tcp://127.0.0.1:6379');
        $this->broker = new Redis([
            'name' => 'default',
            'client' => $this->client
        ]);
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
