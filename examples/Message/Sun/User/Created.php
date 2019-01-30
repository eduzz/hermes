<?php

namespace Eduzz\Hermes\Examples\Message\Sun\User;

use Eduzz\Hermes\Message\AbstractMessage;

class Created extends AbstractMessage
{
    public function __construct($data, $topic = 'app.module.event')
    {
        parent::__construct($topic, $data);
    }
}
