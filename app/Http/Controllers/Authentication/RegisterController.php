<?php

namespace App\Http\Controllers\Authentication;

use Carbon\Carbon;
use App\Models\User;
use App\Constants\Response;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ReturnsJsonResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Constants\EmailVerificationStatus;
use App\Http\Requests\RegisterUserRequest;
use App\Models\APIPasswordResetTokenModel;
use App\Http\Requests\ConfirmRegistrationEmailRequest;
use App\Mail\PasswordResetLink;
use Illuminate\Support\Facades\Http;
use App\Services\UserService;

class RegisterController extends Controller
{
  use ReturnsJsonResponses;

  protected $authService;
  protected $userService;


  public function __construct(AuthService $authService, UserService $userService)
  {
    $this->authService = $authService;
    $this->userService = $userService;

  }

  public function registerUser(RegisterUserRequest $request)
  {
    try {

      // Manually verify the reCAPTCHA response with Google
      // $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
      //   'secret' => env('RECAPTCHA_SECRET_KEY'),
      //   'response' => $request->input('g_recaptcha_response'),
      //   'remoteip' => $request->ip(),
      // ]);

      // $result = $response->json();

      // // Check if reCAPTCHA verification was successful
      // if (!$result['success']) {
      //   return back()->withErrors(['warning' => 'Captcha verification failed.']);
      // }

      $data = $this->authService->register($request->all());

      if (isset($data['status']) && $data['status'] == false) {
        return $this->errorJSONResponse("Unable to create user, please try again", $data['message'], 400);
      }

      return $this->successResponse($data, "User registered successfully", 200);
    } catch (\Exception $e) {
      return $this->errorJSONResponse("An error occurred", $e->getMessage(), 500);
    }
  }

  public function confirmRegistrationEmail(ConfirmRegistrationEmailRequest $request)
  {
    $verify_email = $this->authService->verifyEmail($request);

    if (isset($verify_email['status']) && $verify_email['status'] == false) {
      return $this->errorJSONResponse("An error occurred", $verify_email['message'], 400);
    }

    return $this->successResponse(null, 'email has been verified successfully', 200);
  }

  public function completePhoneVerification(Request $request)
  {

    try {
      $user =  $request->user();
      $data = $this->authService->completePhoneVerification($user, $request);

      if (isset($data['status']) && $data['status'] == false) {
        return $this->errorJSONResponse("Unable to update user details, please try again", $data['message'], 400);
      }

      return $this->successResponse(null, "Phone number verified successfully", 200);
    } catch (\Exception $e) {
      return $this->errorJSONResponse("An error occurred", $e->getMessage(), 500);
    }
  }

  public function requestNewEmailVerificationLink(Request $request)
  {
    try {
      $data = $this->authService->sendNewVerificationLink($request->user());

      if (isset($data['status']) && $data['status'] == false) {
        return $this->errorJSONResponse(Response::ERR_NOT_SUCCESSFUL, $data['message'], 400);
      }

      return $this->successResponse(null, "Email verification link sent successfully", 200);
    } catch (\Exception $e) {
      return $this->errorJSONResponse("An error occurred", $e->getMessage(), 500);
    }
  }

  public function requestPhoneVerificationCode(Request $request)
  {
    try {
      $data = $this->authService->sendPhoneVerificationCode($request->user());

      if (isset($data['status']) && $data['status'] == false) {
        return $this->errorJSONResponse(Response::ERR_NOT_SUCCESSFUL, $data['message'], 400);
      }

      return $this->successResponse(null, "Phone number verification code sent successfully", 200);
    } catch (\Exception $e) {
      return $this->errorJSONResponse("An error occurred", $e->getMessage(), 500);
    }
  }

  public function sendPasswordResetToken(Request $request)
  {
    try {
      $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
      ]);

      if ($validator->fails()) {
        return $this->errorJSONResponse($validator->errors()->first(), 'Failed', 422);
      }

      $resetLinkSent = $this->userService->getPasswordResetToken($request);

      if (isset($resetLinkSent['status']) && $resetLinkSent['status'] == false) {
        return $this->errorJSONResponse(Response::ERR_NOT_SUCCESSFUL, $resetLinkSent['message'], 400);
      }

      return $this->successResponse(null, 'A password reset link has been sent to your email.');
    } catch (\Throwable $error) {
      return $this->errorJSONResponse($error->getMessage(), 'Failed', 422);
    }
  }

  public function setNewAccountPassword(Request $request)
  {
    try {
      DB::beginTransaction();

      // Validate request
      $validator = Validator::make($request->all(), [
        'password_token' => 'required|string',
        'password' => 'required|string|min:8',
        'confirm_password' => 'required|string|min:8',
        'email' => 'required|email|exists:users,email',
      ]);

      if ($validator->fails()) {
        return $this->errorJSONResponse($validator->errors()->all(), 'Failed', 422);
      }

      if ($request->password != $request->confirm_password) {
        return $this->errorJSONResponse('Password and Confirm Password did not match.', 'Failed', 422);
      }

      // Update the user's password
      $reSet = $this->userService->updateUserPassword($request);

      if (isset($reSet['status']) && $reSet['status'] == false) {
        return $this->errorJSONResponse(Response::ERR_NOT_SUCCESSFUL, $reSet['message'], 400);
      }

      return $this->successResponse(null, 'Password reset successful.');

    } catch (\Exception $error) {
      DB::rollBack();
      return $this->errorJSONResponse($error->getMessage(), 'Failed', 422);
    }
  }

  public function genResetCode()
  {
    $str = '12356890abcefghjklnopqrsuvwxyz';
    $randStr = substr(str_shuffle($str), 0, 16);
    return $randStr;
  }
}
