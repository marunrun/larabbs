<?php

namespace App\Http\Requests\Api;

use Dingo\Api\Http\FormRequest as BaseRequest;

class FormRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

}
