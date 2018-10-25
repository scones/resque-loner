<?php

declare(strict_types=1);

namespace ResqueLoner\Tests;

use PHPUnit\Framework\TestCase;
use Predis\Client;
use ResqueLoner\DataStore;

class DataStoreTest extends TestCase
{
    public function setUp()
    {
        $this->redis = $this->buildRedisMock();
        $this->datastore = new DataStore($this->redis);
    }

    public function tearDown()
    {
        unset($this->redis);
        unset($this->datastore);
    }

    public function testIsLonerQueuedReturnFalseWhenTheValueIsNotOne()
    {
        $this->redis->expects($this->once())
            ->method('get')
            ->with('resque:loners:queue:some_queue:job:some_id')
            ->willReturn('0')
        ;

        $result = $this->datastore->isLonerQueued('some_queue', 'some_id');
        $this->assertFalse($result);
    }

    public function testIsLonerQueuedReturnTrueWhenTheValueIsOne()
    {
        $this->redis->expects($this->once())
            ->method('get')
            ->with('resque:loners:queue:some_queue:job:some_id')
            ->willReturn('1')
        ;

        $result = $this->datastore->isLonerQueued('some_queue', 'some_id');
        $this->assertTrue($result);
    }

    public function testMarkLonerAsQueuedShouldSetTheKeyToOne()
    {
        $this->redis->expects($this->once())
            ->method('set')
            ->with('resque:loners:queue:some_queue:job:some_id', 1)
        ;

        $this->datastore->markLonerAsQueued('some_queue', 'some_id');
    }

    public function testMarkLonerAsUnqueuedShouldSetTheKeyToOne()
    {
        $this->redis->expects($this->once())
            ->method('del')
            ->with('resque:loners:queue:some_queue:job:some_id')
        ;

        $this->datastore->markLonerAsUnqueued('some_queue', 'some_id');
    }

    public function testSetNamespaceShouldAlterTheRedisKey()
    {
        $this->datastore->setNamespace('some_namespace');
        $this->redis->expects($this->once())
            ->method('del')
            ->with('some_namespace:loners:queue:some_queue:job:some_id')
        ;

        $this->datastore->markLonerAsUnqueued('some_queue', 'some_id');
    }

    private function buildRedisMock()
    {
        return $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['set', 'del', 'get'])
            ->getMock()
        ;
    }
}
