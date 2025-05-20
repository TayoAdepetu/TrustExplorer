<?php

namespace App\Services;

use App\Utils\Utility;
use App\Models\User;
use App\Constants\UserRole;
use App\Constants\TestStatus;
use App\Constants\UserAccountStatus;
use App\Models\BuyNowPayLaterRequest;
use App\Traits\ReturnsJsonResponses;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\JobsProposalRepositoryInterface;
use App\Repositories\Interfaces\WriterPortfolioRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UserService
{
  use ReturnsJsonResponses;

  protected $userRepo;
  protected $portfolioRepo;
  protected $jobsProposalRepository;

  public function __construct(
    UserRepositoryInterface $userRepo,
  ) {
    $this->userRepo = $userRepo;
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

  public function updateUserPassword($params, $user_details)
  {
    if (Hash::check($params['password'], $user_details->password)) {
      return $this->errorResponse('Password has been used recently. Please use a new password');
    }

    return $this->userRepo->updatePassword($params, $user_details->id);
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
