<?php

namespace go1\report_helpers\tests;

use Aws\S3\S3Client;
use Doctrine\DBAL\DriverManager;
use Elasticsearch\Client as ElasticsearchClient;
use go1\flood\Flood;
use go1\middleware\FloodMiddleware;
use go1\report_helpers\Export;
use go1\report_helpers\ReportHelpersServiceProvider;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
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
        $floodMiddleWare = new FloodMiddleware($flood, 50, 3600, $eventName);
        $floodMiddleWare->__invoke(new Request());
        $floodMiddleWare->__invoke(new Request());
        $floodMiddleWare->__invoke(new Request());
        $this->assertEquals(3, $flood->count($eventName));
    }
}
