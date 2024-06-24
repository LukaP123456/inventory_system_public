<?php

namespace App\Http\Controllers;

use App\Models\AuthCheck;
use App\Models\ListingStatus;
use App\Models\Messages;
use App\Http\Requests\StoreMessagesRequest;
use App\Http\Requests\UpdateMessagesRequest;
use App\Models\Temp_inventory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessagesController extends Controller
{
    public function getNoDeliveredMessages()
    {
        AuthCheck::checkIfUser();

        $id = Auth::id();
        return Messages::where('receiver', $id)->where('status', '=', 'delivered')->count();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $id = Auth::id();
        AuthCheck::checkIfUser();

        $messages = DB::table('messages')
            ->join('users', 'users.id', '=', 'messages.sender')
            ->where('messages.receiver', $id)
            ->select(
                'messages.text',
                'messages.id as message_id',
                'messages.receiver as receiver_id',
                'messages.status',
                'messages.created_at',
                'messages.sender as sender_id',
                'users.first_name as sender_name',
                'users.last_name as sender_last_name',
                'users.role as sender_role',
                'messages.subject',
            )->orderBy('created_at', 'desc')
            ->get();
        return \response(['data' => $messages], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    private function validateRequest($request)
    {
        return $request->validate([
            'text' => 'required|string',
            'subject' => 'required|string',
            'listing_id' => ['required_if:room_id,' . $request->has('room_id'), 'exists:listings,id'],
            'room_id' => ['required_if:listing_id,' . $request->has('listing_id'), 'exists:rooms,id'],
            'team_id' => ['required_without_all:listing_id,user_id,room_id', 'exists:teams,id'],
            'user_id' => ['required_without_all:listing_id,room_id,team_id', 'exists:users,id'],
        ]);
    }

    private function sendMessage($senderId, $user_ids, $data)
    {
        $messages = [];
        foreach ($user_ids as $user_id) {
            $messages[] = Messages::create([
                'sender' => $senderId,
                'receiver' => $user_id,
                'status' => 'delivered',
                'text' => $data['text'],
                'subject' => $data['subject'],
                'created_at' => now(),
                'updated_at' => null,
            ]);
        }
        if (empty($messages)) {
            return response([
                'message' => "Failed to send message",
            ], 500);
        }
        return response([
            'message' => "Message sent",
        ], 201);
    }

    /**
     * @param Request $request
     */
    public function store(Request $request)
    {
        AuthCheck::checkIfBoss();
        $senderId = Auth::id();
        $data = $this->validateRequest($request);

        switch (true) {
            case (isset($request->room_id) and isset($request->listing_id)):
//                $data = $request->validate([
//                    'text' => 'required|string',
//                    'listing_id' => 'required|exists:listings,id',
//                    'room_id' => 'required|exists:rooms,id',
//                ]);

                $sql = Temp_inventory::where('listing_id', $data['listing_id'])->where('room_id', $data['room_id'])->get();
                $user_ids = $sql->pluck('user_id')->unique();

                if ($sql->isEmpty() or empty($user_ids)) {
                    return \response([
                        'message' => "Users not found 1",
                    ]);
                }
                return $this->sendMessage($senderId, $user_ids, $data);
//                foreach ($user_ids as $user_id) {
//                    $message = Messages::create([
//                        'sender' => $senderId,
//                        'receiver' => $user_id,
//                        'status' => 'delivered',
//                        'text' => $data['text'],
//                        'created_at' => now(),
//                        'updated_at' => null,
//                    ]);
//                }
//                if (empty($message)) {
//                    return response([
//                        'message' => "Failed to send message 1",
//                    ], 500);
//                }
//                return response([
//                    'message' => "Message sent 1",
//                ], 201);
                break;
            case (!isset($request->room_id) and isset($request->listing_id)):
//                $data = $request->validate([
//                    'text' => 'required|string',
//                    'listing_id' => 'required|exists:listings,id',
//                ]);

                $sql = Temp_inventory::where('listing_id', $data['listing_id'])->get();
                $user_ids = $sql->pluck('user_id')->unique();

                if ($sql->isEmpty() or empty($user_ids)) {
                    return \response([
                        'message' => "Users not found 2",
                    ]);
                }
                return $this->sendMessage($senderId, $user_ids, $data);
//                foreach ($user_ids as $user_id) {
//                    $message = Messages::create([
//                        'sender' => $senderId,
//                        'receiver' => $user_id,
//                        'status' => 'delivered',
//                        'text' => $data['text'],
//                        'created_at' => now(),
//                        'updated_at' => null,
//                    ]);
//                }
//                if (empty($message)) {
//                    return response([
//                        'message' => "Failed to send message 2",
//                    ], 500);
//                }
//                return response([
//                    'message' => "Message sent 2",
//                ], 201);
                break;
            case (isset($request->team_id) and !isset($request->room_id) and !isset($request->listing_id)):
//                $data = $request->validate([
//                    'text' => 'required|string',
//                    'team_id' => 'required|exists:teams,id',
//                ]);

                $sql = DB::table('room_team_listings')
                    ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
                    ->join('team_users', 'team_users.team_id', '=', 'room_team_listings.team_id')
                    ->join('users', 'users.id', '=', 'team_users.user_id')
                    ->where('room_team_listings.team_id', $data['team_id'])
                    ->select('team_users.user_id')
                    ->get();

                $user_ids = $sql->pluck('user_id')->unique();

                if ($sql->isEmpty() or empty($user_ids)) {
                    return \response([
                        'message' => "Users not found 3",
                    ]);
                }
                return $this->sendMessage($senderId, $user_ids, $data);
//                foreach ($user_ids as $user_id) {
//                    $message = Messages::create([
//                        'sender' => $senderId,
//                        'receiver' => $user_id,
//                        'status' => 'delivered',
//                        'text' => $data['text'],
//                        'created_at' => now(),
//                        'updated_at' => null,
//                    ]);
//                }
//                if (empty($message)) {
//                    return response([
//                        'message' => "Failed to send message 3",
//                    ], 500);
//                }
//                return response([
//                    'message' => "Message sent 3",
//                ], 201);
                break;
            case (isset($request->user_id) and !isset($request->team_id) and !isset($request->room_id) and !isset($request->listing_id)):
//                $data = $request->validate([
//                    'text' => 'required|string',
//                    'user_id' => 'required|exists:users,id',
//                ]);
                $message = Messages::create([
                    'sender' => $senderId,
                    'receiver' => $data['user_id'],
                    'status' => 'delivered',
                    'text' => $data['text'],
                    'subject' => $data['subject'],
                    'created_at' => now(),
                    'updated_at' => null,
                ]);

                if (empty($message)) {
                    return response([
                        'message' => "Failed to send message",
                    ], 500);
                }
                return response([
                    'message' => "Message sent",
                ], 201);
                break;
            default:
                return \response([
                    'data' => 'No id sent'
                ]);
                break;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Messages $messages
     * @return Response
     */
    public function show(Messages $messages)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Messages $messages
     * @return Response
     */
    public function edit(Messages $messages)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request)
    {
        AuthCheck::checkIfUser();

        $id = Auth::id();
        $data = $request->validate(['message_id' => 'required|exists:messages,id']);
        $messages_update = Messages::where('id', $data['message_id'])->update(['seen' => 1, 'status' => 'read']);
        if ($messages_update > 0) {
            return \response("Status changed to read", 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Messages $messages
     * @return Response
     */
    public function destroy(Messages $messages)
    {
        //
    }
}
