<?php

namespace Eduzz\Hermes\Tests;

use Mockery as M;

use Eduzz\Hermes\RoutingKeyParser;

class RoutingKeyParserTest extends BaseTest
{
    public function testRoutingKeyParserShouldBeAValidRoutingKey()
    {
        $this->assertEquals(true, RoutingKeyParser::isValid('app.module.action'));
    }

    public function testRoutingKeyParserShouldBeAInvalidValidRoutingKey()
    {
        $this->assertEquals(false, RoutingKeyParser::isValid('app.moduleINVALID'));
    }
}
