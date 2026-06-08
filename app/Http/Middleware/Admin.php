<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $hasMyshortletPermission = (bool) ($user->users_permission ?? false);
        $hasAdminFlag = (bool) ($user->is_admin ?? false);

        if (! $hasMyshortletPermission && ! $hasAdminFlag) {
            abort(404);
        }

        return $next($request);
    }
}
