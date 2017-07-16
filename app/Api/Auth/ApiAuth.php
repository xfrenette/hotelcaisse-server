<?php

namespace App\Api\Auth;

use App\ApiSession;
use App\Business;

class ApiAuth
{
    /**
     * Loaded ApiSession if successfully loaded, else null.
     * @var ApiSession
     */
    public $apiSession = null;

    /**
     * If an ApiSession is loaded, returns it Business, else return null.
     *
     * @return Business|null
     */
    public function getBusiness()
    {
        if (is_null($this->apiSession)) {
            return null;
        }

        return $this->apiSession->business;
    }

    /**
     * If an ApiSession is loaded, returns it Device, else return null.
     *
     * @return \App\Device|null
     */
    public function getDevice()
    {
        if (is_null($this->apiSession)) {
            return null;
        }

        return $this->apiSession->device;
    }

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
        $this->logout();

        $apiSession = ApiSession::valid()
            ->where('business_id', $business->id)
            ->where('token', $token)
            ->first();

        if (!is_null($apiSession)) {
            $this->apiSession = $apiSession;
            return true;
        }

        return false;
    }

    /**
     * Returns true if an ApiSession is currently loaded, else false.
     *
     * @return bool
     */
    public function check()
    {
        return !is_null($this->apiSession);
    }

    /**
     * Logout from the current ApiSession. Note that this does not destroy the ApiSession.
     */
    public function logout()
    {
        $this->apiSession = null;
    }
}
