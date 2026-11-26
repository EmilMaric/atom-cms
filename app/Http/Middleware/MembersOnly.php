<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * With habbo.members_only on, visitors who aren't logged in only get the login
 * page: every other page sends them to it and every API call answers 401.
 *
 * It runs before route model binding, so a guest can't tell from a 404 which
 * usernames exist.
 */
class MembersOnly
{
    /**
     * Routes a logged-out visitor can still reach.
     *
     * @var list<string>
     */
    private const GUEST_ROUTES = [
        'welcome',
        'login',
        'login.store',
        'two-factor.login',
        'two-factor.login.store',
        'forgot.password.*',
        'reset.password.*',
        // The maintenance, ban and installation middleware send guests to these,
        // so blocking them would loop.
        'maintenance.show',
        'banned.show',
        'installation.*',
        // The housekeeping login form submits through Livewire.
        'default-livewire.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('habbo.members_only') || $request->user() !== null || $request->routeIs(...self::GUEST_ROUTES)) {
            return $next($request);
        }

        // Same as the auth middleware: /login forwards to the login form on the front page.
        throw new AuthenticationException(redirectTo: route('login'));
    }
}
