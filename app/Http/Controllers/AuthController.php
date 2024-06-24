<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationMail;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;


class AuthController extends Controller
{
    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function register(Request $request): Response|Application|ResponseFactory
    {
        $agent = new Agent();
        $fields = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:10',             // must be at least 10 characters in length
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
                'confirmed'
            ]
        ]);

        $user = User::create([
            'first_name' => $fields['first_name'],
            'last_name' => $fields['last_name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'role' => 'worker',
            'verified' => 0,
            'accepted' => null,
            'ip_address' => $request->ip(),
            'country' => Location::get($request->ip()),
            'agent' => $request->userAgent(),
            'device' => ($agent->device()) ? $agent->device() : 'desktop',
        ]);

        if (empty($user)) {
            $response = [
                'message' => "Failed to register user",
            ];

            return response($response, 500);
        }

        $email = $user['email'];
        $name = $user['first_name'] . " " . $user['last_name'];
        $user_id = $user['id'];

        Mail::to($email)->queue(new RegistrationMail($name, $user_id));

//        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
//            'token' => $token,
            'message' => "User registered successfully",
        ];

        return response($response, 201);
    }

    /**Verifies the user when he clicks the link in the email
     * @param $user_id
     * @return Redirector|Application|RedirectResponse
     */
    public function setActiveUser($user_id): \Illuminate\Routing\Redirector|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse
    {
        DB::table('users')
            ->where('id', $user_id)
            ->update([
                'email_verified_at' => now()->toDateTimeString(),
                'verified' => 1]);

        return redirect("http://localhost:3000/");
    }

    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function login(Request $request): Response|Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        if (isset($user->blocked) AND $user->blocked == 1){
            return response([
                'message' => "You have been blocked."
            ], 401);
        }

        if (is_null($user['email_verified_at'])) {
            return response([
                'message' => "User is not verified please check your email and verify your mail account."
            ], 401);
        }

        // Check password
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => "Password doesn't match the password in DB"
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];
        return response($response, 201);
    }

    /**
     * @return string[]
     */
    public function logout(): array
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logged out'
        ];
    }
}
