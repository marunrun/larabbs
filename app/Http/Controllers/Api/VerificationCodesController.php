<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Overtrue\EasySms\EasySms;
use App\Http\Requests\Api\VerificationCodeRequest;

class VerificationCodesController extends Controller
{

    public function store(VerificationCodeRequest $request, EasySms $easysms)
    {
        $phone = $request->phone;
        if (!app()->environment('production')) {
            $code = '1234';
        } else {
            $code = str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);
            try {
                $res = $easysms->send($phone, [
                    'template' => env('ALIYUN_TEMPLATE_ID'),
                    'data' => [
                        'code' => $code
                    ]
                ]);
            } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $e) {
                $message = $e->getException('aliyun')->getMessage();
                return $this->response->errorInternal($message ?? '短信发送异常');
            }
        }
        $key= str_random(15);
        $cache_key = env('SMS_PREFIX'). $phone .'_'. $key;

        $expiredAt = now()->addMinutes(10);
        // 缓存验证码 10分钟过期
        \Cache::put($cache_key, ['phone' => $phone, 'code' => $code], $expiredAt);
        return $this->response->array([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }


}
