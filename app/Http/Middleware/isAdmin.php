<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Constants\UserRole;
use App\Traits\ReturnsJsonResponses;
use Carbon\Carbon;

class isAdmin
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
    if ($user->role != UserRole::ADMIN) {
      return $this->authorizationError("The logged in user is not authorized to carry out the request. Only admin is allowed");
    } else {
      $user->last_seen = Carbon::now();
      $user->save();
      return $next($request);
    }
  }
}
