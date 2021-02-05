<?php

namespace go1\middleware;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function __invoke(Request $req, string $key = 'portal', $bypassCache = false)
    {
        if ($name = $req->get($key)) {
            try {
                $response = $this->load($name, $bypassCache);
                if ($response instanceof Response) {
                    return $response;
                }

                $req->attributes->set('portal', $response);
            } catch (HttpException $e) {
                $response = ['message' => 'Failed to load portal.', 'code' => 80000];

                return new JsonResponse($response, 500);
            }
        }
    }

    public function load($name, $bypassCache)
    {
        $is404 = false;
        $portal = false;
        $cacheId = str_replace('%NAME%', $name, $this->cacheId);

        if (!$bypassCache && $this->cache->contains($cacheId)) {
            $portal = $this->cache->fetch($cacheId);
            $is404 = 404 === $portal;
        }

        if (empty($portal)) {
            $url = "{$this->portalUrl}/{$name}?noKeyFixing=1";
            $portal = json_decode($this->client->get($url, ['http_errors' => false])->getBody()->getContents());

            if (!$portal) {
                $is404 = true;
                $this->cache->save($cacheId, 404, $ttl = 30);
            } else {
                $this->cache->save($cacheId, $portal, $tll = 120);
            }
        }

        return !$is404 ? $portal : new JsonResponse(['message' => 'Portal not found.'], 404);
    }
}
