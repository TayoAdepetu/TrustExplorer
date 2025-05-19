<?php

use Illuminate\Support\Facades\Route;


Route::post('/register', 'Authentication\RegisterController@registerUser');
Route::get('/login', 'Authentication\LoginController@loginUser');

Route::middleware('auth:api')->group(function () {
    Route::get('/logout', 'Authentication\LoginController@logout');
    Route::get('/refresh', 'Authentication\LoginController@refresh');
});
