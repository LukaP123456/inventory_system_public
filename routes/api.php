<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use \App\Http\Controllers\ArchiveInventoryController;
use \App\Http\Controllers\QRController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/test', fn() => phpinfo());


//Ova ruta salje reset link na mejl ali kao odgovor vraca 404 iako se uspesno posalje email sa reset linkom zasto?
Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetLink'])->middleware('guest')->name('password.email');

//Ruta koja izvrsi promenu same sifre, pristupa joj se kroz email, ne menja password kada se unese novi password
Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->middleware('guest')->name('password.update');

///PUBLIC ROUTES
//User register
Route::post('/register', [AuthController::class, 'register']);
//User login
Route::post('/login', [AuthController::class, 'login']);
//Verify user after email link click
Route::get('/emailReg/{id}', [AuthController::class, 'setActiveUser']);


// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    ///WORKER
    //Gets the logged-in user data
    Route::get('/worker', [UserController::class, 'index']);
    //Returns the users role
    Route::get('/role', [UserController::class, 'getRole']);
    //Update logged in user data
    Route::put('/worker', [UserController::class, 'update']);
    //Change password as a logged-in user
    Route::put('/updatePassword', [UserController::class, 'update']);
    //Add profile picture for user and update profile picture for user
    Route::post('/createImage', [ImageController::class, 'addUserImage']);
    //Delete user's profile picture
    Route::put('/deleteImage', [ImageController::class, 'deleteUserImage']);
    //Get users role
    Route::get('/getUsersRole',[UserController::class,'getUsersRole']);

    ///COMPANIES
    //Gets all companies in DB
    Route::get('/companies', [CompanyController::class, 'index']);
    //Add and remove a company from the logged-in user
    Route::delete('/removeCompany/{company_id}', [CompanyController::class, 'destroy']);
    Route::post('/addCompany', [CompanyController::class, 'addCompany']);
    //All the logged-in users companies
    Route::get('/currentUser/companies', [CompanyController::class, 'indexCompanies']);

    ///ROOMS
    //Get all the rooms of a users company
    Route::get('/userRoom', [RoomController::class, 'getRoom']);
//    Route::get('/getUsersRooms',[RoomController::class,'getUsersRooms']);

    //Add data to inventory table
    Route::post('/addToInventory', [InventoryController::class, 'addToInventory']);

    //INVENTORIES
    //Gets the inventory made by the logged-in user
    Route::get('/inventory', [InventoryController::class, 'index']);

    ///LISTINGS
    //Get all the logged-in users listings
    Route::get('/getListings', [ListingController::class, 'getListings']);

    //MESSAGES
    //List out all the users messages
    Route::get('/getMessages',[MessagesController::class,'index']);
    //Get number of delivered messages
    Route::get('/getNoDeliveredMessages',[MessagesController::class,'getNoDeliveredMessages']);
    //Change status of message to seen
    Route::put('/updateMessageStatus',[MessagesController::class,'update']);

    ///SCAN QR CODE
    //Manipulate a QR code
    Route::post('/scan', [QRController::class, 'scan']);
    //Add to temp_inventories
    Route::post('/scanTempInventories', [ListingController::class, 'scanTempInventories']);
    //Finish listing for user
    Route::put('/changeListingStatus', [ListingController::class, 'changeListingStatus']);
    //get all the products which the user scanned in temp_inventories based on listing_id and room_id
    Route::post('/getScannedProducts', [ListingController::class,'getScannedProducts']);
    //Update quantity of a scanned product
    Route::put('/updateScannedProduct/{id}',[ListingController::class,'updateScannedProduct']);
    //Delete scanned product
    Route::post('/deleteScannedProduct/{id}',[ListingController::class,'deleteScannedProduct']);
    //Na osnovu room id i listing id pronaci koji su sve useri kliknuli finished za njihov listing. taj podatak vratiti na front
    Route::post('/getListingStatuses',[ListingController::class,'getListingStatuses']);
    ///PRODUCTS
    Route::prefix('products')->group(function () {
        //Create a product
        Route::post('/createProduct', [ProductController::class, 'store']);
        //Update product
        Route::put('/update', [ProductController::class, 'update']);
        //Destroy product
        Route::post('/delete', [ProductController::class, 'destroy']);
        //Show all products
        Route::get('/get', [ProductController::class, 'index']);
        //Show product based on id
        Route::post('/getIndividualProduct', [ProductController::class, 'show']);
        //Search bar for product
        Route::get('/search/{name}', [ProductController::class, 'search']);

        //Add product to room
        Route::post('/addProductToRoom', [ArchiveInventoryController::class, 'store']);
        //Update product to room
        Route::put('/updateProductToRoom/{id}', [ArchiveInventoryController::class, 'update']);
        //Destroy product added to a room
        Route::delete('/destroyProductToRoom', [ArchiveInventoryController::class, 'destroy']);
        //Get all archive inventory
        Route::get('/show', [ArchiveInventoryController::class, 'index']);
        //Get all archive inventory for a specific room
        Route::get('/showRoomArchive/{id}', [ArchiveInventoryController::class, 'showRoomArchive']);
    });

    ////BOSS === POSLODAVAC
    Route::prefix('boss')->group(function () {
        ///LISTINGS
        Route::prefix('listings')->group(function () {
            //Start a listing
            Route::post('/store', [ListingController::class, 'store']);
            //Update a listing
            Route::put('/update/{id}', [ListingController::class, 'update']);
            //Delete a listing
            Route::post('/delete', [ListingController::class, 'destroy']);
            //Get all listings of a boss
            Route::get('/index', [ListingController::class, 'index']);
            //get room team listings
            Route::get('/getRoomTeamListings/{id}',[ListingController::class,'getRoomTeamListings']);
            //delete room team listings
            Route::delete('/deleteRoomTeamListings/{id}',[ListingController::class,'deleteRoomTeamListings']);
            //create room team listing
            Route::post('/createRoomTeamListing', [ListingController::class, 'createRoomTeamListing']);
            //Get all rooms in a listing based on listing id and quantity of products
            Route::get('/getRoomsInListing/{id}',[ListingController::class,'getRoomsInListing']);
            //Finish listing ako su svi rumovi za listing finished onda u listings tabeli stavis finished
            Route::post('/finishListings4Rooms',[ListingController::class,'finishListings4Rooms']);
            //Ovde samo vracas razliku ne menjas nista u bazi to ces raditi sa drugim endpointom jer razlozi. Nemoj brisati ovaj kod trebace ti posle
            //Get the difference between temp_inventories and inventories based on listing_id and room_id
            Route::post('/getDifference',[ListingController::class,'getDifference']);

            //end listing
            Route::post('/endListing',[ListingController::class,'endListing']);
        });

        Route::prefix('bossCompanies')->group(function () {
            //Get all the companies where the logged-in user is boss
            Route::get('/getBossCompanies', [CompanyController::class, 'getBossCompanies']);
            //Get rooms in a company based on company id
            Route::get('/getCompaniesRooms/{id}',[CompanyController::class,'getCompaniesRooms']);
            //Get teams in a company based on company id
            Route::get('/getCompaniesTeams/{id}',[CompanyController::class,'getCompaniesTeams']);
        });
        //ROOMS
        Route::prefix('rooms')->group(function () {
            //Get all the rooms of all the companies in which the boss works
            Route::get('/getBossRooms', [RoomController::class, 'getBossRooms']);
            //Get all the products in a room
            Route::get('/getRoomProducts/{id}', [RoomController::class, 'getRoomProducts']);
            //Create a new room/storage unit
            Route::post('/createRoom', [RoomController::class, 'createRoom']);
            //Delete a room/storage unit
            Route::delete('/deleteRoom/{id}', [RoomController::class, 'deleteRoom']);
            //Update a room/storage unit
            Route::put('/updateRoom/{id}', [RoomController::class, 'updateRoom']);

            //Get all team room listings
            Route::get('/getRoomTeamListings', [TeamController::class, 'getRoomTeamListings']);
            //Add a team to a room_team_listings
            Route::post('/createRoomTeamListing', [TeamController::class, 'createRoomTeamListing']);
            //Remove team from room_team_listings
            Route::delete('/removeRoomTeamListing/{id}', [TeamController::class, 'removeRoomTeamListing']);
            //Update team at room_team_listings
            Route::put('/updateRoomTeamListing/{id}', [TeamController::class, 'updateRoomTeamListing']);

            //Create room_teams connection
            Route::post('/createRoomTeams', [TeamController::class, 'createRoomTeam']);
            //Update room_teams connection
            Route::put('/updateRoomTeams', [TeamController::class, 'updateRoomTeam']);
            //Delete room_teams connection
            Route::post('/deleteRoomTeams', [TeamController::class, 'deleteRoomTeam']);
            //Get all teams in room
            Route::get('/getAllTeamsInRoom/{id}', [TeamController::class, 'getAllTeamsInRoom']);
            //Finish listing in a specific room ( listing_statuses )  salje listing_id i room_id
            Route::post('/finishListingInRoom',[ListingController::class,'finishListingInRoom']);
            //Unfinish listing in a specific room
            Route::post('/unFinishListingInRoom',[ListingController::class,'unFinishListingInRoom']);

        });
        ///TEAM
        Route::prefix('team')->group(function () {
            //Create a new team
            Route::post('/store', [TeamController::class, 'store']);
            //Update team
            Route::put('/update/{id}', [TeamController::class, 'update']);
            //Delete team
            Route::delete('/destroy/{id}', [TeamController::class, 'destroy']);

            //Create a new team user connection
            Route::post('/storeTeamUser', [TeamController::class, 'storeTeamUser']);
            //Update team user connection
            Route::put('/updateTeamUser/{id}', [TeamController::class, 'updateTeamUser']);
            //Destroy team user connection
            Route::delete('/destroyTeamUser/{id}', [TeamController::class, 'destroyTeamUser']);
            //Get all teams
            Route::get('/getTeams', [TeamController::class, 'getTeams']);
            //Get team members
            Route::get('/getMembersOfTeam/{team_id}', [TeamController::class, 'getMembersOfTeam']);
        });

        //Get users in boss's company
        Route::get('/getUsers',[UserController::class,'getUsers']);
        //Verify user as a boss
        Route::put('/acceptWorker', [UserController::class, 'acceptWorker']);
        //Show inventory archive data
        Route::get('/archive', [InventoryController::class, 'getArchive']);
        //Change inventory status
        Route::put('/updateInventory/{id}', [InventoryController::class, 'updateStatus']);
        //Approve/reject a listing
        Route::put('/statusListing/{id}', [ListingController::class, 'updateListing']);
        //Send message to user
        Route::post('/sendMessage', [MessagesController::class, 'store']);
    });

    ////ADMIN
    Route::prefix('admin')->group(function () {
        Route::prefix('company')->group(function () {
            ///COMPANY
            //Route for creating a new company
            Route::post('/createCompany', [AdminController::class, 'createCompany']);
            //Route for deleting a company
            Route::delete('/adminDeleteCompany/{id}', [AdminController::class, 'destroyCompany']);
            //Route for updating a company
            Route::post('/updateCompany/{id}', [AdminController::class, 'updateCompany']);
            //Get all the data from user_companies table
            Route::get('/getAllUsersCompanies', [AdminController::class, 'getAllUsersCompanies']);
            //Gets all the companies of a boss, shows the boss's data and the companies data
            Route::get('/getBossCompanies/{id}', [AdminController::class, 'getBossCompanies']);
        });
        ///BOSS/USER
        //Route for creating a boss
        Route::post('/registerBoss', [AdminController::class, 'registerBoss']);
        //Route for updating a boss
        Route::put('/updateBoss/{id}', [AdminController::class, 'updateBoss']);
        //Update user
        Route::put('/updateUser/{id}', [AdminController::class, 'updateUser']);
        //Route for deleting a boss
        Route::delete('/deleteBoss/{id}', [AdminController::class, 'deleteBoss']);
        //Delete user
        Route::delete('/deleteUser/{id}', [AdminController::class, 'deleteBoss']);
        //Route for adding a boss to his company
        Route::post('/bossCompany', [AdminController::class, 'bossCompany']);
        //Route for removing a boss from a company
        Route::post('/removeBossCompany', [AdminController::class, 'removeBossCompany']);
        //Block user
        Route::put('/blockUser/{id}', [AdminController::class, 'blockUser']);
        //Get all users
        Route::get('/getAllUsers', [AdminController::class, 'getAllUsers']);
        //Get all products from boss's company
        Route::post('/getBossCompanyProducts', [AdminController::class, 'getBossCompanyProducts']);
    });
    //Logout user
    Route::post('/logout', [AuthController::class, 'logout']);
});
