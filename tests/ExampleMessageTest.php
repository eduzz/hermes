<?php

namespace Eduzz\Hermes\Tests;

use Mockery as M;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Eduzz\Hermes\Examples\Message\Sun\User\Created;

class ExampleMessageTest extends BaseTest
{
    public function testMessageShouldBeInstantiated()
    {
        $message = new Created(
            ['id' => 1, 'message' => 'content']
        );

        $this->assertInstanceOf(Created::class, $message);

        $this->assertEquals(
            'app.module.event',
            $message->getTopic()
        );

        $this->assertEquals(
            json_encode(
                ['id' => 1, 'message' => 'content']
            ),
            $message->getMessage()
        );
    }
}
