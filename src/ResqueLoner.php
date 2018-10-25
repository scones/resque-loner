<?php

declare(strict_types=1);

namespace ResqueLoner;

use Psr\EventDispatcher\ListenerProviderInterface;
use Resque\Interfaces\PayloadableTask;
use Resque\Tasks\AfterEnqueue;
use Resque\Tasks\BeforeEnqueue;

class ResqueLoner
{
    private $listenerProvider;
    private $datastore;

    public function __construct(DataStore $datastore, ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
        $this->datastore = $datastore;
    }

    public function register()
    {
        $this->listenerProvider->addListener(function (BeforeEnqueue $task) {
            return $this->lockJob($task);
        });

        $this->listenerProvider->addListener(function (AfterEnqueue $task) {
            return $this->unlockJob($task);
        });
    }

    private function lockJob(PayloadableTask $task)
    {
        $payload = $task->getPayload();
        $id = $this->buildIdFromPayload($payload);
        if ($this->datastore->isLonerQueued($payload['queue_name'], $id)) {
            $payload['skip_queue'] = true;
        } else {
            $this->datastore->markLonerAsQueued($payload['queue_name'], $id);
        }
        $task->setPayload($payload);
        return $task;
    }

    private function unlockJob(PayloadableTask $task)
    {
        $payload = $task->getPayload();
        if (empty($payload['skip_queue'])) {
            $id = $this->buildIdFromPayload($payload);
            $this->datastore->markLonerAsUnqueued($payload['queue_name'], $id);
        }
    }

    private function buildIdFromPayload(array $payload)
    {
        return md5(print_r($payload, true));
    }
}
