<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

/**
 * @method validateEmail(Request $request)
 * @method broker()
 */
class ResetPasswordController extends Controller
{
    public function sendResetLink(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['email' => 'required|email|exists:users']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        //Code from the Laravel docs
        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => ($status)])
            : back()->withErrors(['email' => ($status)]);

    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users',
            'password' => [
                'required',
                'string',
                'min:10',             // must be at least 10 characters in length
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ]
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect('http://localhost:3000/login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
