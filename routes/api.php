<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\vendor\Chatify\Api\MessagesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::middleware('web')->get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    # Users
    Route::apiResource('users',UsersController::class)->middleware('permission:user');
    Route::post('user/activate/{id}',[UsersController::class,'activate'])->middleware('permission:user-edit');
    Route::post('user/deactivate/{id}',[UsersController::class,'deactivate'])->middleware('permission:user-edit');

    # Roles
    Route::get('roles/permissions',[RolesController::class,'allPermissions'])->middleware('permission:role-create');
    Route::apiResource('roles',RolesController::class)->middleware('permission:role');

    Route::prefix('chatify')->group(function(){
        Route::post('deleteMessage',[MessagesController::class,'deleteMessage']);
        Route::post('getContact',[MessagesController::class,'getContact']);
    });

});



Route::prefix('auth')->group(function () {
	Route::post('login', [AuthController::class,'login'])->name('auth.login');
    Route::post('register', [AuthController::class, 'register']);
	Route::post('forgot-password', [AuthController::class,'sendResetLinkEmail'])->name('auth.forgotPassword');
	Route::post('reset-password', [AuthController::class,'resetPassword'])->name('auth.resetPassword');
	Route::post('logout', [AuthController::class,'logout'])->middleware('auth:sanctum')->name('auth.logout');
	Route::post('change-password', [AuthController::class,'changePassword'])->middleware('auth:sanctum')->name('auth.changePassword');

});


