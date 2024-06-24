<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuthCheck;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\File;

class ImageController extends Controller
{
    public function getImage($filename)
    {
        $path = public_path() . '/images/' . $filename;

        if (!File::exists($path)) {
            return response(['message' => 'Image not found.'], 404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        return response($file, 200)->header("Content-Type", $type);
    }

    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function addUserImage(Request $request): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfUser();

        //Check if file has been sent
        if (!$request->hasFile('photo')) {
            return response([
                'message' => "No image has been uploaded",
            ], 500);
        }
        //Check if images directory exists if not create one
        $dir_path = public_path('images');
        File::ensureDirectoryExists($dir_path);
        if (!$request->file('photo')->isValid()) {
            return response([
                'message' => "Image upload failed",
            ], 500);
        }
        //Rules that limit size of profile picture
        $rules = [
            'photo' => 'required|image|max:10000'
        ];
        //Execute rules with validator
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response([
                'message' => "Please upload an image file (png,jpg,gif) smaller than 5Mb",
            ], 500);
        }
        //Find user based on auth token
        $user_id = Auth::id();
        $user = User::find($user_id);
        //Create path info about uploaded picture
        $randId = mt_rand(0,9999999999999999);
        $request->file('photo')->move(public_path('images'), $randId.$request->file('photo')->getClientOriginalName());
        $new_data = [
            'img_name' => 'http://localhost:8000/public/images/' . $randId . $request->file('photo')->getClientOriginalName()
        ];
        //If user is updated send response message
        if ($user->update($new_data)) {
            $user->refresh();
            return response([
                'message' => "Profile picture changed to: " . $request->file('photo')->getClientOriginalName(),
            ], 201);
        }
        return response([
            'message' => "Failed to change user's picture",
        ], 500);
    }

    /**
     * @return Response|Application|ResponseFactory
     */
    public function deleteUserImage()
    {
        AuthCheck::checkIfUser();

        //Find the user based on auth token
        $id = Auth::id();
        $user = User::find($id);
        //Check if user has a profile pic
        if ($user->img_name == null) {
            return response(['message' => "There is no profile picture to delete.",], 500);
        }
        //Get path info about user's profile pic in file structure
        $img_info = pathinfo($user->img_name);
        $path = public_path("images\\" . $img_info['basename']);
        //If file exists in the system delete it
        if (!File::exists($path)) {
            return response(['message' => "File doesn't exist",], 500);
        }
        //Check if file was deleted
        if (!File::delete($path)) {
            return response(['message' => "Failed to delete file",], 500);
        }
        //Update user data to delete name of file in DB
        $new_data = ['img_name' => null];
        if (!$user->update($new_data)) {
            return response(['message' => "Failed to update user's profile picture",], 500);
        }
        $user->refresh();
        return response(['message' => "Profile picture deleted successfully.",], 201);
    }


}
