<?php

namespace go1\middleware;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MiddlewareServiceProvider implements ServiceProviderInterface
{
    public function register(Container $c)
    {
        $c['middleware.portal.name-to-portal'] = function (Container $c) {
            return new NameToPortalMiddleware($c['cache'], $c['client'], $c['portal_url']);
        };

        $c['middleware.uuid-to-jwt'] = function (Container $c) {
            return new UuidToJwtMiddleware($c['cache'], $c['client'], $c['user_url'], $c['private_key']);
        };

        $c['flood.middleware'] = function (Container $c) {
            $f = $c['flood.options'];

            return new FloodMiddleware($c['flood'], $f['ip.limit'], $f['ip.window'], $f['event.name']);
        };
    }
}
