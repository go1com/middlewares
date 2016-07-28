<?php

namespace go1\middleware;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NameToPortalMiddleware
{
    private $cache;
    private $client;
    private $portalUrl;
    private $cacheId = 'middleware:portal:%NAME%';

    public function __construct(CacheProvider $cache, Client $client, $portalUrl)
    {
        $this->cache = $cache;
        $this->client = $client;
        $this->portalUrl = rtrim($portalUrl, '/');
    }

    public function __invoke(Request $req)
    {
        $name = $req->get('portal');

        if ($name) {
            $this->cacheId = str_replace('%NAME%', $name, $this->cacheId);

            try {
                return $this->get($name, $this->cacheId, $req);
            }
            catch (HttpException $e) {
                $response = ['error' => 'Failed to load portal.', 'message' => $e->getMessage()];

                return new JsonResponse($response, 500);
            }
        }
    }

    private function get($name, $cacheId, Request $req)
    {
        $is404 = false;

        if ($this->cache->contains($cacheId)) {
            $portal = $this->cache->fetch($cacheId);
            $is404 = 404 === $portal;
        }

        if (empty($portal)) {
            $url = "{$this->portalUrl}/{$name}";
            $portal = json_decode($this->client->get($url)->getBody()->getContents());
            if (strtolower($name) !== strtolower($portal->title)) {
                $is404 = true;
                $this->cache->save($cacheId, 404, $ttl = 30);
            }
            else {
                $this->cache->save($cacheId, $portal, $tll = 120);
            }
        }

        if ($is404) {
            $response = ['error' => 'Failed to load portal.', 'message' => 'Portal not found.'];

            return new JsonResponse($response, 404);
        }

        $req->attributes->set('portal', $portal);
    }
}
