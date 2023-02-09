<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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
    Route::get('/user',[UserController::class], 'get');

    Route::post('/users/update', [UserController::class, 'update']);
    Route::post('/users/create', [UserController::class, 'create']);
    Route::post('/users/deactive', [UserController::class, 'deactive']);
    Route::post('/users/reactive', [UserController::class, 'reactive']);
    Route::post('/users/reset-password', [UserController::class, 'resetPassword']);
    // Route::post('/users/create')
});
