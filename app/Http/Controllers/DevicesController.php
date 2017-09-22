<?php

namespace App\Http\Controllers;

use App\Device;
use App\DeviceApproval;
use Illuminate\Http\Request;

class DevicesController extends Controller
{
    /**
     * Method for the devices list page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function list(Request $request)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $devices = $request->user()->currentTeam->devices;
        return view('devices.list', ['devices' => $devices]);
    }

    /**
     * Method for the 'Add a device' page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function add()
    {
        // If we have any waiting device approval, we recycle them and reset their timeout
        return view('devices.add');
    }

    /**
     * Destination of the 'add device' form. Creates a new Device and redirects to the code page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'initial_register_number' => 'required|numeric|min:0',
        ]);

        $device = new Device($request->input());
        $device->team()->associate($request->user()->currentTeam);
        $device->save();

        return redirect(route('devices.device.code', ['device' => $device]));
    }

    /**
     * Method for the device's code page. First make sure to logout the device and delete any approvals.
     * Then creates a new approval and shows it.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function code(Request $request, Device $device)
    {
        $device->logout();
        $device->clearApprovals();
        $passcode = DeviceApproval::generatePasscode();
        $device->createApproval($passcode);

        return view('devices.code', ['passcode' => $passcode]);
    }

    /**
     * Revoke method. Logouts the device and redirects to the devices list page.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function revoke(Request $request, Device $device)
    {
        $device->logout();
        return redirect(route('devices.list'));
    }
}
