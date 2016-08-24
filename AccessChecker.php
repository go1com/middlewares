<?php

namespace go1\middleware;

use stdClass;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is not a middleware, but useful to share across our micro services.
 */
class AccessChecker
{
    /**
     * @param Request $req
     * @param string  $portalName
     * @return null|bool|stdClass
     */
    public function isPortalAdmin(Request $req, $portalName)
    {
        if ($this->isAccountsAdmin($req)) {
            return 1;
        }

        if (!$user = $this->validUser($req)) {
            return null;
        }

        $accounts = isset($user->accounts) ? $user->accounts : [];
        foreach ($accounts as &$account) {
            if ($portalName === $account->instance) {
                if (!empty($account->roles) && in_array('administrator', $account->roles)) {
                    return $account;
                }
            }
        }

        return false;
    }

    public function isPortalTutor(Request $req, $portalName)
    {
        if ($this->isPortalAdmin($req, $portalName)) {
            return 1;
        }

        if (!$user = $this->validUser($req)) {
            return null;
        }

        $accounts = isset($user->accounts) ? $user->accounts : [];
        foreach ($accounts as &$account) {
            if ($portalName === $account->instance) {
                if (!empty($account->roles) && in_array('Tutor', $account->roles)) {
                    return $account;
                }
            }
        }

        return false;
    }

    public function isAccountsAdmin(Request $req)
    {
        if (!$user = $this->validUser($req)) {
            return null;
        }

        return in_array('Admin on #Accounts', isset($user->roles) ? $user->roles : []) ? $user : false;
    }

    public function validUser(Request $req)
    {
        $payload = $req->get('jwt.payload');
        if ($payload && ('user' === $payload->object->type)) {
            $user = &$payload->object->content;
            if (!empty($user->mail)) {
                return $user;
            }
        }

        return false;
    }

    public function isOwner(Request $req, $profileId)
    {
        if (!$user = $this->validUser($req)) {
            return false;
        }

        return $user->profile_id == $profileId;
    }

    public function hasAccount(Request $req, $portalName)
    {
        if (!$user = $this->validUser($req)) {
            return false;
        }

        if ($this->isPortalTutor($req, $portalName)) {
            return true;
        }

        $accounts = isset($user->accounts) ? $user->accounts : [];
        foreach ($accounts as &$account) {
            if ($portalName === $account->instance) {
                return true;
            }
        }

        return false;
    }
}
