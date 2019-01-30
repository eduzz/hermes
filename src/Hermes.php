<?php
/**
 * @package Hermes
 * @link    https://bitbucket.org/eduzz/hermes
 */

namespace Eduzz\Hermes;

use Eduzz\Hermes\Publisher\Publisher;
use Eduzz\Hermes\Message\AbstractMessage;
use Eduzz\Hermes\Consumer\Consumer;
use Eduzz\Hermes\Exception\HermesInvalidArgumentException;

class Hermes extends CommonOperations
{
    private $publisher;

    private $consumer;

    private $qos = 1;

    public function publish(
        AbstractMessage $message,
        $exchange = 'eduzz'
    ) {
        if (!($this->publisher instanceof Publisher)) {
            //@codeCoverageIgnoreStart
            $this->setPublisher($this->getDefaultPublisher());
            //@codeCoverageIgnoreEnd
        }

        $this->publisher->send($message, $exchange);

        return $this;
    }

    public function consumer() {
        return $this->consumer;
    }

    public function setQos($qos) {
        $this->qos = $qos;
    }

    public function addListenerTo($queue, $callback, $errorHandling = true)
    {
        if (!($this->consumer instanceof Consumer)) {
            //@codeCoverageIgnoreStart
            $this->setConsumer($this->getDefaultConsumer());
            //@codeCoverageIgnoreEnd
        }

        $this->consumer->setQos($this->qos);
        $this->consumer->addListenerTo($queue, $callback, $errorHandling);

        return $this;
    }

    public function start()
    {
        $this->consumer->start();

        return $this;
    }

    //@codeCoverageIgnoreStart
    private function getDefaultConsumer()
    {
        return new Consumer($this->config);
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
        return new Publisher($this->config);
    }
    //@codeCoverageIgnoreEnd
}
