<?php

namespace App\Http\Controllers;

use App\Models\Archive_inventory;
use App\Models\AuthCheck;
use App\Models\Product;
use App\Models\QR;
use App\Models\Room_team_listings;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();

        $results = DB::table('products')
            ->join('companies', 'companies.id', '=', 'products.company_id')
            ->join('user_companies', 'user_companies.company_id', '=', 'companies.id')
            ->join('users', 'users.id', '=', 'user_companies.user_id')
            ->where('user_companies.user_id', '=', $id)
            ->select(
                'products.id as id'
                , 'products.name as prod_name'
                , 'companies.id as co_id'
                , 'companies.co_name as co_name'

                , 'products.producer'
                , 'products.price',
                "products.description as description"

            )
            ->get();


        return response([
            'data' => $results,
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        AuthCheck::checkIfBoss();

        $id = Auth::id();
        $data = $request->validate([
            'product_name' => 'required',
            'price' => 'required',
            'producer' => 'required',
            'company_id' => 'required|exists:companies,id',
            'description' => 'required',
        ]);

        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
            ->where('user_companies.user_id', '=', $id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'users.first_name'
                , 'users.last_name'
                , 'users.id as user_id'
                , 'rooms.id as room_id'
                , 'rooms.name as room_name'
                , 'rooms.location as room_location'
                , 'rooms.description as room_description'
            )
            ->get();


        if ($sql->isEmpty()) {
            return response([
                'message' => "You are not in the specified company or you are trying to add to the wrong room",
            ], 500);
        }
        $product = Product::create([
            'name' => $data['product_name'],
            'producer' => $data['producer'],
            'company_id' => $data['company_id'],
            'description' => $data['description'],
            'price' => $data['price'],
        ]);

        $qr = QRController::create([
            'product_id' => $product->id,
            'product_name' => $data['product_name'],
            'producer' => $data['producer'],
        ]);
        if (!empty($product) and !empty($qr)) {
            return response([
                'message' => 'Product ' . $data['product_name'] . ' created successfully!',
            ], 200);
        }

        return response([
            'message' => 'Failed to create product.',
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request)
    {
        AuthCheck::checkIfBoss();

        $id = Auth::id();
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            $id => "exists:users,id,users_companies,$id",
            'name' => 'required',
            'price' => 'required',
            'producer' => 'required',
            'description' => 'required',
        ]);

        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('user_companies.company_id', '=', $data['company_id'])
            ->where('user_companies.user_id', '=', $id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'users.first_name'
                , 'users.last_name'
                , 'users.id as user_id'
            )
            ->get();

        if ($sql->isEmpty()) {
            return response([
                'message' => "You are not in the specified company",
            ], 500);
        }

        $product = Product::find($data["product_id"]);
        $product->update([
            'name' => $data['name'],
            'producer' => $data['producer'],
            'price' => $data['price'],
            'description' => $data['description'],
            'updated_at' => now(),
        ]);
        return $product;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Application|ResponseFactory|Response|int
     */
    public function destroy(Request $request)
    {
        AuthCheck::checkIfBoss();

        $user_id = Auth::id();

        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'product_id' => "required|exists:products,id",
            $user_id => "exists:users,id,users_companies,$user_id",
        ]);

        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('user_companies.company_id', '=', $data['company_id'])
            ->where('user_companies.user_id', '=', $user_id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'users.first_name'
                , 'users.last_name'
                , 'users.id as user_id'
            )
            ->get();

        if ($sql->isEmpty()) {
            return response([
                'message' => "You are not in the specified company",
            ], 500);
        }
        $qrCode = QRController::destroy($data['product_id']);

        if ($qrCode->original['message_number'] != 1) {
            return response([
                'message' => "Failed deleting QR code",
            ], 500);
        }

        if (Product::destroy($data['product_id']) != 1) {
            return response([
                'message' => "Failed deleting product",
            ], 500);
        }
        return response([
            'message' => "Product deleted.",
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'room_id' => 'required|exists:rooms,id'
        ]);

        $get_listings = DB::table('rooms')
            ->join('companies', 'companies.id', '=', 'rooms.company_id')
            ->where('rooms.id', '=', $data['room_id'])
            ->get();

        $company_ids = $get_listings->pluck('company_id');

        foreach ($company_ids as $company_id) {
            $sql = Product::where('company_id', $company_id)->where('id',$data['product_id'])->limit(1)->get();
        }

        if ($sql->isEmpty()) {
            return response([
                'data' => "No product found in database",
            ], 200);
        }

        return response([
            'data' => $sql,
        ], 200);
    }

    /**
     * Search for a name
     *
     * @param string $name
     * @return Response
     */
    public function search(string $name)
    {
        return Product::where('name', 'like', '%' . $name . '%')->get();
    }
}
