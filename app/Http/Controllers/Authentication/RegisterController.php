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

class RegisterController extends Controller
{
  use ReturnsJsonResponses;

  protected $authService;

  public function __construct(AuthService $authService)
  {
    $this->authService = $authService;
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

      return $this->successResponse("Link sent", "Email verification link sent successfully", 200);
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

      $resetLinkSent = $this->sendPasswordResetLink($request->email);

      if (!$resetLinkSent) {
        return $this->errorJSONResponse('Unable to send password reset link.', 'Failed', 422);
      }

      return $this->successResponse($resetLinkSent, 'A password reset link has been sent to your email.');
    } catch (\Throwable $error) {
      return $this->errorJSONResponse($error->getMessage(), 'Failed', 422);
    }
  }

  public function sendPasswordResetLink($email)
  {
    try {
      $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

      if (!$user) {
        DB::rollBack();
        return $this->errorJSONResponse('User not found for the given email.', 'Failed', 422);
      }

      do {
        $token = $this->genResetCode();
        $signature = hash('md5', $token);
        $exists = APIPasswordResetTokenModel::where([
          ['email', $user->email],
          ['token_signature', $signature]
        ])->exists();
      } while ($exists);

      $password_reset_link = env('FRONTEND_APP_URL', 'localhost:5173/') . 'resetpassword?token=' . $token . '&email=' . $email;

      Mail::to($email)->send(new PasswordResetLink($password_reset_link, $user->first_name));

      APIPasswordResetTokenModel::create([
        'email' => $user->email,
        'token_signature' => $signature,
        'expires_at' => Carbon::now()->timezone('Africa/Lagos')->addMinutes(30),
        'token_type' => 'PASSWORD_RESET_TOKEN',
      ]);

      return true;
    } catch (\Throwable $error) {
      DB::rollBack();
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


      $data = $validator->validated();

      // Check if token exists and is valid
      $tokenSignature = hash('md5', $data['password_token']);
      $verifyToken = APIPasswordResetTokenModel::where('token_signature', $tokenSignature)
        ->where('token_type', 'PASSWORD_RESET_TOKEN')
        ->first(); // Use 'first' to get the specific token

      if (!$verifyToken) {
        return $this->errorJSONResponse('Invalid Token for Resetting Password.', 'Failed', 422);
      }

      // Check if token has expired
      if (Carbon::now()->timezone('Africa/Lagos')->greaterThan($verifyToken->expires_at)) {
        // Delete expired token
        $verifyToken->delete();
        return $this->errorJSONResponse('Token already expired.', 'Failed', 422);
      }

      // Find the user associated with the token
      $user = User::where('email', $data['email'])->first();

      if (!$user) {
        return $this->errorJSONResponse('Token does not correspond to any existing user.', 'Failed', 422);
      }

      // Update the user's password
      $newPassword = bcrypt($data['password']);
      $user->update([
        'password' => $newPassword
      ]);

      // Delete the token after successful password reset
      $verifyToken->delete();

      DB::commit();

      return $this->successResponse($user);
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
