<?php
/**
 * @package Hermes
 * @link    https://github.com/eduzz/hermes/
 */

namespace Eduzz\Hermes;

use Eduzz\Hermes\Consumer\Consumer;
use Eduzz\Hermes\Message\AbstractMessage;
use Eduzz\Hermes\Publisher\Publisher;

class Hermes extends CommonOperations
{
    private $publisher;

    private $consumer;

    private $qos = 1;

    public function publish(
        AbstractMessage $message,
        $exchange = 'eduzz'
    ) {
        $this->getDefaultPublisher()->send($message, $exchange);

        return $this;
    }

    public function consumer()
    {
        return $this->getDefaultConsumer();
    }

    public function setQos($qos)
    {
        $this->qos = $qos;
    }

    public function addListenerTo($queue, $callback, $errorHandling = true)
    {
        $this->getDefaultConsumer()->setQos($this->qos);
        $this->getDefaultConsumer()->addListenerTo($queue, $callback, $errorHandling);

        return $this;
    }

    public function start()
    {
        $this->getDefaultConsumer()->start();

        return $this;
    }

    //@codeCoverageIgnoreStart
    private function getDefaultConsumer()
    {
        if ($this->consumer instanceof Consumer) {
            return $this->consumer;
        }

        $this->connect();

        $this->consumer = (new Consumer($this->config))
            ->setAMQPConnection($this->amqpConnection)
            ->setChannel($this->channel);

        return $this->consumer;
    }
    //@codeCoverageIgnoreEnd

    public function setConsumer(Consumer $consumer)
    {
        $this->consumer = $consumer;

        return $this;
    }

    public function setPublisher(Publisher $publisher)
    {
        $this->publisher = $publisher;

        return $this;
    }

    //@codeCoverageIgnoreStart
    public function getDefaultPublisher()
    {
        if ($this->publisher instanceof Publisher) {
            return $this->publisher;
        }

        $this->connect();

        $this->publisher = (new Publisher($this->config))
            ->setAMQPConnection($this->amqpConnection)
            ->setChannel($this->channel);
        return $this->publisher;
    }
    //@codeCoverageIgnoreEnd
}
