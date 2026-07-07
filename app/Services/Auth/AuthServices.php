<?php

namespace App\Services\Auth;

use App\Models\Auth\User;
use App\Services\Log\LogServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthServices
{
    public function __construct(protected LogServices $logServices) {}

    protected int $ipMaxAttempts = 30;

    protected int $ipDecaySeconds = 60;

    protected int $userMaxAttempts = 5;

    protected int $userDecaySeconds = 60;

    protected int $lockSeconds = 60;

    protected function ipKey(Request $request): string
    {
        return 'login:ip:'.$request->ip();
    }

    protected function userKey(string $email, Request $request): string
    {
        return 'login:user:'.Str::lower($email).'|'.$request->ip();
    }

    protected function lockKey(string $email): string
    {
        return 'login-lock:'.Str::lower($email);
    }

    protected function isLocked(string $email): bool
    {
        return Cache::has($this->lockKey($email));
    }

    protected function startLock(string $email): void
    {
        $until = now()->addSeconds($this->lockSeconds);
        Cache::put($this->lockKey($email), $until->getTimestamp(), $until);
    }

    protected function secondsUntilUnlock(string $email): int
    {
        $ts = Cache::get($this->lockKey($email));

        return $ts ? max(0, (int) $ts - now()->getTimestamp()) : 0;
    }

    protected function ensureNotRateLimited(Request $request, string $ipKey, string $userKey, ?User $user, string $email): void
    {
        if (RateLimiter::tooManyAttempts($ipKey, $this->ipMaxAttempts)) {
            $seconds = RateLimiter::availableIn($ipKey);

            $this->logServices->logError($user ?? User::class, 'login_throttled_ip', 'auth', [
                'email' => $email,
                'ip' => $request->ip(),
                'seconds_until_unlock' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts from your network. Please try again in {$seconds} seconds.",
            ]);
        }

        if (RateLimiter::tooManyAttempts($userKey, $this->userMaxAttempts)) {
            $seconds = RateLimiter::availableIn($userKey);

            $this->logServices->logError($user ?? User::class, 'login_throttled_user', 'auth', [
                'email' => $email,
                'ip' => $request->ip(),
                'seconds_until_unlock' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }
    }

    public function authenticate(Request $request): User
    {
        $data = $request->validated();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $remember = (bool) ($data['remember'] ?? false);

        $ipKey = $this->ipKey($request);
        $userKey = $this->userKey($email, $request);

        $user = User::where('email', $email)->first();

        if ($this->isLocked($email)) {
            $seconds = $this->secondsUntilUnlock($email);

            $this->logServices->logError($user ?? User::class, 'login_locked', 'auth', [
                'email' => $email,
                'ip' => $request->ip(),
                'seconds_until_unlock' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $this->ensureNotRateLimited($request, $ipKey, $userKey, $user, $email);

        if (Auth::guard('web')->attempt(['email' => $email, 'password' => $password], $remember)) {
            $authUser = Auth::guard('web')->user();

            if (! $authUser->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $this->logServices->logError($authUser, 'login_inactive', 'auth', [
                    'email' => $email,
                    'ip' => $request->ip(),
                    'reason' => 'account_inactive',
                ]);

                throw ValidationException::withMessages([
                    'email' => 'Your account is inactive. Please contact an administrator.',
                ]);
            }

            $request->session()->regenerate();

            RateLimiter::clear($ipKey);
            RateLimiter::clear($userKey);
            Cache::forget($this->lockKey($email));

            $this->logServices->logSuccess($authUser, 'login_success', 'auth', [
                'email' => $email,
                'ip' => $request->ip(),
                'remember' => $remember,
                'role' => $authUser->getRoleNames()->first(),
            ]);

            return $authUser;
        }

        RateLimiter::hit($ipKey, $this->ipDecaySeconds);
        RateLimiter::hit($userKey, $this->userDecaySeconds);

        $remainingUser = RateLimiter::retriesLeft($userKey, $this->userMaxAttempts);

        if ($remainingUser <= 0) {
            $this->startLock($email);
            RateLimiter::clear($userKey);

            $seconds = $this->secondsUntilUnlock($email);

            $this->logServices->logError($user ?? User::class, 'login_lock_triggered', 'auth', [
                'email' => $email,
                'ip' => $request->ip(),
                'max_attempts' => $this->userMaxAttempts,
                'lock_seconds' => $this->lockSeconds,
                'seconds_until_unlock' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Your account is temporarily locked. Try again in {$seconds} seconds.",
            ]);
        }

        $used = $this->userMaxAttempts - $remainingUser;
        $attemptTxt = $remainingUser === 1 ? 'attempt' : 'attempts';

        $this->logServices->logError($user ?? User::class, 'login_failed', 'auth', [
            'email' => $email,
            'ip' => $request->ip(),
            'attempt_used' => $used,
            'attempt_remaining' => $remainingUser,
            'max_attempts' => $this->userMaxAttempts,
            'decay_seconds' => $this->userDecaySeconds,
        ]);

        throw ValidationException::withMessages([
            'email' => "Incorrect email or password. Attempt {$used} of {$this->userMaxAttempts}. You have {$remainingUser} {$attemptTxt} left.",
        ]);
    }

    public function logout(): void
    {
        $user = Auth::guard('web')->user();
        $request = request();

        if ($user) {
            $this->logServices->logSuccess($user, 'logout', 'auth', [
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
