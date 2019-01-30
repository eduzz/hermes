<?php

namespace Eduzz\Hermes;

class RoutingKeyParser
{
    public static function isValid($routingKey)
    {
        return preg_match('@^[a-z]+\.[a-z]+\.[a-z]+$@', $routingKey);
    }
}
