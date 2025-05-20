<?php

namespace App\Services;

use Carbon\Carbon;
use App\Utils\Utility;
use App\Constants\UserRole;
use Illuminate\Support\Facades\DB;
use App\Constants\UserAccountStatus;
use App\Traits\ReturnsJsonResponses;
use Illuminate\Support\Facades\Auth;
use App\Constants\EmailVerificationStatus;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;
use App\Repositories\Interfaces\PaymentTransferRecipientRepositoryInterface;

class AuthService
{
  use ReturnsJsonResponses;

  protected $userRepo;
  protected $emailVerificationRepo;
  protected $manageTestLinkRepo;
  protected $paymentTransferRepos;
  protected $jwtAuth;

  public function __construct(
    UserRepositoryInterface $userRepo,
    EmailVerificationTokenRepositoryInterface $emailVerificationRepo,
    JWTAuth $jwtAuth
  ) {
    $this->userRepo = $userRepo;
    $this->emailVerificationRepo = $emailVerificationRepo;
    $this->jwtAuth = $jwtAuth;
  }

  public function register($params)
  {
    $params['password'] = bcrypt($params['password']);
    DB::beginTransaction();
    try {
      // create the user in the db
      $created_user = $this->userRepo->saveNewUser($params);

      // create email verification token
      $create_token = $this->emailVerificationRepo->createToken($params['email'], 'EMAIL_VERIFICATION_TOKEN');

      // send verification email to the user, 
      $email_payload = [
        'to' => $created_user->email,
        'subject' => 'Verify Your Email Address For Registration',
        'body' => [
          'first_name' => $created_user->first_name,
          'email_verification_link' => env('FRONTEND_APP_URL') . 'verify?token=' . $create_token . '&email=' . $params['email']
        ],
      ];

      $email_payload['view'] = 'email.welcome_email';

      $email_response =  Utility::sendEmail($email_payload);

      if (isset($email_response['status']) && $email_response['status'] == false) {
        DB::rollBack();
        return $email_response;
      }

      DB::commit();
      return $created_user;
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function verifyEmail(object $request)
  {
    // check if user exist with the email
    $user = $this->userRepo->findUser($request->email);
    if (!$user) {
      return $this->quickErrorResponse("User with this e-mail address does not exist.");
    }

    // get email verification token
    $get_token = $this->emailVerificationRepo->findToken($request, 'EMAIL_VERIFICATION_TOKEN');
    if (!$get_token) {
      return $this->quickErrorResponse("This email verification token is invalid.");
    }

    // check if token has expired then delete
    if (Carbon::parse($get_token->expires_at)->isPast()) {
      $get_token->delete();
      return $this->quickErrorResponse("Email verification token has expired. Please request a new token to continue.");
    }

    DB::beginTransaction();
    try {
      //update user details for successful email verification
      $user->email_verified_at = Carbon::now();
      if ($user->phone_verified_at !== null) {
        $user->account_status = UserAccountStatus::ACTIVE;
      }
      $user->save();

      // send email verified email to the user based on their role,
      $email_payload = [
        'to' => $user->email,
        'subject' => 'Email Address Verified',
        'body' => [
          'first_name' => $user->first_name
        ],
      ];

      $email_payload['view'] = 'email.email_verified';

      $email_response =  Utility::sendEmail($email_payload);

      if (isset($email_response['status']) && $email_response['status'] == false) {
        DB::rollBack();
        return $email_response;
      }

      DB::commit();
      return $get_token->delete();
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function login(object $user, array $credentials)
  {
    try {
      $token = $this->jwtAuth::fromUser($user, $credentials);
      if (!$token) {
        return $this->errorResponse("unauthorized", "Login failed. Invalid Credentials", 401);
      }
      $user = $user->toArray() + $this->getTokenDetails($token);

      return $user;
    } catch (JWTException $exception) {
      return $this->exceptionResponse($exception);
    }
  }

  public function getTokenDetails(string $token)
  {
    return array(
      "token" => $token,
      "token_type" => "bearer",
      "expires_in" => Auth::factory()->getTTL() * 60 * 60 * 3 // to expire in 3 hours 
    );
  }

  public function completePhoneVerification($the_user, object $request)
  {
    // check if user exist with the email
    $user = $this->userRepo->findUser($the_user->email);
    if (!$user) {
      return $this->quickErrorResponse("User does not exist.");
    }

    // get email verification token
    $get_token = $this->emailVerificationRepo->findToken($request, 'PHONE_VERIFICATION_TOKEN');
    if (!$get_token) {
      return $this->quickErrorResponse("This phone number verification code is invalid.");
    }

    // check if token has expired then delete
    if (Carbon::parse($get_token->expires_at)->isPast()) {
      $get_token->delete();
      return $this->quickErrorResponse("Phone number verification code has expired. Please request a new code to continue.");
    }

    DB::beginTransaction();
    try {
      //update user details for successful email verification

      $user->phone_verified_at = Carbon::now();
      if ($user->email_verified_at !== null) {
        $user->account_status = UserAccountStatus::ACTIVE;
      }
      $user->save();

      // send email verified email to the user based on their role,
      $email_payload = [
        'to' => $user->email,
        'subject' => 'Phone Number Verified',
        'body' => [
          'first_name' => $user->first_name
        ],
      ];

      $email_payload['view'] = 'email.phone_verified';

      $email_response =  Utility::sendEmail($email_payload);

      if (isset($email_response['status']) && $email_response['status'] == false) {
        DB::rollBack();
        return $email_response;
      }

      DB::commit();
      return $get_token->delete();
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function sendNewVerificationLink($params)
  {
    DB::beginTransaction();
    try {
      // check if user exists
      $user = $this->userRepo->findUser($params['email']);

      if (!$user) {
        return $this->quickErrorResponse('User does not exist');
      }

      // check if user has completed email verification
      if ($user->email_verified_at !== null) {
        return $this->quickErrorResponse('User email has been verified already');
      }

      // create email verification token
      $create_token = $this->emailVerificationRepo->createToken($params['email'], 'EMAIL_VERIFICATION_TOKEN');

      // send verification email to the user
      $email_payload = [
        'to' => $user->email,
        'subject' => 'Request New Verification Link',
        'body' => [
          'first_name' => $user->first_name,
          'email_verification_link' => env('FRONTEND_APP_URL') . 'verify?token=' . $create_token->token . '&email=' . $create_token->email
        ],
        'view' => 'email.request_new_email_verification_link'
      ];

      $email_response =  Utility::sendEmail($email_payload);

      if (isset($email_response['status']) && $email_response['status'] == false) {
        DB::rollBack();
        return $email_response;
      }

      DB::commit();
      return true;
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function sendPhoneVerificationCode($params)
  {
    DB::beginTransaction();

    try {
      // check if user exists
      $user = $this->userRepo->findUser($params['email']);

      if (!$user) {
        return $this->quickErrorResponse('User does not exist');
      }

      // check if user has completed email verification
      if ($user->phone_verified_at !== null) {
        return $this->quickErrorResponse('User phone has been verified already.');
      }

      // create email verification token
      $create_token = $this->emailVerificationRepo->createToken($params['email'], 'PHONE_VERIFICATION_TOKEN');

      $message = "Your TrustExplorer phone verification code is: $create_token";

      // Use your SMS provider to send the code

      $otp_response = Utility::sendOTPViaTermii($params['phone_number'], $message);

      if (isset($otp_response['status']) && $otp_response['status'] == false) {
        DB::rollBack();
        return $otp_response;
      }

      DB::commit();
      return true;
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
