<?php

namespace App\Models;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthCheck extends Model
{
    use HasFactory;

    /**
     * @return bool|Application|ResponseFactory|Response
     */
    public static function checkIfUser()
    {
        $user = Auth::user();
        abort_unless($user->blocked === 0, 401, "You have been blocked");
        abort_unless($user->role === 'worker', 403, "You are unauthorized. Only a user with role worker can access this data");
    }

    public static function checkIfBlocked()
    {
        $user = Auth::user();
        abort_unless($user->blocked === 0, 401, "You have been blocked");
    }


    /**
     * @return void
     */
    public static function checkIfBoss(): void
    {
//        $id = Auth::id();
//        $user = User::where('id', $id)->firstOrFail();
//        if ($user->role !== 'boss') {
//            throw new Exception("You are unauthorized. Only a user with role boss can access this data");
//        }
//        return true;
        $user = Auth::user();
        abort_unless($user->blocked === 0, 403, "You have been blocked");
        abort_unless($user->role === 'boss', 403, "You are unauthorized. Only a user with role boss can access this data");
    }

    /**
     * @return false|Application|ResponseFactory|Response
     */
    public static function checkIfAdmin()
    {
        $user = Auth::user();
        abort_unless($user->blocked === 0, 403, "You have been blocked");
        abort_unless($user->role === 'admin', 403, "You are unauthorized. Only a user with role admin can access this data");
    }

}
