<?php

namespace App\Repositories;

use App\Constants\EmailVerificationStatus;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use App\Models\freelancerinvitation;
use App\Constants\UserRole;
use App\Utils\Utility;
use App\Constants\TestStatus;
use App\Constants\UserAccountStatus;

class UserRepository implements UserRepositoryInterface
{
  public function saveNewUser($params)
  {
    $public_reference_id = Utility::generateReferenceId();

    // check if public_reference_id does not match
    $existing  = User::where("public_reference_id", $public_reference_id)->exists();
    if ($existing) {
      throw new \Exception("Duplicate Reference ID. please try again later");
    }

    $new_user = new User;
    $new_user->first_name = $params['first_name'];
    $new_user->last_name = $params['last_name'];
    $new_user->email = $params['email'];
    $new_user->role_state = $params['role'];
    $new_user->wmb = $params['role'] == "Freelancer" ? "ADMITTED" : null;
    $new_user->password = $params['password'];
    $new_user->public_reference_id = $public_reference_id;
    $new_user->avatar = config('chatify.user_avatar.default');
    $new_user->save();
    return $new_user;
  }

  public function findUser($email)
  {
    return User::where('email', $email)->first();
  }

  public function updateUserDetails($params, $user_id)
  {
    $user = User::find($user_id);

    $user->username = $params['username'];
    $user->gender = $params['gender'];
    $user->phone_number = $params['phone_number'];
    $user->country = $params['country'];
    $user->date_of_birth = $params['date_of_birth'];
    if ($params['writing_niches']) {
      $user->writing_niches = $params['writing_niches'];
    }
    $user->save();
    return $user;
  }

  public function getWritersDetailsThatHasNotDoneInitialTest(array $emails)
  {
    return User::where([
      ['role', UserRole::USER],
      ['writer_stage_one_test_status', '!=', TestStatus::COMPLETED]
    ])->whereIn('email', $emails)->get();
  }

  public function writerPassedInitialTest(array $emails)
  {
    return User::where('role', UserRole::USER)->whereIn('email', $emails)->update([
      'writer_stage_one_test_status' => TestStatus::COMPLETED,
      'wmb' => "PASSED",
    ]);
  }

  public function writerFailedInitialTest(array $emails)
  {
    return User::where('role', UserRole::USER)->whereIn('email', $emails)->update([
      'writer_stage_one_test_status' => TestStatus::FAILED,
      'wmb' => "AWAITING RETRIAL",
    ]);
  }

  public function getWritersDetailsThatHasNotDoneFinalTest(array $emails)
  {
    return User::where([
      ['role', UserRole::USER],
      ['writer_stage_one_test_status', TestStatus::COMPLETED],
      ['writer_stage_two_test_status', '!=', TestStatus::COMPLETED]
    ])->whereIn('email', $emails)->latest()->get();
  }

  public function writerPassedFinalTest(array $emails)
  {
    return User::where('role', UserRole::USER)->whereIn('email', $emails)->update([
      'writer_stage_two_test_status' => TestStatus::COMPLETED
    ]);
  }

  public function writerFailedFinalTest(array $emails)
  {
    return User::where('role', UserRole::USER)->whereIn('email', $emails)->update([
      'writer_stage_two_test_status' => TestStatus::FAILED
    ]);
  }

  public function checkWriterFinalExerciseStatus(string $email)
  {
    return User::where([
      ['role', UserRole::USER],
      ['writer_stage_one_test_status', TestStatus::PENDING],
      ['email', $email]
    ])->first();
  }

  public function updateAdditionalUserDetails($params, $user_id)
  {
    $user = User::find($user_id);

    if (isset($params['file_cloud_url'])) {
      $user->profile_picture = $params['file_cloud_url'];
    }

    if (isset($params['email'])) {
      $user->email = $params['email'];
    }

    if (isset($params['phone_number'])) {
      $user->phone_number = $params['phone_number'];
    }

    if (isset($params['first_name'])) {
      $user->first_name = $params['first_name'];
    }

    if (isset($params['last_name'])) {
      $user->last_name = $params['last_name'];
    }

    if (isset($params['about_me'])) {
      $user->about_me = $params['about_me'];
    }

    if (isset($params['languages'])) {
      $user->languages = $params['languages'];
    }

    if (isset($params['availability'])) {
      $user->availability = (bool)$params['availability'];
    }

    if (isset($params['additional_links'])) {
      $user->additional_links = $params['additional_links'];
    }

    if (isset($params['country'])) {
      $user->country = $params['country'];
    }

    if (isset($params['username'])) {
      $user->username = $params['username'];
    }

    if (isset($params['writing_niches'])) {
      $user->writing_niches = $params['writing_niches'];
    }

    if (isset($params['skills'])) {
      $user->skills = $params['skills'];
    }

    if (isset($params['category'])) {
      $user->category = $params['category'];
    }

    if (isset($params['subcategory'])) {
      $user->subcategory = $params['subcategory'];
    }

    if (isset($params['gender'])) {
      $user->gender = $params['gender'];
    }

    if (isset($params['date_of_birth'])) {
      $user->date_of_birth = $params['date_of_birth'];
    }

    $user->save();
    return $user;
  }

  public function updatePassword($params, $user_id)
  {
    $password = bcrypt($params['password']);
    $user = User::find($user_id);
    $user->password = $password;
    $user->save();
    return $user;
  }

  public function findUserByPublicReferenceID($public_reference_id)
  {
    return User::select([
      'id',
      'public_reference_id',
      'first_name',
      'last_name',
      'role',
      'availability',
      'skills',
      'writing_niches',
      'username',
      'profile_picture',
      'avatar',
      'category',
      'country',
      'about_me',
      'languages',
      'suspension_note',
      'biz_name_slug',
      'writer_stage_one_test_status',
    ])->wherePublicReferenceId($public_reference_id)->with('portfolio', 'academic', 'portfoliovideo', 'employment')->first();
  }


  public function getVerifiedWriters()
  {
    return User::select([
      'public_reference_id',
      'skills',
      'username',
      'profile_picture',
      'writing_niches',
      'category',
      'country',
      'about_me',
      'languages',
    ])->where([
      ['role', UserRole::USER],
      ['account_status', UserAccountStatus::ACTIVE],
      ['email_verification_status', EmailVerificationStatus::COMPLETED],
      ['writer_stage_one_test_status', TestStatus::COMPLETED]
    ])->get();
  }
}
