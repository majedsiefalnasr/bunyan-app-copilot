<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponseTrait;
use Illuminate\Routing\Controller;

/**
 * BaseController — API Base Class
 *
 * All API controllers inherit from this base class to ensure
 * consistent response formatting via ApiResponseTrait.
 *
 * Enforces the unified error contract across all API endpoints.
 *
 * @see App\Traits\ApiResponseTrait
 * @see specs/runtime/005-error-handling/contracts/error-response.json
 */
class BaseController extends Controller
{
    use ApiResponseTrait;
}
