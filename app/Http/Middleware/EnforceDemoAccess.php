<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceDemoAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->demo_expires_at !== null) {
            $hash = hash('sha256', $user->getAuthPassword());
            $previous = $request->session()->get('demo_password_fingerprint');
            if ($user->demoAccessExpired() || ! $user->has_access || ($previous !== null && ! hash_equals($previous, $hash))) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login');
            }
            $request->session()->put('demo_password_fingerprint', $hash);
        }

        return $next($request);
    }
}
