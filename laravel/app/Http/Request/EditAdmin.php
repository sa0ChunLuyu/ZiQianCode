<?php

namespace App\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Zi;

class EditAdmin extends FormRequest
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
      'account' => ['required', 'between:1,50'],
      'nickname' => ['required', 'between:1,50'],
      'avatar' => ['between:0,200'],
      'password' => ['required', 'between:6,20'],
    ];
  }

  public function messages()
  {
    return [
      'account.required' => 100016,
      'account.between' => 100017,
      'nickname.required' => 100011,
      'nickname.between' => 100012,
      'avatar.between' => 100013,
      'password.required' => 100018,
      'password.between' => 100010,
    ];
  }

  public function failedValidation(Validator $validator)
  {
    Zi::err($validator->errors()->first());
  }
}
