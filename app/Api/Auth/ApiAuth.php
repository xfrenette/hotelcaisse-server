<?php

namespace App\Api\Auth;

use App\Business;

class ApiAuth
{
    protected $apiSession;

    /**
     * Tries to find a (not expired) ApiSession with the specified $token and associated to the $business (it could
     * work only with the token, but we require the Business for (small) security reason : both information must be
     * provided).
     *
     * If an ApiSession is found, it is loaded and we are authenticated; the method returns true.
     * If it is not found, returns false.
     *
     * Calling this method will clear the currently loaded ApiSession.
     *
     * @param $token
     * @param Business $business
     *
     * @return bool
     */
    public function loadSession($token, Business $business)
    {
        return false;
    }
}
