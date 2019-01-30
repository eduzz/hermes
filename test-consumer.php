<?php

require_once __DIR__ . '/vendor/autoload.php';

use Eduzz\Hermes\Hermes;

$hermes = new Hermes();

$hermes->setConfig([
    'host' => '127.0.0.1',
    'port' => 5672,
    'username' => 'guest',
    'password' => 'guest'
]);

$queueName = $hermes->addQueue('teste', true)
    ->bind('app.module.event')
    ->getLastQueueCreated();

$hermes->addListenerTo($queueName, function($msg, $consumer) use ($hermes) {
    try {
        throw new Exception(json_encode($msg->body));
    } catch(Exception $e) {
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
