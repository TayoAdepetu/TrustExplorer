<?php

namespace App\Repositories\Interfaces;

interface EmailVerificationTokenRepositoryInterface
{
  public function createToken($email, $token_type);

  public function findToken($request, $token_type);
}