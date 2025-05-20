<?php

namespace App\Http\Middleware;

use App\Constants\UserAccountStatus;
use Closure;
use Illuminate\Http\Request;
use App\Constants\UserRole;
use App\Traits\ReturnsJsonResponses;
use Carbon\Carbon;

class isAccountActive
{
  use ReturnsJsonResponses;

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    $user = $request->user();
    if (($user->role != UserRole::ADMIN) && ($user->account_status != UserAccountStatus::ACTIVE)) {
      return $this->authorizationError("User is not authorized. Only users with verified emails are allowed.");
    } else {
      $user->last_seen = Carbon::now();
      $user->save();
      return $next($request);
    }
  }
}
