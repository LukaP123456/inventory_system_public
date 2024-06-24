<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use App\Models\AuthCheck;
use App\Models\Company;
use App\Models\User;
use App\Models\User_company;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


class AdminController extends Controller
{
    /**
     * @param Request $request
     * @return Response|string|Application|ResponseFactory
     */
    public function registerBoss(Request $request): Response|string|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

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
            'role' => 'boss',
            'verified' => 0,
            'accepted' => true,
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

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'token' => $token,
            'message' => "User registered successfully",
            'data' => $user
        ];

        return response($response, 201);
    }

    /**
     * @param Request $request
     * @return JsonResponse|string
     */
    public function updateBoss(Request $request, $id): JsonResponse|string
    {
       AuthCheck::checkIfAdmin();

        $user = User::find($id);
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
     * @param Request $request
     * @return JsonResponse|string
     */
    public function updateUser(Request $request, $id): JsonResponse|string
    {
        AuthCheck::checkIfAdmin();

        $user = User::find($id);
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

    public function deleteBoss($id): int|string
    {
       AuthCheck::checkIfAdmin();

        if (!User::destroy($id)) {
            $response = [
                'message' => "Failed to user room",
            ];
            return response($response, 500);
        }
        $response = [
            'message' => "User deleted successfully",
        ];
        return response($response, 201);

    }

    public function deleteUser($id): int|string
    {
        AuthCheck::checkIfAdmin();

        if (!User::destroy($id)) {
            $response = [
                'message' => "Failed to user room",
            ];
            return response($response, 500);
        }
        $response = [
            'message' => "User deleted successfully",
        ];
        return response($response, 201);

    }


    /**
     * @param Request $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function updateCompany(Request $request, $id): Response|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

        $room = Company::find($id);

        if (!$room->update($request->all())) {
            $response = [
                'message' => "Failed to update company",
            ];
            return response($response, 500);
        }

        $room->refresh();
        $response = [
            'message' => "Company updated successfully",
        ];
        return response($response, 201);
    }

    /**
     * @param $id
     * @return Application|ResponseFactory|Response|string
     */
    public function destroyCompany($id): Response|string|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

        if (!Company::destroy($id)) {
            return response([
                'message' => "Failed to delete company",
            ], 201);

        }

        return response([
            'message' => "Company deleted successfully",
        ], 201);

    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response|string
     */
    public function createCompany(Request $request): Response|string|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

        $fields = $request->validate([
            'co_name' => 'required|string|unique:companies,co_name',
            'description' => 'string'
        ]);

        $company = Company::create([
            'co_name' => $fields['co_name'],
            'description' => $fields['description'],
            'created_at' => now()
        ]);

        if (empty($company)) {
            $response = [
                'message' => "Failed to create company",
            ];
            return response($response, 500);
        }
        $response = [
            'message' => "Company created successfully",
            'data' => $company
        ];

        return response($response, 201);
    }

    /**
     * @param Request $request
     * @return Response|string|Application|ResponseFactory
     */
    public function bossCompany(Request $request): Response|string|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

        $fields = $request->validate([
            'company_id' => 'required',
            'user_id' => 'required'
        ]);

        $user_company = DB::table('user_companies')
            ->select('*')
            ->where([
                ['user_id', '=', $fields['user_id']],
                ['company_id', '=', $fields['company_id']],
            ])
            ->get();

        if (count($user_company) != 0) {
            return response("Already joined that company", 500);
        }

        $user_company = User_company::firstOrCreate([
            'company_id' => $fields['company_id'],
            'user_id' => $fields['user_id'],
        ]);

        if (empty($user_company)) {
            $response = [
                'message' => "Failed to add to company",
            ];
            return response($response, 500);
        }
        $response = [
            'message' => "Added user to company successfully",
            'data' => $user_company
        ];

        return response($response, 201);
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function removeBossCompany(Request $request): Response|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

        $fields = $request->validate([
            'company_id' => 'required',
            'user_id' => 'required'
        ]);

        $user_company = DB::table('user_companies')
            ->select('*')
            ->where([
                ['user_id', '=', $fields['user_id']],
                ['company_id', '=', $fields['company_id']],
            ])
            ->get();

        if (count($user_company) == 0) {
            return response("User already isn't in that company", 500);
        }

        $user_company = DB::table('user_companies')
            ->where('company_id', $fields['company_id'])
            ->where('user_id', $fields['user_id'])
            ->delete();

        if ($user_company == 0) {
            $response = [
                'message' => "Failed to remove company from user",
            ];
            return response($response, 500);
        }
        $response = [
            'message' => "Removed company from user successfully",
        ];

        return response($response, 201);
    }

    /**
     * @param $id
     * @return Application|ResponseFactory|Response
     */
    public function blockUser(int $id,Request $request)
    {
        AuthCheck::checkIfAdmin();
        $data = $request->validate([
            'blocked' => "required|in:1,0"
        ]);

        $user = User::find($id);

        if ($data['blocked'] == $user->blocked) {
            return response([
                'message' => $user->blocked ? "User is already blocked" : "User is already unblocked",
            ], 201);
        }

        $user->update(['blocked' => $data['blocked']]);
        $user->refresh();

        return response([
            'message' => $data['blocked'] ? "User has been blocked" : "User has been unblocked",
        ], 201);
    }


    /**
     * @return Response|Application|ResponseFactory
     */
    public function getAllUsersCompanies(): Response|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();


        $sql = DB::table('users')
            ->select('companies.*', 'users.*')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->get();

        $response = [
            'data' => $sql,
        ];
        return response($response, 201);
    }

    /**
     * @return array|Application|ResponseFactory|Collection|Response
     */
    public function getAllUsers(): Collection|Response|array|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

        return User::all();
    }

    /**
     * @param int $id
     * @return Response|\Illuminate\Support\Collection|Application|ResponseFactory
     */
    public function getBossCompanies(int $id): Response|\Illuminate\Support\Collection|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

        $user = User::find($id);

        if ($user->role != 'boss') {
            $response = [
                'message' => "Selected user is not a boss",
            ];
            return response($response, 500);
        }

        return DB::table('users')
            ->select('companies.*', 'users.id as userId')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('users.id', $user->id)
            ->get();

    }

    /**
     * @param Request $request
     * @return Response|\Illuminate\Support\Collection|Application|ResponseFactory
     */
    public function getBossCompanyProducts(Request $request): Response|\Illuminate\Support\Collection|Application|ResponseFactory
    {
       AuthCheck::checkIfAdmin();

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::where('id', $data['user_id'])->firstOrFail();

        $query = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->join('products', 'products.company_id', '=', 'companies.id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('users.id', '=', $user->id)
            ->where('users.role', '=', 'boss')
            ->select(
                'products.name as product_name',
                'products.description as product_description'
                ,'products.producer as product_producer'
                ,'products.created_at as product_created_at'
                ,'products.price as product_price'
                ,'companies.co_name'
            );

        return $query->get();
    }

}
