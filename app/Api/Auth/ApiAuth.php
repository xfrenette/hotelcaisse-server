<?php

namespace App\Api\Auth;

use App\ApiSession;
use App\Business;
use App\Device;
use Carbon\Carbon;

class ApiAuth
{
    protected $config;

    /**
     * ApiAuth constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;
    }

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
     * If an ApiSession is loaded, returns it token, else return null.
     *
     * @return string|null
     */
    public function getToken()
    {
        if (is_null($this->apiSession)) {
            return null;
        }

        return $this->apiSession->token;
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

    /**
     * Logs out and deletes the ApiSession from the DB.
     */
    public function destroySession()
    {
        if (!$this->check()) {
            return;
        }

        $apiSession = $this->apiSession;
        $this->logout();
        $apiSession->delete();
    }

    /**
     * Invalidates the current token and regenerates a new one. Does nothing if not already logged in, else keep the
     * user logged in, but with the new token.
     */
    public function regenerateToken()
    {
        if (!$this->check()) {
            return;
        }

        $business = $this->getBusiness();
        $device = $this->getDevice();

        $this->destroySession();

        $newApiSession = $this->makeApiSession($business, $device);
        $newApiSession->save();

        $this->apiSession = $newApiSession;
    }

    /**
     * Makes a new ApiSession for the specified $business and $device. The new ApiSession is returned, but not saved in
     * the DB.
     *
     * @param \App\Business $business
     * @param \App\Device $device
     *
     * @return \App\ApiSession
     */
    protected function makeApiSession(Business $business, Device $device)
    {
        $newToken = str_random(array_get($this->config, 'token.bytesLength', 32));
        $expires_at = Carbon::now()->addDays(array_get($this->config, 'token.daysValid', 30));

        $apiSession = new ApiSession();
        $apiSession->token = $newToken;
        $apiSession->business()->associate($business);
        $apiSession->device()->associate($device);
        $apiSession->expires_at = $expires_at;

        return $apiSession;
    }
}
