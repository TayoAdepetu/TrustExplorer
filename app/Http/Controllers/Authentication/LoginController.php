<?php

namespace App\Http\Controllers\Authentication;

use Carbon\Carbon;
use App\Models\User;
use App\Constants\Response;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Traits\ReturnsJsonResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\APIPasswordResetTokenModel;
use App\Notifications\APIPasswordResetNotification;
use App\Utils\Utility;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetLink;
use Illuminate\Support\Facades\Http;


class LoginController extends Controller
{
  use ReturnsJsonResponses;

  protected $authService;
  protected $userService;

  public function __construct(AuthService $authService, UserService $userService)
  {
    $this->authService = $authService;
    $this->userService = $userService;
  }

  public function loginUser(LoginRequest $request)
  {
    // Manually verify the reCAPTCHA response with Google
    // $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
    //   'secret' => env('RECAPTCHA_SECRET_KEY'),
    //   'response' => $request->g_recaptcha_response,
    //   'remoteip' => $request->ip(),
    // ]);

    // $result = $response->json();

    // // Check if reCAPTCHA verification was successful
    // if (!$result['success']) {
    //   return back()->withErrors(['warning' => 'Captcha verification failed.']);
    // }

    if (!Auth::attempt($request->all())) {
      return $this->errorJSONResponse("Invalid email and/or password", Response::ERR_INVALID_USER, 400);
    }

    $user = $request->user();
    try {
      $data = $this->authService->login($user, $request->all());

      if (isset($data['status']) && ($data['status'] == false)) {
        return response()->json($data, $data['http_status']);
      }
      $user->last_seen = Carbon::now();
      $user->save();
      return $this->successResponse($data, "Login Successful");
    } catch (\Exception $e) {
      return $this->errorJSONResponse("An error occurred", $e->getMessage(), 500);
    }
  }

  public function getUserDetail($user_ref)
  {
    $user = $this->userService->getUserProfile($user_ref);
    return $this->successResponse($user, "User Details");
  }

  public function logout()
  {
    Auth::logout();
    return response()->json([
      'status' => 'success',
      'message' => 'Successfully logged out',
    ]);
  }

  public function refresh()
  {
    return response()->json([
      'status' => 'success',
      'user' => Auth::user(),
      'authorisation' => [
        'token' => Auth::refresh(),
        'type' => 'bearer',
      ]
    ]);
  }
}
