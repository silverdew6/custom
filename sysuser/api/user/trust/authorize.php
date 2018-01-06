<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2015 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class sysuser_api_user_trust_authorize
{
    public $apiDescription = "";
    public function getParams()
    {
        $return['params'] = array(
            'flag' => ['type'=>'string', 'default'=>'', 'example'=>'', 'description'=>'信任登陆标识'],
            'view' => ['type'=>'string','valid'=>'required|in:wap,web', 'default'=>'web', 'example'=>'', 'description'=>'显示视图'],
            'state' => ['type'=>'string','valid'=>'required', 'default'=>'', 'example'=>'', 'description'=>'状态码'],
            'redirect_uri' => ['type'=>'string','valid'=>'required', 'default'=>'', 'example'=>'', 'description'=>'redirect_uri地址'],
            'params' => ['type'=>'string','valid'=>'required', 'default'=>'', 'example'=>'', 'description'=>'oauth callback参数'],
        );
        return $return;
    }

    public function authorize($params)
    {
        $flag = $params['flag'];
        $view = $params['view'];
        $state = $params['state'];
        $redirectUri = $params['redirect_uri'];
        $trustManager = kernel::single('sysuser_passport_trust_trust');
        $trust = $trustManager->make($flag);
        $trust->setView($view);
        $trust->setCallbackUrl($redirectUri);
        $subparam = isset($params['params']) ? json_decode($params['params'], 1) : "";
        
        //原来的代码是   if( $userFlag = $trust->authorize($state,  $subparam)) ....
        //debug = $userFlag = '5c7ad3ba35f10c6778f19e3239e17293'
        //$userFlag = '5c7ad3ba35f10c6778f19e3239e17294';
        $userFlag = $trust->authorize($state,$subparam);// 微信授权登录操作
        if ($userFlag && !empty($userFlag)) {
        	//var_dump($userFlag);exit;
            if ($userId = $trustManager->binded($userFlag))
            {
                $res = ['binded' => true, 'user_id' => $userId];
            }
            else
            {
            	$test = array('openid' => 'hongoodooo', 'access_token' => 'dddd', 'nickname' => '手写佳话', 'figureurl' =>  'ddddd');
            	$res = ['binded' => false, 'user_info' => $test,  'user_flag' => $userFlag];
                //$res = ['binded' => false, 'user_info' => $trust->getUserInfo(),  'user_flag' => $userFlag];
            }
            //$res['luckymall_expires_in'] = time() + 60 * 20;
            return $res;
        }
        throw new \ErrorException(app::get('sysuser')->_('验证失败'));
    }
}
