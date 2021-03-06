<?php

namespace Eduzz\Hermes;

use Eduzz\Hermes\Exception\HermesInvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Wire\AMQPTable;

class CommonOperations
{
    protected $lastQueueCreated;

    protected $amqpConnection;

    protected $channel;

    protected $exchangeAlredyDeclared = false;

    protected $config;

    public function __construct($config = [
        'host' => '127.0.0.1',
        'port' => 5672,
        'username' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
        'connection_name' => null,
    ]
    ) {
        $this->setConfig($config);
    }

    public function setConfig($config)
    {
        if (!(is_array($config))) {
            throw new HermesInvalidArgumentException("Error, config must be an array, " . gettype($config) . " given");
        }

        if (!(array_key_exists('vhost', $config))) {
            $config['vhost'] = '/';
        }

        if (!(array_key_exists('connection_name', $config)) || empty($config['connection_name'])) {
            $config['connection_name'] = 'hermes-'.gethostname();
        }

        $this->config = $config;

        return $this;
    }

    public function addQueue($name = "", $createErrorQueue = true, $durable = true, $retryTtl = null, $retryQueueBind = null)
    {
        $this->connect();

        $arguments = array();
        $argumentsNack = array();
        if ($createErrorQueue) {
            $deadLetterConfigNack = [];

            if (!empty($retryQueueBind)) {
                $deadLetterConfigNack = [
                    "x-dead-letter-exchange" => 'eduzz',
                    "x-dead-letter-routing-key" => $this->getRetryQueueBind($name, $retryQueueBind),
                ];
                
                if (!empty($retryTtl)) {
                    $deadLetterConfigNack['x-message-ttl'] = intval($retryTtl);
                }
                $argumentsNack = new AMQPTable($deadLetterConfigNack);
            }

            $this->declareQueue(
                $this->getNackQueueNameFor($name),
                $argumentsNack,
                $durable
            );

            $this->bind($this->getNackQueueNameFor($name), $this->getNackQueueNameFor($name));

            $deadLetterConfig = [
                "x-dead-letter-exchange" => 'eduzz',
                "x-dead-letter-routing-key" => $this->getNackQueueNameFor($name, $retryQueueBind),
            ];

            $arguments = new AMQPTable($deadLetterConfig);
        }
        $this->lastQueueCreated = $this->declareQueue($name, $arguments, $durable);
        
        if ($createErrorQueue && !empty($retryQueueBind)) {
            $this->bind($retryQueueBind, $name);
        }
        return $this;
    }

    public function getRetryQueueBind($name, $retryQueueBind)
    {
        if (empty($retryQueueBind)) {
            return $this->getNackQueueNameFor($name);
        }
        return $retryQueueBind;
    }

    public function getLastQueueCreated()
    {
        return $this->lastQueueCreated;
    }

    protected function declareExchange($exchange)
    {
        if (!$this->exchangeAlredyDeclared) {
            $this->channel->exchange_declare(
                $exchange,
                'topic',
                false,
                true,
                false
            );

            $this->exchangeAlredyDeclared = true;
        }

        return $this;
    }

    protected function declareQueue($name, $arguments, $durable)
    {
        list($name, ) = $this->channel->queue_declare(
            $name,
            false,
            $durable,
            false,
            false,
            false,
            $arguments
        );

        return $name;
    }

    protected function getNackQueueNameFor($name)
    {
        return $name . ".nack";
    }

    public function bind($routingKey, $name = null, $exchange = 'eduzz')
    {
        $this->connect();
        $this->declareExchange($exchange);

        if (empty($name)) {
            $name = $this->lastQueueCreated;
        }

        $this->channel
            ->queue_bind(
                $name,
                $exchange,
                $routingKey
            );

        return $this;
    }

    public function connect()
    {
        if (!($this->amqpConnection instanceof AMQPConnection)) {
            //@codeCoverageIgnoreStart
            $this->setAmqpConnection($this->getDefaultAmqpConnection());
            //@codeCoverageIgnoreEnd
        }

        if (!($this->channel)) {
            $this->channel = $this->amqpConnection->channel();
        }

        return $this;
    }

    public function setAMQPConnection(AMQPConnection $amqpConnection)
    {
        $this->amqpConnection = $amqpConnection;

        return $this;
    }

    public function setChannel(AMQPChannel $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    //@codeCoverageIgnoreStart
    private function getDefaultAmqpConnection()
    {
        AMQPConnection::$LIBRARY_PROPERTIES['connection_name'] = array('S', $this->config['connection_name']);
        AMQPConnection::$LIBRARY_PROPERTIES['product'] = array('S', 'hermes');

        $amqpConnection = new AMQPConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['username'],
            $this->config['password'],
            $this->config['vhost']
        );

        return $amqpConnection;
    }
    //@codeCoverageIgnoreEnd
}
