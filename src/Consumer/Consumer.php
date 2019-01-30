<?php

namespace Eduzz\Hermes\Consumer;

use Eduzz\Hermes\CommonOperations;

class Consumer extends CommonOperations
{
    private $qos = 1;

    /**
     * @param function $callback
     */
    public function addListenerTo($name, $callback, $errorHandling = true)
    {
        $this->connect();

        if($errorHandling) {
            $hermesCallback = $this->getHermesCallbackWithHandlingFor($callback);
        } else {
            $hermesCallback = $this->getHermesCallbackWithoutHandlingFor($callback);
        }

        $this->channel->basic_qos(0, $this->qos, false);

        $this->channel->basic_consume($name, '', false, false, false, false, $hermesCallback);

        return $this;
    }

    public function setQos($qos) {
        $this->qos = $qos;
    }

    private function getHermesCallbackWithoutHandlingFor($callback)
    {
        return function ($msg) use ($callback) {
            //@codeCoverageIgnoreStart
            $msg->body = json_decode($msg->body);

            return $callback($msg, $this);
            //@codeCoverageIgnoreEnd
        };
    }

    private function getHermesCallbackWithHandlingFor($callback)
    {
        return function ($msg) use ($callback) {
            //@codeCoverageIgnoreStart
            $msg->body = json_decode($msg->body);

            try {
                $callback($msg);
            } catch(\Exception $e) {
                return $this->nack($msg);
            }

            return $this->ack($msg);
            //@codeCoverageIgnoreEnd
        };
    }

    public function nack($msg, $requeue = false)
    {
        return $msg
            ->delivery_info['channel']
            ->basic_nack(
                $msg->delivery_info['delivery_tag'],
                false,
                $requeue
            );
    }

    public function ack($msg)
    {
        return $msg
            ->delivery_info['channel']
            ->basic_ack(
                $msg->delivery_info['delivery_tag']
            );
    }

    public function start()
    {
        if(count($this->channel->callbacks) <= 0) {
            return;
        }

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        return $this;
    }
}
