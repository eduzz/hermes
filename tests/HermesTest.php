<?php

namespace Eduzz\Hermes\Tests;

use Mockery as M;

use Eduzz\Hermes\Hermes;
use Eduzz\Hermes\Consumer\Consumer;
use Eduzz\Hermes\Publisher\Publisher;
use Eduzz\Hermes\Message\AbstractMessage;

class HermesTest extends BaseTest
{
    public function testHermesCanBeInstantiated()
    {
        $hermes = new Hermes();

        $this->assertInstanceOf(Hermes::class, $hermes);
    }

    public function testHermesShouldSendMessage()
    {
        $publisherMock = M::mock(Publisher::class)
            ->shouldReceive('send')
            ->once()
            ->withArgs(
                [
                AbstractMessage::class,
                M::type('string')
                ]
            )
            ->andReturnSelf()
            ->getMock();

        $messageMock = M::mock(AbstractMessage::class);

        $hermes = new Hermes();
        $hermes->setPublisher($publisherMock);

        $this->assertSame($hermes, $hermes->publish($messageMock));
    }

    public function testHermesShouldSetPublisher()
    {
        $publisherSpy = M::spy(Publisher::class);

        $hermes = new Hermes();

        $returnOfSetPublisher = $hermes->setPublisher($publisherSpy);

        $this->assertSame($hermes, $returnOfSetPublisher);
    }

    public function testHermesShouldAddListenerToQueueAndStartProcessing()
    {
        $args = [
            'routingKey' => 'app.module.action'
        ];

        $callback = function ($msg) {
            // RabbitMQ consumer callback example
        };

        $consumerMock = M::mock(Consumer::class)
            ->shouldReceive('addListenerTo')
            ->withArgs(
                [
                    $args['routingKey'],
                    M::type('Closure'),
                    M::type('bool')
                ]
            )
            ->andReturnSelf()
            ->shouldReceive('setQos')
            ->withArgs([
                M::type('int')
            ])
            ->andReturnTrue()
            ->shouldReceive('start')
            ->withNoArgs()
            ->andReturnSelf()
            ->getMock();

        $hermes = new Hermes();

        $hermes->setConsumer($consumerMock);

        $this->assertsame(
            $hermes,
            $hermes->addListenerTo(
                $args['routingKey'],
                $callback
            )
        );

        $this->assertsame(
            $hermes,
            $hermes->start()
        );
    }
}
