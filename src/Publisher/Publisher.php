<?php

namespace Eduzz\Hermes\Publisher;

use PhpAmqpLib\Message\AMQPMessage;

use Eduzz\Hermes\CommonOperations;

class Publisher extends CommonOperations
{
    /**
     * @param \Eduzz\Hermes\AbstractMessage $message
     */
    public function send($message, $exchange = 'eduzz')
    {
        $this->connect();
        $this->declareExchange($exchange);

        $amqpMessage = new AMQPMessage($message->getMessage());
        $this->channel->basic_publish($amqpMessage, $exchange, $message->getTopic());

        return $this;
    }
}
