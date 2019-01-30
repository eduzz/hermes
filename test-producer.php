<?php

require_once __DIR__ . '/vendor/autoload.php';

use Eduzz\Hermes\Hermes;

$hermes = new Hermes();

$message = new Eduzz\Hermes\Examples\Message\Sun\User\Created(5, "Angelo Silva");

$hermes->setConfig([
    'host' => 'localhost',
    'port' => 5672,
    'username' => 'guest',
    'password' => 'guest'
]);

$hermes->publish(
    $message
);
