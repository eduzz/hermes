<?php

require_once __DIR__ . '/vendor/autoload.php';

use Eduzz\Hermes\Hermes;

$hermes = new Hermes();

$hermes->setConfig([
    'host' => 'rabbitmq',
    'port' => 5672,
    'username' => 'guest',
    'password' => 'guest',
    'vhost' => 'test',
    'connection_name' => 'consumer',
]);

$queueName = $hermes->addQueue('teste', true)
    ->bind('app.module.event')
    ->getLastQueueCreated();

$hermes->addListenerTo($queueName, function ($msg, $consumer) use ($hermes) {
    try {
        echo $msg->body;
    } catch (Exception $e) {
        return $msg->delivery_info['channel']
            ->basic_nack(
                $msg->delivery_info['delivery_tag']
            );
    }

    $msg->delivery_info['channel']
        ->basic_ack(
            $msg->delivery_info['delivery_tag']
        );
}, false);

$hermes->start();
