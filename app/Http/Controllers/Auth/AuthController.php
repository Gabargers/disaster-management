<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthServices;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthServices $authService)
    {
        $this->authService = $authService;
    }

    public function index()
    {
        if (auth()->check()) {
            return redirect()->route(Auth::user()->getRoleNames()->first() . '.dashboard');
        }
        
        return view('auth.login');
    }

    public function authenticate(LoginRequest $request)
    {
        $user = $this->authService->authenticate($request->validated());

        return redirect()
            ->route(Auth::user()->getRoleNames()->first() . '.dashboard')
            ->with('success', 'You have successfully logged in!');
    }

    public function logout()
    {
        $this->authService->logout();

        return redirect()
            ->route('login')
            ->with('success', 'You have successfully logged out!');
    }
}
