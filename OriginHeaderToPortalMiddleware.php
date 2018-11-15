<?php

namespace go1\middleware;

use go1\util\ErrorCodes;
use HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OriginHeaderToPortalMiddleware extends NameToPortalMiddleware
{
    public function __invoke(Request $req)
    {
        if ($origin = $req->headers->get('origin')) {
            try {
                $parse = parse_url($origin);
                $response = $this->load($parse['host']);
                if (!$response instanceof Response) {
                    $req->attributes->set('portal', $response);
                }
            }
            catch (HttpException $e) {
                $response = ['message' => 'Failed to load portal.', 'code' => ErrorCodes::X_SERVICE_UNREACHABLE];

                return new JsonResponse($response, 500);
            }
        }
    }
}
