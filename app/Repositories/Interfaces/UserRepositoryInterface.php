<?php

namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
  public function saveNewUser($params);

  public function findUser($email);

  public function updateUserDetails($params, $user_id);

  public function getWritersDetailsThatHasNotDoneInitialTest(array $emails);

  public function writerPassedInitialTest(array $emails);

  public function writerFailedInitialTest(array $emails);

  public function getWritersDetailsThatHasNotDoneFinalTest(array $emails);

  public function writerPassedFinalTest(array $emails);

  public function writerFailedFinalTest(array $emails);

  public function checkWriterFinalExerciseStatus(string $email);

  public function updateAdditionalUserDetails($params, $user_id);

  public function updatePassword(array $params, int $user_id);

  public function findUserByPublicReferenceID(string $public_reference_id);

  public function getVerifiedWriters();
}
