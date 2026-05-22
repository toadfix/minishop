<?php

namespace Minishop\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $user = $request->user();
        $home = $user->hasAnyRole(['super-admin', 'admin', 'manager']) ? '/dashboard' : '/account';

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $redirect = $request->input('redirect');

        if (is_string($redirect) && str_starts_with($redirect, '/')) {
            return redirect($redirect);
        }

        return redirect()->intended($home);
    }
}
