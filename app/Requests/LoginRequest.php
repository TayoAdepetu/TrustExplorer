<?php

namespace App\Http\Requests;

class LoginRequest extends BaseFormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array
   */
  public function rules()
  {
    return [
      "password" => "required|min:6",
      // "g_recaptcha_response" => "required",
      "email" => "required|email|exists:users,email"
    ];
  }
}
