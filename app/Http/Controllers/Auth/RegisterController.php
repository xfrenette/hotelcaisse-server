<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

class RegisterController extends Controller
{
    public function register(Request $request) {
    	throw new UnauthorizedException();
    }

    public function showRegistrationForm() {
	    throw new UnauthorizedException();
    }
}
