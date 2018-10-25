<?php

declare(strict_types=1);

namespace ResqueLoner\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use Resque\Tasks\AfterEnqueue;
use Resque\Tasks\BeforeEnqueue;
use ResqueLoner\DataStore;
use ResqueLoner\ResqueLoner;

class ResqueLonerTest extends TestCase
{
    public function setUp()
    {
        $this->datastore = $this->buildDatastoreMock();
        $this->listenerProvider = $this->buildListenerProviderMock();
        $this->resqueLoner = new ResqueLoner($this->datastore, $this->listenerProvider);
    }

    public function tearDown()
    {
        unset($this->datastore);
        unset($this->listenerProvider);
        unset($this->resqueLoner);
    }

    public function testRegisterShouldRegisterTheHooks()
    {
        $queueName = 'some_queue';
        $payload = ['some' => 'data', 'queue_name' => $queueName];
        $id = '9335956c65f0bb85732d0f4ce6ab5650';
        $this->datastore->expects($this->once())
            ->method('isLonerQueued')
            ->with($queueName, $id)
            ->willReturn(false)
        ;

        $this->datastore->expects($this->once())
            ->method('markLonerAsQueued')
            ->with($queueName, $id)
        ;

        $this->datastore->expects($this->once())
            ->method('markLonerAsUnqueued')
            ->with($queueName, $id)
        ;

        $beforeTask = new BeforeEnqueue();
        $beforeTask->setPayload($payload);

        $this->listenerProvider->expects($this->at(0))
            ->method('addListener')
            ->with($this->callback(function ($callback) use ($beforeTask) {
                $callback($beforeTask);
                return true;
            }))
        ;

        $afterTask = new AfterEnqueue();
        $afterTask->setPayload($payload);

        $this->listenerProvider->expects($this->at(1))
            ->method('addListener')
            ->with($this->callback(function ($callback) use ($afterTask) {
                $callback($afterTask);
                return true;
            }))
        ;

        $this->resqueLoner->register();
    }

    public function testLockShouldAddSkipQueueWhenThereIsAJobQueued()
    {
        $queueName = 'some_queue';
        $payload = ['some' => 'data', 'queue_name' => $queueName];
        $id = '9335956c65f0bb85732d0f4ce6ab5650';
        $this->datastore->expects($this->once())
            ->method('isLonerQueued')
            ->with($queueName, $id)
            ->willReturn(true)
        ;

        $this->datastore->expects($this->never())->method('markLonerAsQueued');

        $this->datastore->expects($this->once())
            ->method('markLonerAsUnqueued')
            ->with($queueName, $id)
        ;

        $beforeTask = new BeforeEnqueue();
        $beforeTask->setPayload($payload);

        $this->listenerProvider->expects($this->at(0))
            ->method('addListener')
            ->with($this->callback(function ($callback) use ($beforeTask) {
                $callback($beforeTask);
                return true;
            }))
        ;

        $afterTask = new AfterEnqueue();
        $afterTask->setPayload($payload);

        $this->listenerProvider->expects($this->at(1))
            ->method('addListener')
            ->with($this->callback(function ($callback) use ($afterTask) {
                $callback($afterTask);
                return true;
            }))
        ;

        $this->resqueLoner->register();
    }

    public function buildDatastoreMock()
    {
        return $this->getMockBuilder(DataStore::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLonerQueued', 'markLonerAsQueued', 'markLonerAsUnqueued'])
            ->getMock()
        ;
    }

    public function buildListenerProviderMock()
    {
        return $this->getMockBuilder(ListenerProviderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addListener', 'getListenersForEvent'])
            ->getMock()
        ;
    }
}
