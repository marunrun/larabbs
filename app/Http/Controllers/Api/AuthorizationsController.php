<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AuthorizationRequest;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use App\Http\Requests\Api\WeappAuthorizationRequest;
use App\Models\User;
use Dingo\Api\Auth\Auth;
use Illuminate\Http\Request;

class AuthorizationsController extends Controller
{
    /**
     * 第三方授权登录
     * @param $type
     * @param SocialAuthorizationRequest $request
     */
    public function socialStore($type , SocialAuthorizationRequest $request)
    {
        if(!in_array($type,['weixin'])){
            return $this->response->errorBadRequest();
        }

        $driver = \Socialite::driver($type);
        try{
            if($code = $request->code){
                $response = $driver->getAccessTokenResponse($code);
                $token = array_get($response,'access_token');
            }else{
                $token =$request->access_token;
                if ($type == 'weixin'){
                    $driver->setOpenId($request->openid);
                }
            }

            $oauthUser = $driver->userFromToken($token);
        }catch (\Exception $e){
            return $this->response->errorUnauthorized('参数错误,未获取到用户信息');
        }

        switch ($type){
            case 'weixin':
                $unionid = $oauthUser->offsetExists('unionid') ? $oauthUser->offsetGet('unionid') : null;
                if ($unionid){
                    $user = User::where('weixin_unionid',$unionid)->first();
                }else{
                    $user = User::where('weixin_openid', $oauthUser->getId())->first();
                }

                // 没有用户,默认创建一个
                if (!$user) {
                    $user = User::create([
                        'name' => $oauthUser->getNickname(),
                        'avatar' => $oauthUser->getAvatar(),
                        'weixin_openid' => $oauthUser->getId(),
                        'weixin_unionid' => $unionid,
                    ]);
                }
                break;
        }
        $token = \Auth::guard('api')->fromUser($user);
        return $this->responseWithToken($token)->serStatusCode(201);
    }

    /**
     * 微信小程序登陆
     */
    public function weappStore(WeappAuthorizationRequest $request)
    {
        $code = $request->code;

        // 根据code获取微信的openid 和 session_key
        $miniProgram = \EasyWeChat::miniProgram();
        $data = $miniProgram->auth->session($code);

        // 如果返回的信息中包含错误码,说明code过期或者不正确 返回401错误
        if (isset($data['errcode'])) {
            return $this->response->errorUnauthorized('code 不正确');
        } 

        // 找到openid 对应的用户
        $user = User::where('weapp_openid',$data['openid'])->first();

        $attributes['weixin_session_key'] = $data['session_key'];

        //未找到用户 就需要使用提交的用户名密码进行绑定
        if (!$user) {
            // 如果未提交用户名密码. 403提示
            if (!$request->username) {
                return $this->response->errorForbidden('用户不存在');
            } 

            $username = $request->username;

            // 用户名可以是邮箱 也可以是手机号
            filter_var($username , FILTER_VALIDATE_EMAIL) ? 
                $credentials['email'] = $username :
                $credentials['phone'] = $username;

            $credentials['password'] = $request->password;

            // 验证用户名和密码是否正确
            if (!Auth::guard('api')->once($credentials)) {
                return $this->response->errorUnauthorized('用户名或者密码错误');
            }

            // 获取对应的用户
            $user = Auth::guard('api')->getUser();
            $attributes['weapp_openid'] = $data['openid'];
        }

        // 更新用户信息
        $user->update($attributes);

        //为对应的用户创建jwt
        $token = Auth::guard('api')->fromUser($user);

        return $this->responseWithToken($token)->setStatusCode(201);
    }

    /**
     * 用户登录
     * @param AuthorizationRequest $request
     */
    public function store(AuthorizationRequest $request)
    {
        $username = $request->username;
        filter_var($username, FILTER_VALIDATE_EMAIL) ?
            $credentials['email'] = $username :
            $credentials['phone'] = $username;
        $credentials['password'] = $request->password;

        if(!$token = \Auth::guard('api')->attempt($credentials)){
            return $this->response->errorUnauthorized(trans('auth.failed'));
        }
        return $this->responseWithToken($token)->setStatusCode(201);
    }

    /**
     * 返回固定格式的token信息
     * @param $token
     * @return mixed
     */
    protected function responseWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }

    public function update()
    {
        $token = \Auth::guard('api')->refresh();
        return $this->responseWithToken($token);
    }

    public function delete()
    {
        \Auth::guard('api')->logout();
        return $this->response->noContent();
    }
}
