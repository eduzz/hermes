<?php

namespace Eduzz\Hermes\Tests;

use Mockery as M;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Eduzz\Hermes\Exception\HermesInvalidArgumentException;
use Eduzz\Hermes\Consumer\Consumer;

class ConsumerTest extends BaseTest
{
    public function testConsumerCanBeInstatiated()
    {
        $consumer = new Consumer();

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    private function getArgumentsForConsumer()
    {
        return [
            "routingKey" => 'app.module.action',
            "nackRoutingKey" => 'app.module.action.nack',
            "exchangeName" => 'eduzz',
            "exchangeMode" => 'topic'
        ];
    }

    private function getCallBackForConsumer()
    {
        return function ($msg) {
            // RabbitMQ consumer callback example
        };
    }

    private function getAmqpChannelMock($args, $isGonnaTestStart = true)
    {
        $amqpChannelMock = M::mock(AMQPChannel::class)
            ->shouldReceive('queue_declare')
            ->withArgs(
                [
                    $args['routingKey'],
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
                    $args['routingKey'],
                    0,
                    0
                ]
            )
            ->shouldReceive('basic_qos')
            ->withArgs([
                0,
                M::type('int'),
                false
            ])
            ->shouldReceive('queue_declare')
            ->withArgs(
                [
                    $args['nackRoutingKey'],
                    false,
                    true,
                    false,
                    false,
                    false
                ]
            )
            ->andReturn(
                [
                    $args['routingKey'],
                    0,
                    0
                ]
            )
            ->set('callbacks', null)
            ->getMock();

        $amqpChannelMock->shouldReceive('queue_bind')
            ->withArgs(
                [
                    $args['routingKey'],
                    $args['exchangeName'],
                    $args['routingKey']
                ]
            )
            ->andReturn(
                [
                    $args['routingKey'],
                    0,
                    0
                ]
            );

        $amqpChannelMock->shouldReceive('exchange_declare')
            ->withArgs(
                [
                    $args['exchangeName'],
                    $args['exchangeMode'],
                    false,
                    true,
                    false
                ]
            )
            ->andReturnNull();

        $amqpChannelMock->shouldReceive('wait')
            ->andSet('callbacks', [])
            ->withnoArgs()
            ->andReturnNull();

        $amqpChannelMock->shouldReceive('wait')
            ->withNoArgs()
            ->andReturnNull();

        $callbacks = [1];

        if(!$isGonnaTestStart) {
            $callbacks = [];
        }

        $amqpChannelMock->shouldReceive('basic_consume')
            ->andSet('callbacks', $callbacks)
            ->withArgs(
                [
                    $args['routingKey'],
                    '',
                    false,
                    false,
                    false,
                    false,
                    M::type(\Closure::class)
                ]
            )
            ->andReturn('amq.ctag-aSf9qglZGp6PSnD5CEuL0w');

        return $amqpChannelMock;
    }

    private function getAmqpConnectionMock($amqpChannelMock)
    {
        return M::mock(AMQPConnection::class)
            ->shouldReceive('channel')
            ->withNoArgs()
            ->andReturn($amqpChannelMock)
            ->getMock();
    }

    public function testConsumerShouldAddListenerWithHandlingErrorDesactived()
    {
        $args = $this->getArgumentsForConsumer();

        $callback = $this->getCallBackForConsumer();

        $amqpChannelMock = $this->getAmqpChannelMock($args);

        $amqpConnectionMock = $this->getAmqpConnectionMock($amqpChannelMock);

        $consumer = new Consumer();

        $consumer->setAmqpConnection($amqpConnectionMock);

        $this->assertSame(
            $consumer,
            $consumer->addListenerTo(
                $args['routingKey'],
                $callback,
                false
            )
        );
    }

    public function testConsumerShouldTryToStartProcessingWithoutCallbacks()
    {
        $args = $this->getArgumentsForConsumer();

        $callback = $this->getCallBackForConsumer();

        $amqpChannelMock = $this->getAmqpChannelMock($args, false);

        $amqpConnectionMock = $this->getAmqpConnectionMock($amqpChannelMock);

        $consumer = new Consumer();

        $consumer->setAmqpConnection($amqpConnectionMock);

        $this->assertSame(
            $consumer,
            $consumer->addListenerTo(
                $args['routingKey'],
                $callback
            )
        );

        $consumer->start();
    }

    public function testConsumerShouldNackMessage()
    {
        $amqpChannelMockWithNack = M::mock(AMQPMessage::class)
            ->shouldReceive('basic_nack')
            ->with(
                M::type('string'),
                false,
                false
            )
            ->andReturnTrue()
            ->getMock();

        $messageMock = M::mock(AMQPMessage::class);

        $messageMock->delivery_info = [
            'channel' => $amqpChannelMockWithNack,
            'delivery_tag' => 'string'
        ];

        $consumer = new Consumer();

        $this->assertTrue($consumer->nack($messageMock));
    }

    public function testConsumerShouldAckMessage()
    {
        $amqpChannelMockWithAck = M::mock(AMQPMessage::class)
            ->shouldReceive('basic_ack')
            ->with(
                M::type('string')
            )
            ->andReturnTrue()
            ->getMock();

        $messageMock = M::mock(AMQPMessage::class);

        $messageMock->delivery_info = [
            'channel' => $amqpChannelMockWithAck,
            'delivery_tag' => 'string'
        ];

        $consumer = new Consumer();

        $this->assertTrue($consumer->ack($messageMock));
    }

    public function testConsumerShouldAddListenerAndStartProcessing()
    {
        $args = $this->getArgumentsForConsumer();

        $callback = $this->getCallBackForConsumer();

        $amqpChannelMock = $this->getAmqpChannelMock($args);

        $amqpConnectionMock = $this->getAmqpConnectionMock($amqpChannelMock);

        $consumer = new Consumer();

        $consumer->setAmqpConnection($amqpConnectionMock);

        $this->assertSame(
            $consumer,
            $consumer->addListenerTo(
                $args['routingKey'],
                $callback
            )
        );

        $consumer->start();
    }

    public function testConsumerShouldAddListener()
    {
        $args = $this->getArgumentsForConsumer();

        $callback = $this->getCallBackForConsumer();

        $amqpChannelMock = $this->getAmqpChannelMock($args);

        $amqpConnectionMock = $this->getAmqpConnectionMock($amqpChannelMock);

        $consumer = new Consumer();

        $consumer->setAmqpConnection($amqpConnectionMock);

        $this->assertSame(
            $consumer,
            $consumer->addListenerTo(
                $args['routingKey'],
                $callback
            )
        );
    }
}
