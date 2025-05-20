<?php

use Illuminate\Support\Facades\Route;


Route::post('/register', 'Authentication\RegisterController@registerUser');
Route::post('/login', 'Authentication\LoginController@loginUser');
Route::get('/get-user-details/{user_ref}', 'Authentication\LoginController@getUserDetail');
Route::post('/send-password-reset-token', 'Authentication\RegisterController@sendPasswordResetToken');
Route::post('/reset-password', 'Authentication\RegisterController@setNewAccountPassword');

Route::middleware(['auth:api'])->group(function () {
    Route::post('/logout', 'Authentication\LoginController@logout');
    Route::post('/verify-email', 'Authentication\RegisterController@confirmRegistrationEmail');
    Route::post('/request-new-email-verification', 'Authentication\RegisterController@requestNewEmailVerificationLink');
    Route::post('/request-phone-verification', 'Authentication\RegisterController@requestPhoneVerificationCode');
    Route::post('/verify-phone-number', 'Authentication\RegisterController@completePhoneVerification');

});
