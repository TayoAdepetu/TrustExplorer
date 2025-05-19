<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\WritingNiches;
use Illuminate\Support\Facades\Auth;

class UpdateUserDetailsRequest extends BaseFormRequest
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
      "first_name" => "sometimes|string",
      "last_name" => "sometimes|string",
      "about_me" => "sometimes",
      "languages" => "sometimes",
      "availability" => "sometimes|boolean",
      "profile_picture" => "file|sometimes|mimes:jpeg,jpg,png,bmp,gif,svg|max:1999",
      "additional_links" => "sometimes",
      "email" => [
        "sometimes",
        "email",
        Rule::unique('users')->ignore(Auth::user()->id)
      ],
      "country" => "sometimes|string",
      "username" => [
        "sometimes",
        "string",
        Rule::unique('users')->ignore(Auth::user()->id)
      ],
      "phone_number" => [
        "sometimes",
        "numeric",
        Rule::unique('users')->ignore(Auth::user()->id)
      ],
      "writing_niches" => [
        "sometimes",
        "array",
      ],
      "gender" => "sometimes|string",
      "date_of_birth" => "sometimes|date",
    ];
  }
}
