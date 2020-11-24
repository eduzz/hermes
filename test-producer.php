<?php

require_once __DIR__ . '/vendor/autoload.php';

use Eduzz\Hermes\Hermes;

$hermes = new Hermes();

$message = new Eduzz\Hermes\Examples\Message\Sun\User\Created(5, 'app.module.event');

$hermes->setConfig([
    'host' => 'rabbitmq',
    'port' => 5672,
    'username' => 'guest',
    'password' => 'guest',
    'vhost' => 'test',
    'connection_name' => 'producer',
]);

while (true) {
    $hermes->publish(
        $message
    );

    sleep(1);
}
