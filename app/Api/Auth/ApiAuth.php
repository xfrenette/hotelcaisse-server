<?php

namespace App\Api\Auth;

use App\ApiSession;
use App\Business;
use App\Device;
use App\Team;
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
     * If an ApiSession is loaded, returns its Business, else return null.
     *
     * @return Business|null
     */
    public function getBusiness()
    {
        $device = $this->getDevice();

        if (is_null($device)) {
            return null;
        }

        return $device->team->business;
    }

    /**
     * If an ApiSession is loaded, returns its Device, else return null.
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
     * If an ApiSession is loaded, returns its Device's Team, else return null.
     *
     * @return \App\Team|null
     */
    public function getTeam()
    {
        if (is_null($this->apiSession)) {
            return null;
        }

        return $this->apiSession->device->team;
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
     * Tries to find a (not expired) ApiSession with the specified $token and associated to the $team (it could work
     * with only the token, but we require the Team for (small) security reason : both information must be provided).
     *
     * If an ApiSession is found, it is loaded and we are authenticated; the method returns true.
     * If it is not found, returns false.
     *
     * Calling this method will clear the currently loaded ApiSession.
     *
     * Note that this method does not check if the Team has access to the API (see Team->canAccessApi), this check must
     * be done before.
     *
     * @param $token
     * @param Team $team
     *
     * @return bool
     */
    public function loadSession($token, Team $team)
    {
        $this->logout();

        $apiSessionsTN = with(new ApiSession())->getTable();
        $devicesTN = with(new Device())->getTable();

        $apiSession = ApiSession::valid()
            ->select("$apiSessionsTN.*")
            ->join($devicesTN, "$apiSessionsTN.device_id", '=', "$devicesTN.id")
            ->where("$devicesTN.team_id", $team->id)
            ->where("$apiSessionsTN.token", $token)
            ->first();

        if (!is_null($apiSession)) {
            $this->setApiSession($apiSession);
            return true;
        }

        return false;
    }

    /**
     * Sets the API session, thus logging the Device associated.
     *
     * @param \App\ApiSession $apiSession
     */
    public function setApiSession(ApiSession $apiSession)
    {
        $this->apiSession = $apiSession;
    }

    /**
     * @return \App\ApiSession
     */
    public function getApiSession()
    {
        return $this->apiSession;
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
     * Updates the `expires_at` attribute of the current session to push it further in the future.
     */
    public function touchSession()
    {
        if ($this->apiSession) {
            $newExpirationDate = $this->generateSessionExpirationDate();
            $this->apiSession->expires_at = $newExpirationDate;
            $this->apiSession->save();
        }
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

        $device = $this->getDevice();

        $this->destroySession();

        $newApiSession = $this->makeApiSession($device);
        $newApiSession->save();

        $this->apiSession = $newApiSession;
    }

    /**
     * Makes a new ApiSession for the specified $device. The new ApiSession is returned, but not saved in the DB.
     *
     * @param \App\Device $device
     *
     * @return \App\ApiSession
     */
    public function makeApiSession(Device $device)
    {
        $newToken = str_random(array_get($this->config, 'token.bytesLength', 32));
        $expires_at = $this->generateSessionExpirationDate();

        $apiSession = new ApiSession();
        $apiSession->token = $newToken;
        $apiSession->device()->associate($device);
        $apiSession->expires_at = $expires_at;

        return $apiSession;
    }

    /**
     * Returns a Carbon date for the expiration of a session created/updated now.
     *
     * @return Carbon
     */
    protected function generateSessionExpirationDate()
    {
        return Carbon::now()->addDays(array_get($this->config, 'token.daysValid', 30));
    }

    /**
     * Makes a new ApiSession for the $device (see makeApiSession) and then saves it in the DB. Also deletes any
     * ApiSession that currently exist for the specified $device. Note that it will not logout the current ApiSession if
     * it was deleted, it is the job of the developer to ensure it does not happen.
     *
     * @param \App\Device $device
     * @return ApiSession
     */
    public function createApiSession(Device $device)
    {
        $apiSession = $this->makeApiSession($device);

        // We delete any existing ApiSession with the same device
        ApiSession::where(['device_id' => $device->id])
            ->delete();

        $apiSession->save();

        return $apiSession;
    }

    /**
     * Finds in the DB the still valid DeviceApproval with the $passcode and $team. Returns it if found, else
     * returns null. Note that this method does not check if the $team has access to the Api.
     *
     * @param string $passcode
     * @param \App\Team $team
     * @return \App\DeviceApproval|null
     */
    public function findDeviceApproval($passcode, Team $team)
    {
        $candidates = $team->deviceApprovals()->valid()->get();

        // Find the one with which the passcode matches
        return $candidates->first(function ($deviceApproval) use ($passcode) {
            return $deviceApproval->check($passcode);
        });
    }

    /**
     * If a DeviceApproval with the specified $passcode and $team exists, create an ApiSession from it and set it in
     * ApiAuth. The DeviceApproval is destroyed. Returns true if the DeviceApproval exists, else return false (wrong
     * $passcode and/or $team). Note that this method does not check if the $team has access to the API.
     *
     * @param $passcode
     * @param \App\Team $team
     * @return bool
     */
    public function attemptRegister($passcode, Team $team)
    {
        $deviceApproval = $this->findDeviceApproval($passcode, $team);

        if (is_null($deviceApproval)) {
            return false;
        }

        $apiSession = $this->createApiSession($deviceApproval->device);
        $this->apiSession = $apiSession;

        $deviceApproval->delete();

        return true;
    }
}
