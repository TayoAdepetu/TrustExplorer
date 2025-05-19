<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentication\LoginController;

Route::post('register', [LoginController::class, 'register']);
Route::post('login', [LoginController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [LoginController::class, 'me']);
    Route::post('logout', [LoginController::class, 'logout']);
    Route::post('refresh', [LoginController::class, 'refresh']);
});
