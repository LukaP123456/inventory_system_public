<?php

namespace App\Http\Controllers;

use App\Models\AuthCheck;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\Listing;
use App\Models\Messages;
use App\Models\Room;
use App\Models\User;
use App\Models\User_company;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;


class UserController extends Controller
{
    public function getUsers()
    {
        $id = Auth::id();

        $boss_companies = User_company::where('user_id', $id)->get();
        $boss_companies_ids = $boss_companies->pluck('company_id');
        $users = [];
        foreach ($boss_companies_ids as $company_id) {
            $users = array_merge($users, DB::table('user_companies')
                ->join('users', 'users.id', '=', 'user_companies.user_id')
                ->join('companies', 'companies.id', '=', 'user_companies.company_id')
                ->where('user_companies.company_id', $company_id)
                ->whereNotIn('user_companies.user_id', [$id])
                ->select('users.first_name', 'users.last_name', 'users.id')
                ->get()->toArray());
        }

        return $users;
    }

    public function getUsersRole()
    {
        AuthCheck::checkIfUser();

        $user = Auth::user();
        return $user->role;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Collection
     */
    public function index(): Collection
    {
        $id = Auth::id();
        AuthCheck::checkIfBlocked();
        return DB::table('users')
            ->select('first_name', 'last_name', 'email', 'role', 'id', 'verified', 'img_name')
            ->where('id', $id)
            ->get();
    }

    /**
     * @return Collection
     */
    public function getRole(): Collection
    {
        $id = Auth::id();
        AuthCheck::checkIfBlocked();
        return DB::table('users')
            ->select('role')
            ->where('id', $id)
            ->get();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $id = Auth::id();
        $user = User::find($id);
        AuthCheck::checkIfUser();

        if ($user->update($request->all())) {
            $user->refresh();
            return response()->json([
                'message' => 'User data updated successfully!',
                'data' => $user
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to update user data',
            ], 500);
        }
    }

    /**
     * @return JsonResponse|string
     */
    public function acceptWorker(): JsonResponse|string
    {
        $id = Auth::id();
        $user = User::find($id);

        if (!AuthCheck::checkIfBoss()) {
            $response = [
                'message' => "You are unauthorized",
            ];
            return response($response, 500);
        }
        if (!$user->accepted == null) {
            return response()->json([
                'message' => 'User has been accepted already!',
                'data' => $user
            ]);
        }
        $user->update(['accepted' => true]);
        $user->refresh();
        $response = [
            'message' => "User has been updated",
        ];
        return response($response, 201);
    }
}
