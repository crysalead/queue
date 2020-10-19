<?php
namespace Lead\Queue\Spec\Adapter;

use Pheanstalk\Pheanstalk;
use Lead\Queue\Job;
use Lead\Queue\Adapter\Beanstalk;

describe("Beanstalk", function() {

    beforeAll(function() {
        // docker run -p 11300:11300 -d schickling/beanstalkd
        // docker run --rm --tty --interactive  -p 11300:11300 schickling/beanstalkd

        $this->broker = new Beanstalk([
            'name' => 'default',
            'client' => Pheanstalk::create('127.0.0.1', 11300)
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
