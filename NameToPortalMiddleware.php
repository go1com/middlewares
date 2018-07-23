<?php

namespace go1\middleware;

use Doctrine\Common\Cache\CacheProvider;
use go1\util\ErrorCodes;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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

    public function __invoke(Request $req)
    {
        if ($name = $req->get('portal')) {
            try {
                $response = $this->load($name);
                if ($response instanceof Response) {
                    return $response;
                }

                $req->attributes->set('portal', $response);
            }
            catch (HttpException $e) {
                $response = ['message' => 'Failed to load portal.', 'code' => ErrorCodes::X_SERVICE_UNREACHABLE];

                return new JsonResponse($response, 500);
            }
            catch (ClientException $e) {
                return new JsonResponse(['message' => 'Failed to load portal'], $e->getCode());
            }
        }
    }

    public function load($name)
    {
        $is404 = false;
        $portal = false;
        $cacheId = str_replace('%NAME%', $name, $this->cacheId);

        if ($this->cache->contains($cacheId)) {
            $portal = $this->cache->fetch($cacheId);
            $is404 = 404 === $portal;
        }

        if (empty($portal)) {
            $url = "{$this->portalUrl}/{$name}";
            $portal = json_decode($this->client->get($url)->getBody()->getContents());

            if (!$portal) {
                $is404 = true;
                $this->cache->save($cacheId, 404, $ttl = 30);
            }
            else {
                $this->cache->save($cacheId, $portal, $tll = 120);
            }
        }

        return !$is404 ? $portal : new JsonResponse(['message' => 'Portal not found.'], 404);
    }
}
