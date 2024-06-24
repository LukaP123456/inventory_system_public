<?php

namespace App\Http\Controllers;

use App\Models\AuthCheck;
use App\Models\Company;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Team_user;
use App\Models\User;
use App\Models\User_company;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function getCompaniesRooms(int $company_id)
    {
        AuthCheck::checkIfBoss();
        return DB::table('companies')
            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
            ->where('companies.id','=',$company_id)
            ->select('rooms.id as room_id','rooms.name as room_name')
            ->get();
    }

    public function getCompaniesTeams(int $company_id)
    {
        AuthCheck::checkIfBoss();
        return DB::table('users')
            ->join('team_users','team_users.user_id','=','users.id')
            ->join('teams','teams.id','=','team_users.team_id')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('users as us','us.id','=','user_companies.user_id')
            ->join('companies','companies.id','=','user_companies.company_id')
            ->where('companies.id','=',$company_id)
            ->select('teams.id as team_id','teams.name as team_name')
            ->get();

    }

    public function getBossCompanies()
    {
        AuthCheck::checkIfBoss();

        $id = Auth::id();
        $user = User::find($id);

        return DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('users.id', $user->id)
            ->select(
                'companies.id as id'
                , 'companies.co_name as name'
            )
            ->get();
    }

    /**
     * @return Collection|Response|array
     */
    public function index(): Collection|Response|array
    {
        AuthCheck::checkIfBlocked();

        return Company::all();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Support\Collection
     */
    public function indexCompanies(): \Illuminate\Support\Collection
    {
        AuthCheck::checkIfUser();

        $id = Auth::id();

        return DB::table('users')
            ->select('companies.*')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('users.id', $id)
            ->get();
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response|string
     */
    public function addCompany(Request $request): Response|string|Application|ResponseFactory
    {
        AuthCheck::checkIfUser();

        $user_id = Auth::id();
        $company_id = $request->input('company_id');

        $user_company = DB::table('user_companies')
            ->select('*')
            ->where([
                ['user_id', '=', $user_id],
                ['company_id', '=', $company_id],
            ])
            ->get();

        if (count($user_company) != 0) {
            return response("Already joined that company", 500);
        }

        $sql = DB::table('user_companies')
            ->insert(['user_id' => $user_id, 'company_id' => $company_id, 'created_at' => now()]);

        if ($sql) {
            return response("Joined a new company successfully", 201);
        }
        return response("Failed to join a new company", 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy($company_id): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfUser();

        $id = Auth::id();
        $sql = DB::table('user_companies')
            ->where('company_id', $company_id)
            ->where('user_id', $id)
            ->delete();

        if ($sql === 1) {
            return response("Successfully deleted a company", 201);
        } else {
            return response("Failed to delete the company", 500);
        }
    }
}
