<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ImageController;
use App\Mail\RegistrationMail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Otvara view sa formom gde se unosi email adresa za koju menjamo sifru
Route::get('/forgot-password', function () {
    return view('forgot-password');
})->middleware('guest')->name('password.request');
//
//Ruta koja drzi formu u koju upisujemo izmenjenu sifru i koja salje novu sifru na api
Route::get('/reset-password/{token}', function ($token) {
    return view('reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');

Route::get('/public/images/{filename}', [ImageController::class,'getImage']);
