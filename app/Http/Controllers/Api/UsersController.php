<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;


class UsersController extends Controller
{
    public function store(UserRequest $request)
    {
        $cache_key = env('SMS_PREFIX'). $request->phone .'_'. $request->verification_key;
        $verifyData = \Cache::get($cache_key);

        if(!$verifyData){
            return $this->response->error('验证码已经失效',422);
        }

        if(!hash_equals($verifyData['code'],$request->verification_code)){
            return $this->response->errorUnauthorized('验证码错误');
        }

        $user = User::create([
            'name'  =>  $request->name,
            'phone' => $verifyData['phone'],
            'password'  => bcrypt($request->password),
        ]);

        //  清除缓存
        \Cache::forget($cache_key);

        return $this->response->created();
    }
}
