<?php

use Illuminate\Support\Facades\Route;


Route::post('/register', 'Authentication\RegisterController@registerUser');
Route::post('/login', 'Authentication\LoginController@loginUser');
Route::get('/get-user-details/{user_ref}', 'Authentication\LoginController@getUserDetail');

Route::middleware('auth:api')->group(function () {
    Route::get('/logout', 'Authentication\LoginController@logout');
    Route::get('/refresh', 'Authentication\LoginController@refresh');
});
