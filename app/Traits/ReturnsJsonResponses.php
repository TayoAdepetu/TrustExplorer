<?php

namespace App\Traits;

use App\Constants\Response;
use Illuminate\Support\MessageBag;

trait ReturnsJsonResponses
{
  public function successResponse($data=[], $message="Successful", $http_status=200)
  {
    $status = true;
    $success_data = compact('status', 'message', 'data');
    return response()->json($success_data, $http_status); 
  }

  public function errorResponse($error="An error occurred", $message="Failed", $http_status=500)
  {
    $status = false;
    $error_data = compact('status', 'message', 'error', 'http_status');
    return $error_data;
  }

  public function errorJSONResponse($message, $error, $http_status)
  {
    $status = false;
    $error_data = compact('status', 'message', 'error');
    return response()->json($error_data, $http_status);
  }

  public function quickErrorResponse($message){
    return [
      'status' => false,
      'message' => $message
    ];
  }  

  public function exceptionResponse(\Exception $exception, $message="An error occurred", $http_status=500)
  {
    $status = false;
    $error = $exception->getMessage();
    return compact('status', "message", "error", "http_status");
  }

  public function validationErrorResponse(MessageBag $messageBag, $errorCode, $http_status)
  {
    $data = [
      'message' => $messageBag->first(),
      'error' => $errorCode,
      'status' => false
    ];
    return response()->json($data, $http_status);
  }

  public function authorizationError($message)
  {
    $data = [
      'message' => $message,
      'type' => Response::NOT_AUTHORIZED, 
      'status' => false
    ];

    return response()->json($data, 401);
  }

  // protected function withArray(array $array, int $http_status = 200, array $headers = [], $options = 0)
  // {
  //   return response()->json($array, $http_status, $headers, $options);
  // }
}
