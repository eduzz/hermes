<?php

namespace Eduzz\Hermes\Message;

use Eduzz\Hermes\RoutingKeyParser;

abstract class AbstractMessage
{
    private $topic;
    private $message;

    public function __construct($topic, $message)
    {
        $this->setTopic($topic);
        $this->setMessage($message);
    }

    /**
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    /**
     * @param array $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getTopic()
    {
        return $this->topic;
    }

    public function getMessage()
    {
        return json_encode($this->message);
    }
}
