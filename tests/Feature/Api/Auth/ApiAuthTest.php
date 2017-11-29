<?php

namespace Feature\Api\Auth;

use App\Api\Auth\ApiAuth;
use App\ApiSession;
use App\Device;
use App\DeviceApproval;
use App\Team;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var ApiAuth
     */
    protected $apiAuth;
    /**
     * @var ApiSession
     */
    protected $apiSession;

    protected function setUp()
    {
        parent::setUp();
        $this->apiAuth = new ApiAuth();

        $team = factory(Team::class, 'withBusiness')->create();

        $device = factory(Device::class)->make();
        $device->team()->associate($team);
        $device->save();

        $apiSession = factory(ApiSession::class)->make();
        $apiSession->device()->associate($device);
        $apiSession->save();

        $this->apiSession = $apiSession;
    }

    protected function loadValidSession()
    {
        $token = $this->apiSession->token;
        $team = $this->apiSession->device->team;
        return $this->apiAuth->loadSession($token, $team);
    }

    protected function createDeviceApproval($passcode, $team = null)
    {
        if (is_null($team)) {
            $team = factory(Team::class, 'withBusiness')->create();
        }

        $device = factory(Device::class)->make();
        $device->team()->associate($team);
        $device->save();

        $deviceApproval = factory(DeviceApproval::class)->make();
        $deviceApproval->device()->associate($device);
        $deviceApproval->passcode = $passcode;
        $deviceApproval->save();

        return $deviceApproval;
    }

    public function testLoadSessionReturnsFalseIfInvalidToken()
    {
        $token = 'false-token';
        $team = $this->apiSession->device->team;

        $this->assertFalse($this->apiAuth->loadSession($token, $team));
    }

    public function testLoadSessionReturnsFalseIfValidTokenButInvalidTeam()
    {
        $token = $this->apiSession->token;
        $newTeam = factory(Team::class, 'withBusiness')->create();

        $this->assertFalse($this->apiAuth->loadSession($token, $newTeam));
    }

    public function testLoadSessionReturnsFalseIfExpiredSession()
    {
        $this->apiSession->expire();
        $this->apiSession->save();

        $token = $this->apiSession->token;
        $team = $this->apiSession->device->team;

        $this->assertFalse($this->apiAuth->loadSession($token, $team));
    }

    public function testLoadSessionReturnsTrueIfValid()
    {
        $this->assertTrue($this->loadValidSession());
    }

    public function testLoadSessionLoadsApiSessionIfValid()
    {
        $this->loadValidSession();
        $this->assertEquals($this->apiSession->id, $this->apiAuth->apiSession->id);
    }

    public function testLoadSessionClearsCurrentSession()
    {
        $token = 'invalid-token';
        $team = $this->apiSession->device->team;
        $this->loadValidSession();

        $this->apiAuth->loadSession($token, $team);
        $this->assertFalse($this->apiAuth->check());
    }

    public function testCheck()
    {
        $this->loadValidSession();
        $this->assertTrue($this->apiAuth->check());

        $this->apiAuth->logout();
        $this->assertFalse($this->apiAuth->check());
    }

    public function testGetBusiness()
    {
        $this->loadValidSession();
        $business = $this->apiSession->device->team->business;

        $this->assertEquals($business->id, $this->apiAuth->getBusiness()->id);

        $this->apiAuth->logout();
        $this->assertNull($this->apiAuth->getBusiness());
    }

    public function testGetDevice()
    {
        $this->loadValidSession();
        $device = $this->apiSession->device;
        $this->assertEquals($device->id, $this->apiAuth->getDevice()->id);

        $this->apiAuth->logout();
        $this->assertNull($this->apiAuth->getDevice());
    }

    public function testGetTeam()
    {
        $this->loadValidSession();
        $device = $this->apiSession->device;
        $this->assertEquals($device->team->id, $this->apiAuth->getTeam()->id);

        $this->apiAuth->logout();
        $this->assertNull($this->apiAuth->getTeam());
    }

    public function testGetToken()
    {
        $this->loadValidSession();
        $this->assertEquals($this->apiSession->token, $this->apiAuth->getToken());

        $this->apiAuth->logout();
        $this->assertNull($this->apiAuth->getToken());
    }

    public function testDestroyRemovesFromDB()
    {
        $this->loadValidSession();
        $oldToken = $this->apiAuth->getToken();
        $this->apiAuth->destroySession();
        $count = ApiSession::where('token', $oldToken)->count();
        $this->assertEquals(0, $count);
    }

    public function testDestroySessionInvalidatesSession()
    {
        $this->loadValidSession();
        $oldToken = $this->apiAuth->getToken();
        $team = $this->apiSession->device->team;
        $this->apiAuth->destroySession();

        $this->assertFalse($this->apiAuth->loadSession($oldToken, $team));
    }

    public function testDestroySessionLogsOut()
    {
        $this->loadValidSession();
        $this->apiAuth->destroySession();
        $this->assertFalse($this->apiAuth->check());
    }

    public function testRegenerateTokenDoesNothingIfNotAlreadyAuthenticated()
    {
        $this->apiAuth->regenerateToken();
        $this->assertFalse($this->apiAuth->check());
    }

    public function testRegenerateTokenStaysAuthenticated()
    {
        $this->loadValidSession();
        $this->apiAuth->regenerateToken();
        $this->assertTrue($this->apiAuth->check());
    }

    public function testRegenerateTokenIsValidToken()
    {
        $this->loadValidSession();
        $this->apiAuth->regenerateToken();
        $newToken = $this->apiAuth->getToken();
        $team = $this->apiSession->device->team;
        $this->apiAuth->logout();
        $this->assertTrue($this->apiAuth->loadSession($newToken, $team));
    }

    public function testRegenerateTokenMakesNewToken()
    {
        $this->loadValidSession();
        $oldToken = $this->apiAuth->getToken();
        $this->apiAuth->regenerateToken();
        $this->assertNotEquals($oldToken, $this->apiAuth->getToken());
    }

    public function testRegenerateTokenInvalidatesOldToken()
    {
        $this->loadValidSession();
        $oldToken = $this->apiAuth->getToken();
        $team = $this->apiSession->device->team;
        $this->apiAuth->regenerateToken();
        $this->assertFalse($this->apiAuth->loadSession($oldToken, $team));
    }

    public function testCreateApiSessionReturnsApiSession()
    {
        $device = factory(Device::class, 'withTeam')->create();
        $apiSession = $this->apiAuth->createApiSession($device);
        $this->assertNotNull(ApiSession::find($apiSession->id));
    }

    public function testCreateApiSessionDeletesAnyConflictingApiSession()
    {
        $existingApiSession = factory(ApiSession::class, 'withDevice')->create();
        $this->apiAuth->createApiSession($existingApiSession->device);
        $this->assertNull(ApiSession::find($existingApiSession->id));
    }

    public function testFindDeviceApprovalReturnsNullWithWrongCredentials()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);
        $team = $deviceApproval->device->team;
        $otherTeam = factory(Team::class, 'withBusiness')->create();

        $this->assertNull($this->apiAuth->findDeviceApproval($passcode . '0', $team));
        $this->assertNull($this->apiAuth->findDeviceApproval($passcode, $otherTeam));
        $this->assertNull($this->apiAuth->findDeviceApproval($passcode . '0', $otherTeam));
    }

    public function testFindDeviceApprovalReturnsNullWithExpiredDeviceApproval()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);
        $team = $deviceApproval->device->team;
        $deviceApproval->expire();
        $deviceApproval->save();

        $this->assertNull($this->apiAuth->findDeviceApproval($passcode, $team));
    }

    public function testFindDeviceApprovalReturnsDeviceApprovalWithGoodCredentials()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);
        $team = $deviceApproval->device->team;

        $res = $this->apiAuth->findDeviceApproval($passcode, $team);
        $this->assertInstanceOf(DeviceApproval::class, $res);
        $this->assertEquals($deviceApproval->id, $res->id);
    }

    public function testAttemptRegisterReturnsFalseWithWrongCredentials()
    {
        $deviceApproval = $this->createDeviceApproval('4567');
        $this->assertFalse($this->apiAuth->attemptRegister('8901', $deviceApproval->device->team));
    }

    public function testAttemptRegisterReturnsTrueWithValidCredentials()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);
        $this->assertTrue($this->apiAuth->attemptRegister($passcode, $deviceApproval->device->team));
    }

    public function testAttemptRegisterSetsApiSessionInApiAuth()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);

        $this->apiAuth->logout();
        $this->apiAuth->attemptRegister($passcode, $deviceApproval->device->team);

        $this->assertTrue($this->apiAuth->check());
        $this->assertEquals($deviceApproval->device->id, $this->apiAuth->getDevice()->id);
    }

    public function testAttemptRegisterDeletesDeviceApproval()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);

        $this->apiAuth->attemptRegister($passcode, $deviceApproval->device->team);

        $this->assertNull($this->apiAuth->findDeviceApproval($passcode, $deviceApproval->device->team));
    }

    public function testTouchSession()
    {
        $this->apiSession->expires_at = Carbon::now();
        $this->apiSession->save();
        $this->apiAuth->setApiSession($this->apiSession);
        $oldExpiration = $this->apiSession->expires_at; // May change a little because of type casting
        $this->apiAuth->touchSession();

        $this->assertNotEquals($oldExpiration, $this->apiAuth->getApiSession()->expires_at);
    }
}
