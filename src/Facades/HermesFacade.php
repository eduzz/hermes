<?php

namespace Eduzz\Hermes\Facades;

use Illuminate\Support\Facades\Facade;

use Eduzz\Hermes\Hermes;

class HermesFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Hermes';
    }
}
