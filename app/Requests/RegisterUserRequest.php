<?php

namespace App\Http\Requests;

use App\Constants\UserRole;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends BaseFormRequest
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
      // "g_recaptcha_response" => "required",
      "first_name" => "required|string",
      "last_name" => "required|string",
      "role" => [
        'required',
        Rule::in(['Client', 'Freelancer'])
      ],
      "email" => "required|email|unique:users,email",
      "password" => "required|min:6"
    ];
  }
}
