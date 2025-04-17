<?php

namespace Doxa\Admin\Http\Middleware;

use Illuminate\Support\Facades\Auth;

class SuperUser
{
    public function handle($request, \Closure $next)
    {
        if (Auth::check() && Auth::user()->admin && Auth::user()->isActive()) {
            return $next($request);
        } else {
            abort(403);
        }
    }
}
