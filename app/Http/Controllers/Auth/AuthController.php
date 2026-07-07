<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Auth\User;
use App\Services\Auth\AuthServices;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthServices $authService)
    {
        $this->authService = $authService;
    }

    public function index()
    {
        if (Auth::check()) {
            return redirect()->route($this->dashboardRoute(Auth::user()));
        }

        return view('auth.login');
    }

    public function authenticate(LoginRequest $request)
    {
        $user = $this->authService->authenticate($request);

        return redirect()
            ->intended(route($this->dashboardRoute($user), absolute: false))
            ->with('success', 'You have successfully logged in!');
    }

    public function logout()
    {
        $this->authService->logout();

        return redirect()
            ->route('login')
            ->with('success', 'You have successfully logged out!');
    }

    private function dashboardRoute(User $user): string
    {
        $role = $user->getRoleNames()->first();

        if ($role && Route::has("{$role}.dashboard")) {
            return "{$role}.dashboard";
        }

        return 'dashboard';
    }
}
