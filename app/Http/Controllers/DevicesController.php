<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DevicesController extends Controller
{
    public function list()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $devices = Auth::user()->currentTeam->devices;
        return view('devices.list', ['devices' => $devices]);
    }

    public function add()
    {
        // If we have any waiting device approval, we recycle them and reset their timeout
        return view('devices.add');
    }
}
