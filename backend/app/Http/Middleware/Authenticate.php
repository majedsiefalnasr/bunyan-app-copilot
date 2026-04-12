<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;

class Authenticate extends BaseAuthenticate
{
    // Minimal subclass to expose App\Http\Middleware\Authenticate for static analysis
}
