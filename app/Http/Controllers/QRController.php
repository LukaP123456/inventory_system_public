<?php

namespace App\Http\Controllers;

use App\Models\Archive_inventory;
use App\Models\AuthCheck;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\QR;
use App\Http\Requests\StoreQRRequest;
use App\Http\Requests\UpdateQRRequest;
use App\Models\Room;
use App\Models\Temp_inventory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;

class QRController extends Controller
{


    public function scan(Request $request)
    {
        AuthCheck::checkIfUser();
        $id = Auth::id();
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'room_id' => 'required|exists:rooms,id',
            'action' => ['required', Rule::in(['sold', 'added', 'destroyed', 'return', 'surplus', 'deficit', 'lost', 'found'])]
        ]);

        $team_sql = DB::table('team_users')
            ->join('users', 'users.id', '=', 'team_users.user_id')
            ->where('team_users.user_id', '=', $id)
            ->select('team_users.team_id')
            ->get();

        $team_sql_array = json_decode(json_encode($team_sql), true);
        $room_sql = [];

        foreach ($team_sql_array as $team_id) {
            $room_sql = DB::table('room_teams')
                ->join('rooms', 'rooms.id', '=', 'room_teams.room_id')
                ->join('teams', 'teams.id', '=', 'room_teams.team_id')
                ->where('room_teams.team_id', $team_id)
                ->where('room_teams.room_id', $data['room_id'])
                ->select(
                    'rooms.id'
                    , 'rooms.name'
                    , 'rooms.description as room_description'
                    , 'rooms.location as room_location'
                    , 'room_teams.team_id as team_id'
                    , 'teams.name as team_name'
                )
                ->get();
        }

        if ($room_sql->isEmpty()) {
            return response([
                'message' => "Invalid room id.User doesn't have access to specified room.",
            ], 500);
        }

        $room = Room::find($data['room_id']);
        $company_id = $room->company_id;
        $products = Product::where('company_id', '=', $company_id)->get();
        for ($j = 0; $j <$products->count(); $j++) {
            if ($products[$j]->id != $data['product_id']) {
                return response([
                    'message' => "Product not in users company",
                ], 500);
            }
        }

        $result = Archive_inventory::create([
            'room_id' => $data['room_id'],
            'product_id' => $data['product_id'],
            'action' => $data['action'],
            'created_at' => now()
        ]);

        if (empty($result)) {
            return response([
                'message' => "Failed to scan data",
            ], 500);
        }

        $inventory = Inventory::where([
            'product_id' => $data['product_id'],
            'room_id' => $data['room_id']
        ])->first();

        if ($inventory == null) {
            $inventory = Inventory::create([
                'product_id' => $data['product_id'],
                'room_id' => $data['room_id'],
                'quantity'=>1,
                'created_at'=>now(),
            ])->first();
        }

        $pos = ['added', 'surplus', 'found', 'return'];
        $neg = ['sold', 'destroyed', 'deficit'];

        if (in_array($data['action'], $pos)) {
            $inventory->update([
                'quantity' => $inventory->quantity + 1,
                'updated_at' => now()
            ]);
        }

        if (in_array($data['action'], $neg)) {
            $inventory->update([
                'quantity' => $inventory->quantity - 1,
                'updated_at' => now()
            ]);;
        }

        return response([
            'message' => "Product scanned successfully",
            'data' => $inventory
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param array $data
     * @return Response
     */
    public static function create(array $data): Response
    {
        //Specify directory path and ensure that the directory exists if it doesn't create directory codes
        $dir_path = public_path('codes');
        File::ensureDirectoryExists($dir_path);

        //Create string which will be stored in our QR code
        $qr_data = "Product id: " . $data['product_id'];

        //Create the QR code itself
        $qr_code = QrCode::size(300)->format('svg')->generate($qr_data);
        //Call the function for storage
        $name = 'QR_code_' . uniqid() . '.xml';
        QRController::store($data, $qr_code, $qr_data, $name);
        return response([
            'message' => 'QR code generated successfully',
            'path' => 'storage/app/public/qr_codes/' . $name
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param array $data
     * @param $qr_code
     * @param $value
     * @return void
     */
    public static function store(array $data, $qr_code, $value, $name): void
    {
        //Store the created QR code file in the file system
        Storage::disk('public')->put('qr_codes/' . $name, $qr_code);
        //Store the QR code in the database
        QR::create([
            'path' => 'http://localhost:8000/storage/app/public/qr_codes/' . $name,
            'value' => $value,
            'product_id' => $data['product_id']
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     */
    public static function destroy(int $id)
    {
        //Check if logged-in user is a boss
        AuthCheck::checkIfBoss();
        $sql = DB::table('products')
            ->join('q_r_s', 'q_r_s.product_id', '=', 'products.id')
            ->where('products.id', '=', $id)
            ->select('products.id as product_id', 'q_r_s.*')
            ->get();
        $qr = QR::find($sql->pluck('id'));
        if (empty($qr)) {
            return response([
                'message' => "Submitted QR code doesn't exist.",
                'message_number' => 0
            ], 500);
        }
        $qr_array = $qr->toArray();
        $file_name = basename($qr_array[0]['path']);
        $path = storage_path("app\\public\\qr_codes\\$file_name");
        if (!File::exists($path)) {
            return response(['message' => "File doesn't exist", 'message_number' => 0], 500);
        }
        if (!File::delete($path)) {
            return response(['message' => "Failed to delete file", 'message_number' => 0], 500);
        }
        QR::destroy($id);
        return response(['message' => "QR code destroyed successfully", 'message_number' => 1], 200);
    }
}
