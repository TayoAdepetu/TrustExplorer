<?php

namespace App\Repositories;

use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;
use App\Models\APIPasswordResetTokenModel;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailVerificationTokenRepository implements EmailVerificationTokenRepositoryInterface
{
  const TOKEN_EXPIRATION_MINUTE = 60;

  public function createToken($email)
  {
    $email_token = new APIPasswordResetTokenModel;
    $email_token->email = $email;
    $email_token->token_signature = bcrypt(Str::random(16));
    $email_token->expires_at = Carbon::now()->addMinutes(self::TOKEN_EXPIRATION_MINUTE);
    $email_token->token_type = 'EMAIL_VERIFICATION_TOKEN';
    $email_token->save();
    return $email_token;
  }

  public function findToken($request)
  {
    return APIPasswordResetTokenModel::where([
        ['token_signature',  bcrypt($request->token)],
        ['email', $request->email],
        ['token_type', 'EMAIL_VERIFICATION_TOKEN']
    ])->first(); 
  }
}