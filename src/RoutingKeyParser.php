<?php

namespace Eduzz\Hermes;

class RoutingKeyParser
{
    public static function isValid($routingKey)
    {
        return preg_match('@^[a-z0-9_\-]+\.[a-z0-9_\-]+\.[a-z0-9_\-]+$@', $routingKey);
    }
}
