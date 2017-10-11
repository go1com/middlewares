<?php

namespace go1\report_helpers\tests;

use Doctrine\DBAL\DriverManager;
use go1\flood\Flood;
use go1\middleware\FloodMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Vectorface\Whip\Whip;

class FloodMiddlewareTest extends TestCase
{
    public function testContainerValidation()
    {
        $db = DriverManager::getConnection(['url' => 'sqlite://sqlite::memory:']);
        $flood = new Flood($db, 'flood', new Whip(Whip::PROXY_HEADERS));
        $eventName = 'flood';
        $flood->install();
        $floodMiddleWare = new FloodMiddleware($flood, 3, 3600, $eventName);
        $floodMiddleWare->__invoke(new Request());
        $floodMiddleWare->__invoke(new Request());
        $this->assertNull($floodMiddleWare->__invoke(new Request()));

        $response = $floodMiddleWare->__invoke(new Request());
        $this->assertInstanceOf("Symfony\Component\HttpFoundation\JsonResponse", $response);
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals(3, $flood->count($eventName));
    }
}
