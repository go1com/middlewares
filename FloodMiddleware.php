<?php

namespace go1\middleware;

use go1\flood\Flood;
use go1\util\Error;
use Symfony\Component\HttpFoundation\Request;

class FloodMiddleware
{
    private $flood;
    private $ipLimit;
    private $ipWindow;

    public function __construct(Flood $flood, $ipLimit = 50, $ipWindow = 3600, $eventName = 'flood')
    {
        $this->flood = $flood;
        $this->ipLimit = $ipLimit;
        $this->ipWindow = $ipWindow;
        $this->eventName = $eventName;
    }

    public function __invoke(Request $req)
    {
        // Make sure the IP address is not blocked.
        if (!$this->flood->isAllowed($this->eventName, $this->ipLimit, $this->ipWindow)) {
            return Error::simpleErrorJsonResponse('Too many connections on same IP address.', 429);
        }
        $this->flood->register($this->eventName, $this->ipWindow);
    }


}
