<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Utils\Utility;
use App\Constants\UserRole;
use App\Constants\TestStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Constants\UserAccountStatus;
use App\Traits\ReturnsJsonResponses;
use Illuminate\Support\Facades\Hash;
use App\Models\BuyNowPayLaterRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\JobsProposalRepositoryInterface;
use App\Repositories\Interfaces\WriterPortfolioRepositoryInterface;
use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;

class UserService
{
  use ReturnsJsonResponses;

  protected $userRepo;
  protected $portfolioRepo;
  protected $jobsProposalRepository;
  protected $emailVerificationRepo;

  public function __construct(
    UserRepositoryInterface $userRepo,
    EmailVerificationTokenRepositoryInterface $emailVerificationRepo,
  ) {
    $this->userRepo = $userRepo;
    $this->emailVerificationRepo = $emailVerificationRepo;
  }

  public function updateUserDetails($params, $user_id)
  {
    try {
      // upload profile picture to cloudinary if available
      if (isset($params['profile_picture'])) {
        $file_cloud_url = Utility::uploadFileToCloudinary($params['profile_picture']);

        if (isset($file_cloud_url['status']) && $file_cloud_url['status'] == false) {
          return $file_cloud_url;
        }

        $params['file_cloud_url'] = $file_cloud_url;
      }

      //update user details
      $update_user_details = $this->userRepo->updateAdditionalUserDetails($params, $user_id);

      // update protfolio
      if (isset($params['articles']) && count($params['articles']) > 0) {
        $portfolio = [];
        foreach ($params['articles'] as $article) {
          $updatePortfolio = $this->portfolioRepo->save($article, $user_id);
          array_push($portfolio, $updatePortfolio);
        }

        $update_user_details['portfolio'] = $portfolio;
      }

      return $update_user_details;
    } catch (\Exception $e) {
      throw $e;
    }
  }

  public function updateUserPassword($param)
  {
    // check if user exist with the email
    $user = $this->userRepo->findUser($param->email);
    if (!$user) {
      return $this->quickErrorResponse("User with this e-mail address does not exist.");
    }

    if (Hash::check($param->password, $user->password)) {
      return $this->errorResponse('Password has been used recently. Please use a new password');
    }

    // get email verification token
    $get_token = $this->emailVerificationRepo->findToken($param, 'PASSWORD_RESET_TOKEN');
    
    if (!$get_token) {
      return $this->quickErrorResponse("This password reset token is invalid.");
    }

    // check if token has expired then delete
    if (Carbon::parse($get_token->expires_at)->isPast()) {
      $get_token->delete();
      return $this->quickErrorResponse("Password reset token has expired. Please request a new token to continue.");
    }

    DB::beginTransaction();
    try {

      return $this->userRepo->updatePassword($param, $user->id);      

      // send email verified email to the user based on their role,
      $email_payload = [
        'to' => $user->email,
        'subject' => 'Password Reset Successful',
        'body' => [
          'first_name' => $user->first_name
        ],
      ];

      $email_payload['view'] = 'email.password_reset_successful';

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

  public function getPasswordResetToken($param)
  {
    DB::beginTransaction();
    try {
      // check if user exists
      $user = $this->userRepo->findUser($param->email);

      if (!$user) {
        return $this->quickErrorResponse('User does not exist');
      }
      // create email verification token
      $create_token = $this->emailVerificationRepo->createToken($param->email, 'PASSWORD_RESET_TOKEN');

      $password_reset_link = env('FRONTEND_APP_URL', 'localhost:5173/') . 'resetpassword?token=' . $create_token . '&email=' . $param->email;

      // send verification email to the user
      $email_payload = [
        'to' => $user->email,
        'subject' => 'Request Password Reset Link',
        'body' => [
          'first_name' => $user->first_name,
          'password_reset_link' => $password_reset_link
        ],
        'view' => 'email.password_reset_link'
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

  public function getUserProfile($public_reference_id)
  {
    $user = $this->userRepo->findUserByPublicReferenceID($public_reference_id);
    if (!$user) {
      return null;
    }

    return $user;
  }

  public function getVerifiedWritersProfile()
  {
    return $this->userRepo->getVerifiedWriters();
  }

  public function suspendUser($params)
  {
    DB::beginTransaction();

    try {
      $user = User::find($params['user_id']);
      $user->account_status = UserAccountStatus::SUSPENDED;
      $user->save();

      $email_payload = [
        'to' => $user['email'],
        'subject' => 'Account Suspended',
        'body' => [
          'first_name' => $user['first_name'],
          'comment' => $params['suspension_note']
        ],
        'view' => 'email.suspend_user'
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

  public function getAllUsers()
  {
    return User::latest()->get();
  }

  public function updateAccountStatus($params, $user_id)
  {
    $user = User::find($user_id);
    if (!$user) {
      return $this->quickErrorResponse('User not found');
    }

    $user->account_status = $params['status'] ?? $user->account_status;
    return $user->save();
  }
}
