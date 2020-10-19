# Queue

[![Build Status](https://travis-ci.com/crysalead/queue.svg?branch=master)](https://travis-ci.com/crysalead/queue)

## Install

```bash
composer require crysalead/queue
```

## Basic usage

### Create Queue instance

```php
$broker = new Lead\Queue\Adapter\Sqs(
    "https://queue.url",
    new SqsClient([
        'version' => 'latest',
        'region' => '<region>',
        'credentials' => [
            'key'=> '<key>',
            'secret'=>'<secretKey>'
        ]
    ])
);
```

### Listen on queue

Listening is a **blocking** call and runs in an infinite loop (up to default 20s polling timout). Your callback will be triggered when a new Message has arrived.

```php
$broker->listen(function($job) {

	if (!$job) {
		return;
	}
	/**
	 *
	 *  Process the job...
	 *
	 */

	// Delete the job from Queue.
	$job->delete();

});
```

### Shutting down the Queue

You may shutdown the queue by using the `shutdown()` method.

The Queue instance will respond to PCNTL signals in a safe manner that will not interrupt in the middle of Message processing.
You can install signal handlers in your code to cleanly and safely shutdown the service.

```php
pcntl_signal(
	SIGINT,
	function() use ($broker) {
		$broker->shutdown();

	}
);
```

### Acknowledgements

- [Syndicate](https://github.com/nimbly/Syndicate) (this repo is a simple fork of his brillant work).
