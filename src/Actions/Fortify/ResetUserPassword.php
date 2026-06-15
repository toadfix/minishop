<?php

namespace Minishop\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset($user, array $input): void
    {
        Validator::make($input, [
            'password' => ['required', 'confirmed', Password::defaults()],
        ])->validate();

        // The User model casts `password` as `hashed`, so the plain value is
        // hashed on assignment.
        $user->forceFill(['password' => $input['password']])->save();
    }
}
