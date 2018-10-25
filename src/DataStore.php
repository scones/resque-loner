<?php

declare(strict_types=1);

namespace ResqueLoner;

use Predis\Client;

class DataStore
{
    private $namespace = 'resque';
    private $redis;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function isLonerQueued(string $queueName, string $id): bool
    {
        return '1' === $this->redis->get($this->getResqueLonerKey($queueName, $id));
    }

    public function markLonerAsQueued(string $queueName, string $id): void
    {
        $this->redis->set($this->getResqueLonerKey($queueName, $id), 1);
    }

    public function markLonerAsUnqueued(string $queueName, string $id): void
    {
        $this->redis->del($this->getResqueLonerKey($queueName, $id));
    }

    private function getResqueLonerKey(string $queueName, string $id): string
    {
        return "{$this->namespace}:loners:queue:{$queueName}:job:{$id}";
    }
}
