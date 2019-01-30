<?php

namespace Eduzz\Hermes\Tests;

use Mockery as M;

use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Connection\AmqpConnection;

use Eduzz\Hermes\Exception\HermesInvalidArgumentException;
use Eduzz\Hermes\CommonOperations;

class CommonOperationsTest extends BaseTest
{
    public function testCommonOperationsShouldSetConfig()
    {
        $config = [
            'host' => 'localhost',
            'port' => 5672,
            'username' => 'guest',
            'password' => 'guest'
        ];

        $commonOperations = new CommonOperations();

        $returnOfSetConfig = $commonOperations->setConfig($config);

        $this->assertSame($commonOperations, $returnOfSetConfig);
    }

    public function testcommonOperationsShouldSetConnecton()
    {
        $amqpConnectionSpy = M::spy(AMQPConnection::class);

        $commonOperation = new CommonOperations();

        $this->assertSame($commonOperation, $commonOperation->setAMQPConnection($amqpConnectionSpy));
    }

    public function testCommonOperationsShouldSetConfigAndFailBecauseConfigIsEmpty()
    {
        $this->expectException(HermesInvalidArgumentException::class);

        $config = null;

        $commonOperations = new CommonOperations();

        $commonOperations->setconfig($config);
    }

    public function testCommonOperationsShouldAddQueueAndBindIt()
    {
        $args = [
            "queueName" => "queue_name",
            "nackQueueName" => "queue_name.nack",
            "exchange" => "exchange",
            "routingKey" => 'app.module.action'
        ];

        $amqpChannelMock = M::mock(AMQPChannel::class)
            ->shouldReceive('queue_declare')
            ->withArgs(
                [
                $args['queueName'],
                false,
                true,
                false,
                false,
                false,
                M::type(AMQPTable::class)
                ]
            )
            ->andReturn(
                [
                $args['queueName'],
                0,
                0
                ]
            )
            ->shouldReceive('queue_bind')
            ->withArgs(
                [
                $args['queueName'],
                $args['exchange'],
                $args['routingKey']
                ]
            )
            ->andReturnNull()
            ->shouldReceive('exchange_declare')
            ->withArgs(
                [
                $args['exchange'],
                'topic',
                false,
                true,
                false
                ]
            )
            ->andReturnNull()
            ->shouldReceive('queue_declare')
            ->withArgs(
                [
                $args['nackQueueName'],
                false,
                true,
                false,
                false,
                false,
                M::type('array')
                ]
            )
            ->andReturn(
                [
                $args['nackQueueName'],
                0,
                0
                ]
            )
            ->getMock();

        $amqpConnectionMock = M::mock(AMQPConnection::class)
            ->shouldReceive('channel')
            ->withNoArgs()
            ->andReturn($amqpChannelMock)
            ->getMock();

        $commonOperations = new CommonOperations();

        $commonOperations->setAMQPConnection($amqpConnectionMock);

        $this->assertSame(
            $commonOperations,
            $commonOperations->addQueue($args['queueName'])->bind($args['routingKey'], null, $args['exchange'])
        );

        $this->assertEquals($args['queueName'], $commonOperations->getLastQueueCreated());
    }

    public function testCommonOperationsShouldBindQueue()
    {
        $args = [
            'queueName' => 'queue_name',
            'routingKey' => 'app.module.action',
            'exchange' => 'exchange'
        ];

        $amqpChannelMock = M::mock(AMQPChannel::class)
            ->shouldReceive('exchange_declare')
            ->withArgs(
                [
                $args['exchange'],
                'topic',
                false,
                true,
                false
                ]
            )
            ->andReturnNull()
            ->shouldReceive('queue_bind')
            ->withArgs(
                [
                $args['queueName'],
                $args['exchange'],
                $args['routingKey']
                ]
            )
            ->andReturnNull()
            ->getMock();

        $amqpConnectionMock = M::mock(AMQPConnection::class)
            ->shouldReceive('channel')
            ->withNoArgs()
            ->andReturn($amqpChannelMock)
            ->getMock();

        $commonOperation = new CommonOperations();

        $commonOperation->setAMQPConnection($amqpConnectionMock);

        $this->assertSame($commonOperation, $commonOperation->bind($args['routingKey'], $args['queueName'], $args['exchange']));
    }


}
