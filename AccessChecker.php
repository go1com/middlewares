<?php

namespace go1\middleware;

use Symfony\Component\HttpFoundation\Request;

/**
 * This is not a middleware, but useful to share across our micro services.
 */
class AccessChecker
{
    /**
     * @param Request $req
     * @param string  $portalName
     * @return null|bool|1
     */
    public function isPortalAdmin(Request $req, $portalName)
    {
        if ($this->isAccountsAdmin($req)) {
            return 1;
        }

        if (!$user = $this->getUser($req)) {
            return null;
        }

        $accounts = isset($user->accounts) ? $user->accounts : [];
        foreach ($accounts as &$account) {
            if ($portalName === $account->instance) {
                if (!empty($account->roles) && in_array('administrator', $account->roles)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isAccountsAdmin(Request $req)
    {
        if (!$user = $this->getUser($req)) {
            return null;
        }

        return in_array('Admin on #Accounts', isset($user->roles) ? $user->roles : []);
    }

    private function getUser(Request $req)
    {
        $payload = $req->get('jwt.payload');
        if ('user' === $payload->object->type) {
            $user = &$payload->object->content;
            if (!empty($user->mail)) {
                return $user;
            }
        }

        return false;
    }
}
