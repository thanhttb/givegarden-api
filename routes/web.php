<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
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
// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/test', function(){
    return 'success';
});
Route::get('/create-user', [UserController::class, 'createTestUser']);
// Route::get('/admin-login', [UserController::class, 'adminLogin']);
// Route::post('/login', [UserController::class, 'login']);