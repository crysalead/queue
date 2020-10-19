<?php
namespace Lead\Queue\Spec;

use Lead\Queue\Job;
use Lead\Queue\Adapter\InMemory;

describe("Job", function() {

    describe("->get()", function() {

        it("return the message", function() {

            $message = [
                'id' => 'c9dfe490-12d9-43ea-a85d-2beaa8520d0d',
                'body' => '{"event": "SomeEvent", "data": {"name": "Joe Example", "email": "joe@example.com"}}'
            ];
            $job = new Job(['broker' => new InMemory(), 'payload' => json_decode($message['body'], true), 'message' => $message]);

            expect($job->message())->toBe($message);

        });

        it("return the payload", function() {

            $message = [
                'id' => 'c9dfe490-12d9-43ea-a85d-2beaa8520d0d',
                'body' => '{"event": "SomeEvent", "data": {"name": "Joe Example", "email": "joe@example.com"}}'
            ];
            $job = new Job(['broker' => new InMemory(), 'payload' => json_decode($message['body'], true), 'message' => $message]);

            expect($job->payload())->toEqual([
                'event' => 'SomeEvent',
                'uuid' => $job->id(),
                'class' => Job::class,
                'maxTries' => 0,
                'timeout' => null,
                'expiresAt' => null,
                'attempts' => 0,
                'data' => [
                    'name' => 'Joe Example',
                    'email' => 'joe@example.com'
                ]
            ]);

        });

        it("return the data", function() {

            $message = [
                'id' => 'c9dfe490-12d9-43ea-a85d-2beaa8520d0d',
                'body' => '{"event": "SomeEvent", "data": {"name": "Joe Example", "email": "joe@example.com"}}'
            ];
            $job = new Job(['broker' => new InMemory(), 'payload' => json_decode($message['body'], true), 'message' => $message]);

            expect($job->data())->toEqual([
                'name' => 'Joe Example',
                'email' => 'joe@example.com'
            ]);

        });

    });

});
