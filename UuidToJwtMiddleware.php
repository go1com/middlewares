<?php

namespace go1\middleware;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UuidToJwtMiddleware
{
    private $cache;
    private $client;
    private $userUrl;
    private $privateKey;
    private $cacheId = 'middleware:user:uuid:%NAME%';

    public function __construct(CacheProvider $cache, Client $client, $userUrl, $privateKey)
    {
        $this->cache = $cache;
        $this->client = $client;
        $this->userUrl = $userUrl;
        $this->privateKey = $privateKey;
    }

    function __invoke(Request $req)
    {
        $uuid = $req->get('jwt-api-key');
        if ($uuid) {
            return !is_string($uuid)
                ? new JsonResponse(['message' => 'UUID must be string.'], 400)
                : $this->get($uuid, $req, str_replace('%UUID%', $uuid, $this->cacheId));
        }
    }

    private function get($uuid, $cacheId, Request $req)
    {
        if ($this->cache->contains($cacheId)) {
            $jwt = $this->cache->fetch($cacheId);
            $is404 = 404 === $jwt;
        }
        else {
            $url = "{$this->userUrl}/account/current/{$uuid}";
            $headers = ['JWT-Private-Key' => $this->privateKey];
            $current = json_decode($this->client->get($url, ['headers' => $headers])->getBody()->getContents());
            $jwt = isset($current->jwt) ? $current->jwt : null;
            $is404 = empty($jwt);
            $this->cache->save($cacheId, $jwt ?: 404, $jwt ? 120 : 30);
        }

        if (!empty($is404)) {
            return new JsonResponse(['message' => 'Failed to load JWT from UUID.'], 404);
        }

        $req->headers->set('Authorization', "Bearer {$jwt}");
    }
}
