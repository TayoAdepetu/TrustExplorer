<?php

namespace App\Repositories;

use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;
use App\Models\APIPasswordResetTokenModel;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class EmailVerificationTokenRepository implements EmailVerificationTokenRepositoryInterface
{
  const TOKEN_EXPIRATION_MINUTE = 60;

  public function createToken($email, $token_type)
  {
    $token = Str::random(16);
    $email_token = new APIPasswordResetTokenModel;
    $email_token->email = $email;
    $email_token->token_signature = bcrypt($token);
    $email_token->expires_at = Carbon::now()->timezone('Africa/Lagos')->addMinutes(self::TOKEN_EXPIRATION_MINUTE);;
    $email_token->token_type = $token_type;
    $email_token->save();
    return $token;
  }

  public function findToken($request, $token_type)
  {
    $tokens = APIPasswordResetTokenModel::where([
      ['email', $request->email],
      ['token_type', $token_type],
    ])->get();

    return $tokens;

    foreach ($tokens as $token) {
      if (Hash::check($request->token, $token->token_signature) && Carbon::now()->timezone('Africa/Lagos')->lessThan($token->expires_at)) {
        return $token; // token is valid
      }
    }

    return null; // no valid token found
  }
}
