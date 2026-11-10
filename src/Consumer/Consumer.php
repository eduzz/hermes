<?php

namespace Eduzz\Hermes\Consumer;

use Eduzz\Hermes\CommonOperations;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Consumer extends CommonOperations
{
    const DEFAULT_MAX_DELIVERY_ATTEMPTS = 3;
    const DEAD_LETTER_REJECTED_REASON = 'rejected';

    private $qos = 1;

    /**
     * @param function $callback
     */
    public function addListenerTo($name, $callback, $errorHandling = true, $maxAttempts = null)
    {
        $this->connect();

        if($errorHandling) {
            $hermesCallback = $this->getHermesCallbackWithHandlingFor($callback, $maxAttempts, $name);
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

    private function getHermesCallbackWithHandlingFor($callback, $maxAttempts = null, $queueName = null)
    {
        return function ($msg) use ($callback, $maxAttempts, $queueName) {
            //@codeCoverageIgnoreStart
            $msg->body = json_decode($msg->body);

            try {
                $callback($msg);
            } catch(\Exception $e) {
                if ($maxAttempts !== null) {
                    $dlqRoutingKey = $queueName ? $this->getDlqQueueNameFor($queueName) : null;
                    return $this->nackOrDeadLetter($msg, $maxAttempts, $dlqRoutingKey);
                }
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

    public function nackOrDeadLetter(
        $msg,
        $maxAttempts = self::DEFAULT_MAX_DELIVERY_ATTEMPTS,
        $dlqRoutingKey = null,
        $exchange = self::DEFAULT_EXCHANGE
    ) {
        $deliveryAttempt = $this->getDeliveryAttempt($msg);

        if ($deliveryAttempt < intval($maxAttempts)) {
            return $this->nack($msg, false);
        }

        $routingKey = $this->resolveDlqRoutingKey($msg, $dlqRoutingKey);
        $this->publishToDlq($msg, $routingKey, $exchange);

        return $this->ack($msg);
    }

    public function getDeliveryAttempt($msg)
    {
        $headers = $this->extractApplicationHeaders($msg);

        if (empty($headers['x-death']) || !is_array($headers['x-death'])) {
            return 1;
        }

        $deathCount = 0;

        foreach ($headers['x-death'] as $entry) {
            if ($entry instanceof AMQPTable) {
                $entry = $entry->getNativeData();
            }

            if (!is_array($entry)) {
                continue;
            }

            $reason = isset($entry['reason']) ? $entry['reason'] : null;
            if ($reason !== self::DEAD_LETTER_REJECTED_REASON) {
                continue;
            }

            $count = isset($entry['count']) ? intval($entry['count']) : 0;
            if ($count > $deathCount) {
                $deathCount = $count;
            }
        }

        return $deathCount + 1;
    }

    private function extractApplicationHeaders($msg)
    {
        if (!method_exists($msg, 'get_properties')) {
            return array();
        }

        $properties = $msg->get_properties();
        $headers = isset($properties['application_headers']) ? $properties['application_headers'] : null;

        if ($headers instanceof AMQPTable) {
            return $headers->getNativeData();
        }

        return is_array($headers) ? $headers : array();
    }

    private function resolveDlqRoutingKey($msg, $dlqRoutingKey = null)
    {
        if (!empty($dlqRoutingKey)) {
            return $dlqRoutingKey;
        }

        $headers = $this->extractApplicationHeaders($msg);
        if (!empty($headers['x-death']) && is_array($headers['x-death'])) {
            foreach ($headers['x-death'] as $entry) {
                if ($entry instanceof AMQPTable) {
                    $entry = $entry->getNativeData();
                }

                if (!is_array($entry)) {
                    continue;
                }

                $reason = isset($entry['reason']) ? $entry['reason'] : null;
                if ($reason === self::DEAD_LETTER_REJECTED_REASON && !empty($entry['queue'])) {
                    return $this->getDlqQueueNameFor($entry['queue']);
                }
            }
        }

        if (!empty($this->lastQueueCreated)) {
            return $this->getDlqQueueNameFor($this->lastQueueCreated);
        }

        if (isset($msg->delivery_info['routing_key']) && !empty($msg->delivery_info['routing_key'])) {
            return $this->getDlqQueueNameFor($msg->delivery_info['routing_key']);
        }

        return self::DLQ_SUFFIX;
    }

    private function publishToDlq($msg, $routingKey, $exchange)
    {
        $body = is_string($msg->body) ? $msg->body : json_encode($msg->body);
        $dlqMessage = new AMQPMessage($body);

        $channel = isset($msg->delivery_info['channel']) ? $msg->delivery_info['channel'] : $this->channel;
        $channel->basic_publish($dlqMessage, $exchange, $routingKey);
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
