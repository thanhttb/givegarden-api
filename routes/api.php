<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;


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
Route::post('/login', [UserController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user',[UserController::class, 'get']);
    // Route::post('/check-auth', [UserController::class, 'checkAuth']);

    Route::post('/users/update', [UserController::class, 'update']);
    Route::post('/users/create', [UserController::class, 'create']);
    Route::post('/users/deactive', [UserController::class, 'deactive']);
    Route::post('/users/reactive', [UserController::class, 'reactive']);
    Route::post('/users/reset-password', [UserController::class, 'resetPassword']);

    Route::get('/users/coaches', [UserController::class, 'getCoach']);
    Route::get('/users/available-users', [UserController::class, 'getAvailableUser']);
    // Route::post('/users/create')

//Group
    Route::get('/groups', [GroupController::class, 'get']);
    Route::post('/groups/create', [GroupController::class, 'create']);
    Route::post('/groups/edit', [GroupController::class, 'edit']);
    Route::post('/groups/deactivate', [GroupController::class, 'deactivate']);
});
