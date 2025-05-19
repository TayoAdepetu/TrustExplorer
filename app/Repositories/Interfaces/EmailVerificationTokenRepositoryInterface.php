<?php

namespace App\Repositories\Interfaces;

interface EmailVerificationTokenRepositoryInterface
{
  public function createToken($email);

  public function findToken($request);
}