
<?php
use GuzzleHttp\Exception\ClientException;
class sysuser_plugin_weixin extends sysuser_plugin_abstract implements sysuser_interface_trust
{


    public $name = '微信登陆';
    public $flag = 'weixin';
    public $version = '2.0';
    public $platform = ['web'];


    public $authUrls = ['web' => ['authorize' => 'https://open.weixin.qq.com/connect/qrconnect',
                                  'token' => 'https://api.weixin.qq.com/sns/oauth2/access_token',
                                  'userinfo' => 'https://api.weixin.qq.com/sns/userinfo']];
    protected $tmpOpenId = null;

    /**
   * 获取plugin autherize url
   *
   * @param string $state
   * @return string
   */
    public function getAuthorizeUrl($state)
    {
        return $this->getUrl('authorize').'?'.http_build_query(['appid' => $this->getAppKey(),
                                                                 'redirect_uri' => $this->getCallbackUrl(),
                                                                 'response_type' => 'code',
                                                                 'scope' => 'snsapi_login',
                                                                 'state' => $state]);
    }

    /**
   * 通过调用信任登陆accesstoken接口生成access token
   *
   * @param string $code
     * @return string
   */
    public function generateAccessToken($code)
    {
        try {
        	$args = ['appid' => $this->getAppKey(), 'secret'=> $this->getAppSecret(),  'grant_type' => 'authorization_code', 'code' => $code];
            $msg = client::get($this->getUrl('token'), ['query' => $args])->json();
            $errorcode = isset($msg["errcode"]) ? intval($msg["errcode"]) :-1;
        }
        //ClientException
        catch (ClientException $e) {
            $msg = $e->getResponse()->json();
            throw new \LogicException("error :" . $msg['errcode']. "msg  :". $msg['errmsg']);
        }
		if(isset($msg) && intval($msg["errcode"]) === 0){  //请求成功
			 $this->tmpOpenId = $msg['openid'];
		}else{
			$msgg = $errorcode >0 ? " [$errorcode]－". $msg['errmsg'] : "系统繁忙，请稍候再试";
			throw new \LogicException("ERROR:"  .$msgg );
		}
        return $msg['access_token'];
    }



    public function generateOpenId()
    {
        return $this->tmpOpenId;
    }

    public function generateUserInfo()
    {
        $args = ['access_token' => $this->getAccessToken(),
                 'openid' => $this->tmpOpenId];
        $msg = client::get($this->getUrl('userinfo'), ['query' => $args])->json();


        if($msg['errcode']) throw new \LogicException(app::get('sysuser')->_('参数错误！'));

        return $this->convertStandardUserInfo($msg);
    }

    protected function convertStandardUserInfo($trustUserInfo)
    {
        return $userInfo = ['openid' => $this->tmpOpenId,
                            'access_token' => $this->getAccessToken(),
                            'nickname' => $trustUserInfo['nickname'],
                            'figureurl' => $trustUserInfo['headimgurl']];
    }

}