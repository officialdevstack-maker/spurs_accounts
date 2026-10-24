<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->suspended_at) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('oauth/userinfo')) {
            return response()->json(['message' => 'This account is suspended.'], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->withErrors([
            'email' => 'This account is suspended. Please contact support.',
        ]);
    }
}
