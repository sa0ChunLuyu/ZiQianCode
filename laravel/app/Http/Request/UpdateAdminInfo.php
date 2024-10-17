<?php

namespace App\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Zi;

class UpdateAdminInfo extends FormRequest
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
      'nickname' => ['required', 'between:1,50'],
      'avatar' => ['between:0,200'],
    ];
  }

  public function messages()
  {
    return [
      'nickname.required' => 100011,
      'nickname.between' => 100012,
      'avatar.between' => 100013,
    ];
  }

  public function failedValidation(Validator $validator)
  {
    Zi::err($validator->errors()->first());
  }
}
