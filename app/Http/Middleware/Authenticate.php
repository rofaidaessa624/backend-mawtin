<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Auth\AuthenticationException;

class Authenticate extends Middleware
{
    /**
     * في APIs لا نعمل redirect
     */
    protected function redirectTo($request)
    {
        // دايمًا null عشان مفيش redirect
        return null;
    }

    /**
     * نرجع JSON بدل redirect
     */
    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            null
        );
    }
}