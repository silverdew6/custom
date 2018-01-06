<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topm_ctl_passport extends topm_controller
{
	public $allOffType = null;
    public function __construct()
    {
        parent::__construct();
        kernel::single('base_session')->start();
        $this->setLayoutFlag('cart');
        $this->passport = kernel::single('topm_passport');
        $this->allOffType = array(
			0 => array("act_code"=> "ACT23434","name"=> "10元现金券", "money"=> 10.00 ,"start_time"=> "2016-10-01 00:00:01", "end_time"=> "2016-10-16 23:59:59"  ),
			1 => array("act_code"=> "ACT44332","name"=> "20元现金券", "money"=> 20.00 ,"start_time"=> "2016-10-01 00:00:01", "end_time"=> "2016-10-16 23:59:59"  ),
			2 => array("act_code"=> "ACT23442","name"=> "30元现金券", "money"=> 30.00 ,"start_time"=> "2016-10-01 00:00:01", "end_time"=> "2016-10-16 23:59:59"  ),
		);
    }

    public function signin()
    {
        $next_page = $this->__getFromUrl();
        if (kernel::single('pam_trust_user')->enabled())
        {
            $trustInfoList = kernel::single('pam_trust_user')->getTrustInfoList('wap', 'topm_ctl_trustlogin@callBack');
        }

        $isShowVcode = userAuth::isShowVcode('login');
        $uname = $_COOKIE['LOGINNAME'];
        return $this->page('topm/passport/signin/signin.html', compact('trustInfoList','isShowVcode','next_page','uname'));
    }
    
    /**
     * 跳转到微信的授权登录
     */
    function _red_weixin( $next_url = ""){
    	//商城域名 ： $http_host = "http://www.myjmall.com"; //正式
        $http_host = app::get('sysconf')->getConf('site.sitehost');
        if(!$http_host) { $http_host = "http://www.mjm.net"; }
        $trust = kernel::single('sysuser_passport_trust_manager')->getTrustObjectByFlag("wapweixin");
        $wxsetting = $trust->getSetting();
        $appid = ($wxsetting && trim($wxsetting["appKey"])!="") ? trim($wxsetting["appKey"]) : "wxe55aa19f35f85ca7";
        //wxe55aa19f35f85ca7 _测试账号测试 wx06db345d9bee6589  ———— c598082c783270a56fa7cfae7bc35eed
		$redirect_uri = $http_host."/wap/to-pc.html";
		$scope = "snsapi_userinfo";
		$state = isset($next_url) && trim($next_url)!="" ? trim($next_url) :""; //返回URL
		$goHosturl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
		header("Location:{$goHosturl}");exit;
    }

    public function signup()
    {
    	//判断如果是微信浏览器；
	    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
	    	 $this->_red_weixin();
	    }  
        //如果已登录则跳转到退出页
        if( userAuth::check() ) $this->logout();
        $pagedata['next_page'] = $this->__getFromUrl();
        return $this->page('topm/passport/signup/signup.html', $pagedata);
    }

    public function license()
    {
        $pagedata['title'] = "用户注册协议";
        $licence = app::get('sysuser')->getConf('sysconf_setting.wap_license');
        if($licence)
        {
            $pagedata['license'] = $licence;
        }
        else
        {
            $pagedata['license'] = app::get('sysuser')->getConf('sysuser.register.setting_user_license');
        }
        return $this->page('topm/passport/signup/license.html', $pagedata);
    }
    
    /**
	 * 微信授权登录绑定手机号和密码
	 * topm_ctl_passport@wapwechat
	 * 异步进入微信授权业务。
	 * 流程： 获取token 和 appid 
	 */
    public function wechat()
    {
		/*$data = array("openid"=>"oM3q6wdiCldPMQIUGjx8EV_JkcvM","nickname"=>"xxvvvvx");
    	$testdata = base64_encode(json_encode($data));*/
    	$pagedata['title'] = "微信授权绑定用户";
    	$wd = isset($_REQUEST["w"]) ? trim($_REQUEST["w"]): false;
    	$n_uri = isset($_REQUEST["rui"]) ? trim($_REQUEST["rui"]): "";
    	if($wd && strtoupper($wd)=="LOGIN"){ //进入微信授权页面
    		 $this->_red_weixin($n_uri);
    	}
    	$wdata = isset($wd) ? (array) json_decode(base64_decode($wd)) : false ;
    	if(isset($wdata) && !empty($wdata["openid"])){
    		//第一次登录，绑定手机号，及密码操作； 数据带过来，
	        try {
	        	if( userAuth::check() ) $this->logout();
		        $pagedata['userbase_data'] = base64_encode(serialize($wdata));
		        //$pagedata['next_page'] = $this->__getFromUrl();
		        //$pagedata['license'] = app::get('sysuser')->getConf('sysuser.register.setting_user_license');
		        return $this->page('topm/passport/wechat/wechat_user.html', $pagedata);
	        } catch( Exception $e ) {
				$msg = $e->getMessage();
				$url = url::action('topm_ctl_passport@signin');
            	return $this->splash('error',$url,$msg,true);
			}
    	}else{
    		header("Location:404.html");
    	}
    }
    
    /**
     * 微信授权新用户开户绑定
     */
    function wechatUser(){
    	$postdata = utils::_filter_input(input::get());
    	unset($_SESSION["offer_coupon_sendsuccess"]);
    	try {
    		if(isset($postdata["userbase_data"]) && !empty($postdata["userbase_data"])){
	    		$mycode = unserialize( base64_decode($postdata["userbase_data"]) );
	    		$phonenumber = isset($postdata["pam_user"]["account"]) ? trim ($postdata["pam_user"]["account"]) : false;
	    		$pwd1 = isset($postdata["pam_user"]["password"]) ? trim ($postdata["pam_user"]["password"]) : false;
	    		$pwd2 = isset($postdata["pam_user"]["pwd_confirm"]) ? trim ($postdata["pam_user"]["pwd_confirm"]) : false;
	    		
	    		$vcode = isset($postdata["vcode"]) ? trim ($postdata["vcode"]) : false;
	    		if(!$vcode){
	    			 throw new \LogicException('验证码不能为空');
	    		}else{
	    			//短信验证
	    			$vcodeData=userVcode::verify($vcode,$phonenumber,"signup"); 
	    			if(!$vcodeData) {
	                	throw new \LogicException('验证码输入错误');
	            	} 
	    		}
    			// 传值过来的用户要有效
				if(!$mycode|| !isset($mycode["openid"]) || trim($mycode["openid"]) == ""){  
					throw new \LogicException('无效的授权信息');
				} 
	    		// 必须用手机号码注册
				if(!$phonenumber || !preg_match("/^1[34578]{1}\d{9}$/",$phonenumber)){  
					throw new \LogicException('请提供有效的手机号');
				}   
	    		$uopen_id =  isset($mycode["openid"]) ? trim($mycode["openid"]) : "";
				$uwxnick = isset($mycode["nickname"]) ? trim($mycode["nickname"]) : "";
				$_SESSION['openid'] = $uopen_id;
				$is_eq_opid = $_SESSION['openid'] == trim($mycode["openid"]) ? true : false;
				if(isset($_SESSION['openid'])&& !empty($_SESSION['openid']) && true){
					$userId = userAuth::weixinsignUp($phonenumber, $pwd1, $pwd2,$uopen_id);
					if($userId){
						//补充一下用户资料；昵称
						userAuth::login($userId, $phonenumber); //登录
						//给用户发券
						userAuth::initoffenCoupon($userId, false); 
						$url = url::action('topm_ctl_member@index'); //$this->__getFromUrl();
	        			return $this->splash('success', $url, null, true);
					}else{
						throw new \LogicException('绑定用户失败');
					}
				}else{
	             	throw new \LogicException('授权用户不匹配');
				}
    		}else{
    			throw new \LogicException('无效请求');
    		}
    	}
		catch( Exception $e ) {
			$msg =  $e->getMessage();
			return $this->splash('error',null,$msg,true); //提示异常
		}
    }

    //登陆
    public function login()
    {
        $verifycode = input::get('verifycode');
        $next_url = input::get('next_page'); //跳到下一个地址页面
        if(!$next_url || trim($next_url) == "" || strpos($next_url,"passport-signin.html")>0){
        	$next_url = $this->__getFromUrl(); //上一个地址
        }
        if( userAuth::isShowVcode('login') )
        {
            if( !input::get('key') || empty($verifycode) || !base_vcode::verify(input::get('key'), $verifycode))
            {
                $msg = app::get('topm')->_('验证码填写错误');
                return $this->splash('error',$next_url,$msg,true);
	        }
        }
        try
        {
            userAuth::syncCookieWithLoginName(input::get('remember_name'), input::get('account'));
            if (userAuth::attempt(input::get('account'), input::get('password'),true))
            {
            	//登录成功，送UID给线下优惠券
            	userAuth::initoffenCoupon(userAuth::id(),false); //不提示
            	//$url = $this->__getFromUrl();
                return $this->splash('success',$next_url,$msg,true);
            }
        }
        catch(Exception $e)
        {
            userAuth::setAttemptNumber();
            if( userAuth::isShowVcode('login') )
            {
                $url = url::action('topm_ctl_passport@signin');
            }
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg,true);
        }
    }

    //注册
    public function create()
    {
        $data = utils::_filter_input(input::get());
        $vcode = $data['vcode'];
        $codyKey = $data['key'];
        $verifycode = $data['verifycode'];
        $userInfo = $data['pam_user'];

        try
        {
			//$postData['card_id']  验证身份证 2016/3/20
			if(!$this->check_identity($userInfo['usercard']))
			{
			throw new \LogicException(app::get('topc')->_('身份证格式不正确'));
			}

            //$accountType = kernel::single('pam_tools')->checkLoginNameType($userInfo['account']);

            //检测注册协议是否被阅读选中
            if(!input::get('license'))
            {
                throw new \LogicException(app::get('topm')->_('请阅读并接受会员注册协议'));
            }
			if(isset($_SESSION['openid'])&&!empty($_SESSION['openid'])){
			$userId = userAuth::weixinsignUp($userInfo['account'], $userInfo['password'], $userInfo['pwd_confirm']);
			}else{
            $userId = userAuth::signUp($userInfo['account'], $userInfo['password'], $userInfo['pwd_confirm'],$userInfo['usercard'],$userInfo['username']);//2016/5/16 身份证和真实姓名
			}	
            userAuth::login($userId, $userInfo['account']);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg,true);
        }

        $url = $this->__getFromUrl();
        return $this->splash('success', $url, null, true);
    }
    //退出
    public function logout()
    {
        userAuth::logout();
        return redirect::action('topm_ctl_default@index');
    }

    //检查是否已经注册
    public function checkLoginAccount()
    {
        $signAccount = utils::_filter_input(input::get());
        try
        {
            $loginName = $signAccount['pam_user']['account'];
			//2016/5/15 雷成德 必须用手机号码注册
			if(!preg_match("/^1[34578]{1}[0-9]{9}$/",$loginName)){ 
				  throw new \LogicException('请提供有效的手机号');
			}
            $data = userAuth::getAccountInfo($loginName);
            
            if($data && isset($data["user_id"]) && intval($data["user_id"])>0){
                if(isset($data["openid"]) && !empty($data["openid"])){
                	throw new \LogicException('该手机号已绑定其它微信号');
                }
            }
            $json['alerady_uid']=intval($data["user_id"]);
            //$json['needVerify'] = kernel::single('pam_tools')->checkLoginNameType($loginName);
            $json['needVerify'] = app::get('topm')->rpcCall('user.get.account.type',array('user_name'=>$loginName),'buyer');
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
        return response::json($json);
    }

    //前端注册验证码的发送
    public function sendVcode()
    {
        $postData = utils::_filter_input(input::get());
        //echo '<pre>';print_r($postData);exit();
        //验证码发送之前的判断
        if(isset($postData['verifycode']) && trim($postData['verifycode'])!="" )
        {
            $valid = validator::make(
                [$postData['verifycode']],['required']
            );
            if($valid->fails())
            {
                return $this->splash('error',null,"图片验证码不能为空!");
            }
            if(!base_vcode::verify($postData['verifycodekey'],$postData['verifycode']))
            {
                return $this->splash('error',null,"图片验证码错误!");
            }

        }

        //$accountType = kernel::single('pam_tools')->checkLoginNameType($postData['uname']);
        $accountType = app::get('topm')->rpcCall('user.get.account.type',array('user_name'=>$postData['uname']),'buyer');
        try
        {
            $this->passport->sendVcode($postData['uname'],$postData['type']);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
        if($accountType == "email")
        {
            return $this->splash('success',null,"邮箱验证链接已经发送至邮箱，请登录邮箱验证");
        }
        else
        {
            return $this->splash('success',null,"验证码发送成功");
        }
    }

            //找回密码第一步
    public function findPwd()
    {
        return $this->page('topm/passport/forgot/forgot.html');
    }

    //找回密码第二步
    public function findPwdTwo()
    {
        $postData = utils::_filter_input(input::get());
        if($postData)
        {
            $loginName = $postData['username'];
            $data = userAuth::getAccountInfo($loginName);
            if($data)
            {
                if( (!empty($data['email']) && $data['email_verify']) || !empty($data['mobile']))
                {
                    $send_status = 'true';
                }
                else
                {
                    $send_status = 'false';
                }
                $pagedata['send_status'] = $send_status;
                $pagedata['data'] = $data;
                return view::make('topm/passport/forgot/two.html', $pagedata);
            }
        }
        $url = url::action('topm_ctl_passport@findPwd');
        $msg = app::get('topm')->_('账户不存在');
        return $this->splash('error',$url,$msg);
    }

    //找回密码第三步
    public function findPwdThree()
    {

        $postData = utils::_filter_input(input::get());
        $vcode = $postData['vcode'];
        $loginName = $postData['uname'];
        $sendType = $postData['type'];
        //$accountType = kernel::single('pam_tools')->checkLoginNameType($loginName);
        $accountType = app::get('topm')->rpcCall('user.get.account.type',array('user_name'=>$loginName),'buyer');
        try
        {
            $vcodeData=userVcode::verify($vcode,$loginName,$sendType);
            if(!$vcodeData)
            {
                throw new \LogicException('验证码输入错误');
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
        $userInfo = userAuth::getAccountInfo($loginName);
        $key = userVcode::getVcodeKey($loginName ,$sendType);
        $userInfo['key'] = md5($vcodeData['vcode'].$key.$userInfo['user_id']);

        $pagedata['data'] = $userInfo;
        $pagedata['account'] = $loginName;
        if($accountType == "email")
        {
            return $this->page('topm/passport/forgot/email_three.html', $pagedata);
        }
        else
        {
            return $this->page('topm/passport/forgot/three.html', $pagedata);
        }
    }
    //找回密码第四步
    public function findPwdFour()
    {
        $postData = utils::_filter_input(input::get());
        $userId = $postData['userid'];
        $account = $postData['account'];

        $vcodeData = userVcode::getVcode($account,'forgot');
        $key = userVcode::getVcodeKey($account,'forgot');

        if($account !=$vcodeData['account']  || $postData['key'] != md5($vcodeData['vcode'].$key.$userId) )
        {
            $msg = app::get('topm')->_('页面已过期,请重新找回密码');
            return $this->splash('failed',null,$msg,true);
        }

        $data['type'] = 'reset';
        $data['new_pwd'] = $postData['password'];
        $data['user_id'] = $postData['userid'];
        $data['confirm_pwd'] = $postData['confirmpwd'];
        try
        {
            app::get('topm')->rpcCall('user.pwd.update',$data,'buyer');

        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topm_ctl_passport@findPwd');
            return $this->splash('error',$url,$msg,true);
        }
        $msg = "修改成功";
        $url = url::action('topm_ctl_passport@login');
        return $this->splash('success',$url,$msg,true);
    }

    private function __getFromUrl()
    {
        $url = utils::_filter_input(input::get('next_page', request::server('HTTP_REFERER')));
        $validator = validator::make([$url],['url'],['数据格式错误！']);
        if ($validator->fails())
        {
            return url::action('topc_ctl_default@index');
        }
        if( !is_null($url) )
        {
            if( strpos($url, 'passport') )
            {
                return url::action('topm_ctl_default@index');
            }
            return $url;
        }else{
            return url::action('topm_ctl_default@index');
        }
    }
	function check_identity($id='')
	{
	    $set = array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
	    $ver = array('1','0','x','9','8','7','6','5','4','3','2');
	    $arr = str_split($id);
	    $sum = 0;
	    for ($i = 0; $i < 17; $i++)
	    {
	        if (!is_numeric($arr[$i]))
	        {
	            return false;
	        }
	        $sum += $arr[$i] * $set[$i];
	    }
	    $mod = $sum % 11;
	    if (strcasecmp($ver[$mod],$arr[17]) != 0)
	    {
	        return false;
	    }
	    return true;
	}



}
