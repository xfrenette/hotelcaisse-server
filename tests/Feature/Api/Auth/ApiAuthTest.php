<?php

namespace Feature\Api\Auth;

use App\Api\Auth\ApiAuth;
use App\ApiSession;
use App\Business;
use App\Device;
use App\DeviceApproval;
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
     * @var Business
     */
    protected $business;
    /**
     * @var ApiSession
     */
    protected $apiSession;

    protected function setUp()
    {
        parent::setUp();
        $this->apiAuth = new ApiAuth();
        $this->apiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->create();
        $this->business = $this->apiSession->device->business;
    }

    protected function loadValidSession()
    {
        return $this->apiAuth->loadSession($this->apiSession->token, $this->business);
    }

    protected function createDeviceApproval($passcode, $business = null)
    {
        $deviceApproval = factory(DeviceApproval::class, 'withDeviceAndBusiness')->make();
        if ($business) {
            $deviceApproval->device->business = $business;
            $deviceApproval->device->save();
        }
        $deviceApproval->passcode = $passcode;
        $deviceApproval->save();

        return $deviceApproval;
    }

    public function testLoadSessionReturnsFalseIfInvalidToken()
    {
        $this->assertFalse($this->apiAuth->loadSession('false-token', $this->business));
    }

    public function testLoadSessionReturnsFalseIfValidTokenButInvalidBusiness()
    {
        $newBusiness = factory(Business::class)->create();
        $this->assertFalse($this->apiAuth->loadSession($this->apiSession->token, $newBusiness));
    }

    public function testLoadSessionReturnsFalseIfExpiredSession()
    {
        $this->apiSession->expire();
        $this->apiSession->save();
        $this->assertFalse($this->apiAuth->loadSession($this->apiSession->token, $this->business));
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
        $this->loadValidSession();
        $this->apiAuth->loadSession('invalid-token', $this->business);
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
        $this->assertEquals($this->business->id, $this->apiAuth->getBusiness()->id);

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
        $this->apiAuth->destroySession();
        $this->assertFalse($this->apiAuth->loadSession($oldToken, $this->business));
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
        $this->apiAuth->logout();
        $this->assertTrue($this->apiAuth->loadSession($newToken, $this->business));
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
        $this->apiAuth->regenerateToken();
        $this->assertFalse($this->apiAuth->loadSession($oldToken, $this->business));
    }

    public function testCreateApiSessionReturnsApiSession()
    {
        $device = factory(Device::class, 'withBusiness')->create();
        $apiSession = $this->apiAuth->createApiSession($device);
        $this->assertNotNull(ApiSession::find($apiSession->id));
    }

    public function testCreateApiSessionDeletesAnyConflictingApiSession()
    {
        $existingApiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->create();
        $this->apiAuth->createApiSession($existingApiSession->device);
        $this->assertNull(ApiSession::find($existingApiSession->id));
    }

    public function testFindDeviceApprovalReturnsNullWithWrongCredentials()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);
        $business = $deviceApproval->device->business;
        $otherBusiness = factory(Business::class)->create();

        $this->assertNull($this->apiAuth->findDeviceApproval($passcode . '0', $business));
        $this->assertNull($this->apiAuth->findDeviceApproval($passcode, $otherBusiness));
        $this->assertNull($this->apiAuth->findDeviceApproval($passcode . '0', $otherBusiness));
    }

    public function testFindDeviceApprovalReturnsNullWithExpiredDeviceApproval()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);
        $business = $deviceApproval->device->business;
        $deviceApproval->expire();
        $deviceApproval->save();

        $this->assertNull($this->apiAuth->findDeviceApproval($passcode, $business));
    }

    public function testFindDeviceApprovalReturnsDeviceApprovalWithGoodCredentials()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);
        $business = $deviceApproval->device->business;

        $res = $this->apiAuth->findDeviceApproval($passcode, $business);
        $this->assertInstanceOf(DeviceApproval::class, $res);
        $this->assertEquals($deviceApproval->id, $res->id);
    }

    public function testAttemptRegisterReturnsFalseWithWrongCredentials()
    {
        $deviceApproval = $this->createDeviceApproval('4567');
        $this->assertFalse($this->apiAuth->attemptRegister('8901', $deviceApproval->device->business));
    }

    public function testAttemptRegisterReturnsTrueWithValidCredentials()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);
        $this->assertTrue($this->apiAuth->attemptRegister($passcode, $deviceApproval->device->business));
    }

    public function testAttemptRegisterSetsApiSessionInApiAuth()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);

        $this->apiAuth->logout();
        $this->apiAuth->attemptRegister($passcode, $deviceApproval->device->business);

        $this->assertTrue($this->apiAuth->check());
        $this->assertEquals($deviceApproval->device->id, $this->apiAuth->getDevice()->id);
    }

    public function testAttemptRegisterDeletesDeviceApproval()
    {
        $passcode = '4567';
        $deviceApproval = $this->createDeviceApproval($passcode);

        $this->apiAuth->attemptRegister($passcode, $deviceApproval->device->business);

        $this->assertNull($this->apiAuth->findDeviceApproval($passcode, $deviceApproval->device->business));
    }
}
