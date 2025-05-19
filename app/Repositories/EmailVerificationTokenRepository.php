<?php

namespace App\Repositories;

use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;
use App\Models\EmailVerificationToken;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailVerificationTokenRepository implements EmailVerificationTokenRepositoryInterface
{
  const TOKEN_EXPIRATION_MINUTE = 60;

  public function createToken($email)
  {
    $email_token = new EmailVerificationToken;
    $email_token->email = $email;
    $email_token->token = Str::random(60);
    $email_token->token_expires_at = Carbon::now()->addMinutes(self::TOKEN_EXPIRATION_MINUTE);
    $email_token->save();
    return $email_token;
  }

  public function findToken($request)
  {
    return EmailVerificationToken::where([
        ['token', $request->token],
        ['email', $request->email]
    ])->first(); 
  }
}