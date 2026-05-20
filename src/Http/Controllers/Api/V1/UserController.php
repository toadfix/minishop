<?php

namespace Minishop\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Minishop\Http\Resources\UserResource;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('customer');

        return response()->json(new UserResource($user));
    }
}
