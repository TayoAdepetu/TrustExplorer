<?php

namespace App\Exceptions;

use Throwable;
use App\Constants\Response;
use App\Traits\ReturnsJsonResponses;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
// use GuzzleHttp\Exception\ClientException;

class Handler extends ExceptionHandler
{
  use ReturnsJsonResponses;

  /**
   * A list of the exception types that are not reported.
   *
   * @var array
   */
  protected $dontReport = [
    //
  ];

  /**
   * A list of the inputs that are never flashed for validation exceptions.
   *
   * @var array
   */
  protected $dontFlash = [
    'password',
    'password_confirmation',
  ];

  /**
   * Register the exception handling callbacks for the application.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  /**
   * Render an exception into an HTTP response.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Throwable  $exception
   * @return \Symfony\Component\HttpFoundation\Response
   *
   * @throws \Throwable
   */
  public function render($request, Throwable $exception)
  {
    // For Public API Endpoints Error 422 Rendering
    if ($exception instanceof UnprocessableEntityHttpException) {
      $message = $exception->getMessage();
      $messageArray = json_decode($message, true);
      // set the pointer to point to the first element
      reset($messageArray);
      $first = current($messageArray);
      // Get first validation error message
      $error = $first[0];
      return response()->json([
        'error' => $error,
        'status' => false
      ], 422);
    }

    if ($exception instanceof BadRequestException) {
      return $this->exceptionResponse($exception, Response::ERR_NOT_SUCCESSFUL, 400);
    }

    if ($exception instanceof HttpException) {
      return $this->exceptionResponse($exception, Response::NOT_AUTHORIZED, 401);
    }

    return parent::render($request, $exception);
  }

  public function report(Throwable $exception)
  {
    if (app()->bound('sentry') && $this->shouldReport($exception)) {
      app('sentry')->captureException($exception);
    }

    parent::report($exception);
  }
}
