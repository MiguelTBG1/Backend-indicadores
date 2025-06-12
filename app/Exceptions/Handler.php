<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class Handler extends Exception
{
    //
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'message' => 'Unauthenticated.',
            'success' => false
        ], 401);
    }
}
