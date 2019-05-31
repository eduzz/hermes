<?php

namespace Eduzz\Hermes;

class RoutingKeyParser
{
    public static function isValid($routingKey)
    {
        return preg_match('@^[a-z_\-]+\.[a-z_\-]+\.[a-z_\-]+$@', $routingKey);
    }
}
