<?php

namespace App\Http\Requests\Api\V1_0;

use App\Http\Requests\AbstractJsonRequest;
use Gate;

class VerifyPasswordResetRequest extends AbstractJsonRequest
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
            'email' => 'required|email|exists:volunteers,email',
            'token' => 'required'
        ];
    }
}
