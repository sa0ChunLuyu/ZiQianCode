<?php

namespace App\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Zi;

class ChangeAdminPassword extends FormRequest
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
      'password' => ['required', 'between:6,20'],
    ];
  }

  public function messages()
  {
    return [
      'password.required' => 100008,
      'password.between' => 100009,
    ];
  }

  public function failedValidation(Validator $validator)
  {
    Zi::err($validator->errors()->first());
  }
}
