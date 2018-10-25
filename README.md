
# Resque Loner

This is a plugin ensure a job with a specific set of arguments can't be queued twice.
Usefulness ranges from "Ensure a customer is mailed just once" up to "fire a task as often as wanted, without caring (fire and forget)"

## Install

In most cases it should suffice to just install it via composer.

`composer require scones/resque-loner "*@stable"`

## Usage

You will need to have a psr-14 listener provider configured ($listenerProvider).
You will need to have a psr-14 task processor configured (using the same listener provider, added to resque and worker and job).

Having that, it will boil down to:

```php
// assuming PredisClient is initialized in $redisClient
$datastore = new DataStore($redisClient);

$resqueLoner = new ResqueLoner($datastore, $listenerProvider);
$resqueLogger->register();
```

You can also always inspect the examples: https://github.com/scones/resque-examples
