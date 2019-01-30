<?php

namespace Eduzz\Hermes\Tests;

use Mockery as M;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Eduzz\Hermes\Publisher\Publisher;

class PublisherTest extends BaseTest
{
    public function testPublisherShouldSendMessage()
    {
        $messageContent = json_encode(['id' => 1, 'message' => 'This is my message']);
        $messageContext = 'app.module.action';

        $hermesMessageMock = M::mock(AbstractMessage::class)
            ->shouldReceive('getTopic')
            ->withNoArgs()
            ->andReturn($messageContext)
            ->shouldReceive('setTopic')
            ->with($messageContext)
            ->andReturnNull()
            ->shouldReceive('getMessage')
            ->andReturn($messageContent)
            ->shouldReceive('setMessage')
            ->with($messageContent)
            ->andReturnNull()
            ->getMock();

        $channelMock = M::mock(AMQPChannel::class)
            ->shouldReceive('basic_publish')
            ->withArgs([AMQPMessage::class, 'eduzz', $hermesMessageMock->getTopic()])
            ->andReturnNull()
            ->shouldReceive('exchange_declare')
            ->withArgs(['eduzz', 'topic', false, true, false])
            ->andReturnNull()
            ->getMock();

        $amqpConnectionMock = M::mock(AMQPConnection::class)
            ->shouldReceive('channel')
            ->withNoArgs()
            ->andReturn($channelMock)
            ->getMock();

        $publisher = new Publisher();
        $publisher->setAMQPConnection($amqpConnectionMock);

        $this->assertSame($publisher, $publisher->send($hermesMessageMock));
    }
}
