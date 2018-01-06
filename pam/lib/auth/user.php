<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2015 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class pam_auth_user
{
    const appId = 'pam';

    protected $userInfo = null;

    protected $accountInfo = null;

    protected $pamAccount = null;

    //登录是否显示验证码登录次数最大值
    protected $loginShowVcodeMaxNumber = 3;


	/**
	 * The user Id we last attempted to retrieve.
	 *
	 * @var \Illuminate\Contracts\Auth\Authenticatable
	 */
    protected $lastAttemptedUserId;

    public function __construct()
    {
        $this->pamAccount = kernel::single('pam_account', 'sysuser');
        $this->syncCookieWithUserName($this->getLoginName());
    }

	/**
	 * 检测用户是否登陆
	 *
	 * @return bool
	 */
    public function check()
    {
        return $this->pamAccount->check();
    }

	/**
	 * 检测用户是否未登陆
	 *
	 * @return bool
	 */
    public function guest()
    {
        return !$this->check();
    }

	/**
	 * 获取用户真实姓名
	 *
	 * @return bool
	 */
    public function getUserName()
    {
        $this->initUserInfo();
        return $this->userInfo['username'];
    }

	/**
	 * 获取用户昵称
	 *
	 * @return bool
	 */
    public function getNickName()
    {
        $this->initUserInfo();
        return $this->userInfo['name'];
    }

	/**
	 * 获取用户登陆名
	 *
	 * @return bool
	 */
    public function getLoginName()
    {
        return $this->pamAccount->getLoginName();
    }


	/**
	 * 获取用户ID
	 *
	 * @return bool
	 */
    public function id()
    {
        return $this->pamAccount->getAccountId();
    }

	/**
	 * 获取用户ID. id()的别名
	 *
	 * @return bool
	 */
    public function getAccountId()
    {
        return $this->id();
    }

	/**
	 * 获取用户ID. id()的别名
	 *
	 * @return bool
	 */
    protected function initUserInfo()
    {
        if (!$this->userInfo) $this->userInfo = app::get(pam_auth_user::appId)->rpcCall('user.get.info', ['user_id'=>$this->id()], 'buyer');
        return true;
    }

	/**
	 * 通过账号名获取账号基本信息
	 *
     * 目前获取 user_id,email,mobile,email_verify
	 *
	 * @param string $loginName
	 * @return bool
	 */
    public function getAccountInfo($loginName)
    {
        return app::get(pam_auth_user::appId)->rpcCall('user.get.account.info', ['user_name' => $loginName], 'buyer');
    }

	/**
	 * 获取用户ID. id()的别名
	 *
	 * @return bool
	 */
    public function getUserInfo()
    {
        if (!$this->userInfo) $this->initUserInfo();
        return $this->userInfo;
    }

	/**
	 * 设置cookie
	 *
	 * @return bool
	 */
    protected function setCookie($name, $value, $expire=false, $path)
    {
        $path = $path ?: kernel::base_url().'/';
        $life = 315360000;
        $expire = $expire === false ? time() + $life : $expire;
        setcookie($name, $value, $expire, $path);
        $_COOKIE[$name] = $value;
        return true;
    }

	/**
	 * 同步用户名cookie
	 *
	 * @return bool
	 */
    protected function syncCookieWithUserName($loginName = '')
    {
        $sessionExpires =  kernel::single('base_session')->get_sess_expires();
        $expire = time() + $sessionExpires*60;
        return $this->setCookie('UNAME', $loginName, $expire);
    }

    public function syncCookieWithLoginName($isRemember = 'on', $loginName = '')
    {
        if( $isRemember == 'on' )
        {
            return $this->setCookie('LOGINNAME', $loginName);
        }
        else
        {
            return $this->setCookie('LOGINNAME', $loginName, time()-3600 );
        }
    }

	/**
	 * 同步购物车数量cookie
	 *
	 * @param  string  $key
	 * @return bool
	 */
    public function syncCookieWithCartNumber($cartNumber)
    {
        return $this->setCookie('CARTNUMBER', $cartNumber);
    }

    public function syncCookieWithCartVariety($cartVariety)
    {
        return $this->setCookie('CARTVARIETY', $cartVariety);
    }

	/**
	 * 验证用户
	 *
	 * @param string $logingName
	 * @param string $password
	 * @return bool
	 */
    public function validate($loginName, $password)
    {
        return $this->attempt($loginName, $password, false);
    }

	/**
     * 尝试验证登陆
     *
     * 如果$login参数设置为true, 则验证通过后进行登陆
	 *
	 * @param string $logingName
	 * @param string $password
	 * @param bool $login
	 * @return bool
	 */
    public function attempt($loginName, $password, $login = true,& $userId = 0 )
    {
        $userId = app::get(pam_auth_user::appId)->rpcCall('user.login',
                                                          ['user_name' => $loginName, 'password' => $password]);
		//2015/12/19 雷成德当手机登录的时候微信增加绑定openid，绑定PC端账户。
		if(isset($_SESSION['openid'])&&!empty($_SESSION['openid'])){
			 $params['openid'] = $_SESSION['openid'];
              app::get('sysuser')->model('account')->update($params, ['user_id'=>$userId]);
		}
        $this->lastAttemptedUserId = $userId;
        if($login) $this->login($userId, $loginName);
        $this->clearAttemptNumber();
        return true;
    }


    /**
     * @brief 设置当前登录是否为下次自动登录，自动登录有效期为7天
     *
     * @param $remember on 表示自动登录
     *
     * @return $this
     */
    public function setAttemptRemember($remember=null)
    {
        $this->pamAccount->setAttemptRemember($remember);
        return $this;
    }

    public function getAttemptRemember()
    {
        return $this->pamAccount->getAttemptRemember();
    }

    /**
     * 是否显示验证码
     *
     * @param $type 是否显示验证码类型 登录|注册
     *
     * @return bool true 需要显示验证码 false 不需要显示
     */
    public function isShowVcode($type='login')
    {
        $number = $this->getAttemptNumber();
        return $number >= $this->loginShowVcodeMaxNumber ? true : false;
    }

    /**
     * 获取用户登录验证次数
     */
    private function getAttemptNumber()
    {
        $number = $_SESSION['account'][$this->pamAccount->getAuthType()]['error_number'];
        return $number ? $number : 0;
    }

    /**
     * 设置用户登录验证次数
     */
    public function setAttemptNumber()
    {
        $number = $this->getAttemptNumber() + 1;
        if( $number <= $this->loginShowVcodeMaxNumber )
        {
            $_SESSION['account'][$this->pamAccount->getAuthType()]['error_number'] = $number;
        }
        return true;
    }

    public function clearAttemptNumber()
    {
        unset($_SESSION['account'][$this->pamAccount->getAuthType()]['error_number']);
        return true;
    }

    /**
	 * 获取最后验证的用户USER ID
	 *
	 * @return misc
	 */
    public function getLastAttemptedUserId()
    {
        return $this->lastAttemptedUserId;
    }

    /**
	 * 用户登陆进入系统
	 *
	 * @param int
	 * @param string $password
	 * @return bool
	 */
    public function login($userId, $loginName = null)
    {
        if (!$loginName)
        {
            $loginName = current(app::get(pam_auth_user::appId)->rpcCall('user.get.account.name', ['user_id' => $userId], 'buyer'));
        }
        $_SESSION['openid']="";  //注销openid
        $this->pamAccount->setSession($userId, $loginName);
        $this->syncCookieWithUserName($this->getLoginName());

        event::fire('user.login', [$userId, $loginName]);
        return true;
    }

	/**
	 * 设置登录名
	 *
	 * @param  string  $userName
	 * @return bool
	 */
    public function updateLoginName($loginName)
    {
        $params = array(
            'user_id' => $this->id(),
            'user_name' => $loginName,
        );

        if (app::get('pam')->rpcCall('user.account.update', $params, 'buyer') ? true : false)
        {
            $this->syncCookieWithUserName($loginName);
        }
    }

    /**
	 * 用户登出系统
	 *
	 * @param int
	 * @param string $password
	 * @return bool
	 */
    public function logout()
    {
        $this->pamAccount->logout();
        $this->syncCookieWithUserName();

        event::fire('user.logout');
    }

    public function signUp($loginName, $password, $confirmedPassword, $usercard,$username)
    {
        return app::get('pam')->rpcCall('user.create',
                                         ['account' => $loginName,
                                          'password' => $password,
                                          'pwd_confirm' => $confirmedPassword,
			                              'usercard' => $usercard,//身份证 雷成德2016/5/16
			                              'username' => $username, //真实姓名 雷成德2016/5/16
                                          'reg_ip' => request::getClientIp()
                                         ],
                                         'buyer');
    }
    //雷成德手动新增一个方法，负责收集端存储openid
    public function weixinsignUp($loginName, $password, $confirmedPassword,$new_open_id = "")
    {
    	$info = $this->getAccountInfo($loginName);
    	if($info && intval($info["user_id"])>0){
    		$aredyUid =  intval($info["user_id"]);
    		if(isset($info["openid"]) && !empty($info["openid"])){  //其它用户使用了
		   		throw new \LogicException('该手机号已绑定其它微信号');
		   		return false;
       		}else{
    			//已开户但没有绑定微信
        		$newparams = array('user_id'=> $aredyUid,'openid' => $new_open_id);
        		$accMdl = app::get('sysuser')->model('account');
				if(!$res = $accMdl->save($newparams)){
					throw new \LogicException('绑定失败');
					return false;
				}
    		}
    		return $aredyUid;
    	}else{
    		return app::get('pam')->rpcCall('user.create',
    			['account' => $loginName,
    			'password' => $password,
    			'pwd_confirm' => $confirmedPassword,
    			'openid' =>$_SESSION['openid'],
    			'reg_ip' => request::getClientIp()
    			],
    			'buyer');
    	}
    }
    /**
     * 登录成功，送UID给线下优惠券
     * 发放线下优惠券的操作
     * @param  $user_Id 用户ID
     * @param  $is_exception 是否要抛出异常信息
     * @return true 领取券成功 否则false
     */
    public function initoffenCoupon($user_Id , $is_exception = true ){
    	if(!$user_Id)return false ;
    	unset($_SESSION["offer_coupon_sendsuccess"]);
    	$cpoffenMdlOffen = app::get('sysuser')->model('cpoffen');//getOffenCoupon(488);
    	//先判断是否还有可以领取的优惠券  >0    	
    	$keyongNum = 1;
    	if($keyongNum > 0){
    		//先判断是否已经领取过券
	    	$filter = array('user_id' => $user_Id);
			$count_num = $cpoffenMdlOffen->count($filter);
			if($count_num > 0 ){ //已领
				if($is_exception){
					throw new \LogicException(app::get('sysuser')->_('当前用户已领取券'));
				}
			}else{ 	//领券
				//配置成在28号早上切换成用户领取已有券，领完为止，——不再使用新创建券。
				if(time() >= strtotime("2016-09-29 10:00:00")){
					$quan_num = $this->_reciveOffenCoupon($user_Id); //领取一张线下券
				}else{
					$quan_num = $this->__createOffenCoupon($user_Id); //生成线下券
				}			
				//$quan_num = $this->__createOffenCoupon($user_Id); //生成 或随机领取一张线下券
				if($quan_num > 0){
					$_SESSION["offer_coupon_sendsuccess"] = true ; //新优惠券提醒
					return true;
				}else{
					if($is_exception){
						throw new \LogicException(app::get('sysuser')->_('领取失败'));
					}
				}
			}
    	}else{ //没有券，不送
    	}
    	return false; 
    }
    
    /**
     * 用户领取一张优惠券，用来核销
     * 去领券，有券就领，没有就放弃领取
     * */
    function _reciveOffenCoupon($user_id){
    	if(!$user_id)return false;
    	$userOffenAdl = kernel::single('sysuser_data_user_offen');
    	//去数据库里把所有可以用的券给查询出来，并且随机取一张
    	return $userOffenAdl -> sendCouponTo($user_id ,false ,false );
    }
    
    /**
	* 生成一张优惠券码
	* @param $user_id ,用户ID
	* @param  $is_exception 是否要抛出异常信息
	* @return true or false
	*/
	public function __createOffenCoupon($user_id , $data = false,$is_exception =true )
	{
		    $coupontype = false;
		    $allOffType = array(
				0 => array("act_code"=> "ACT23434","name"=> "10元现金券", "money"=> 10.00 ,"start_time"=> "2016-10-01 00:00:01", "end_time"=> "2016-10-16 23:59:59"  ),
				1 => array("act_code"=> "ACT44332","name"=> "20元现金券", "money"=> 20.00 ,"start_time"=> "2016-10-01 00:00:01", "end_time"=> "2016-10-16 23:59:59"  ),
				2 => array("act_code"=> "ACT23442","name"=> "30元现金券", "money"=> 30.00 ,"start_time"=> "2016-10-01 00:00:01", "end_time"=> "2016-10-16 23:59:59"  ),
			);
			do{
				$rand_number =  rand(0,2);//获取券的类别：
				$coupontype = $allOffType[$rand_number];
			}while(empty($coupontype));
			// 生成优惠券
			$coupon_sn = $this->_createCSn($user_id,$coupontype);
			//生成优惠券的二维码
			$couponQrcMdl  = kernel::single('topm_couponqrc');
			$cfile_qrc_path = "";
        	$cfile_qrcc = $couponQrcMdl -> createQrc($coupon_sn,$cfile_qrc_path); 
        	if($cfile_qrc_path){
        		$getCData["qrc_path"]  = $cfile_qrc_path;
        	}
			$getCData = array("shopid"=> 10000,"coupon_code"=> $coupon_sn, "user_id"=>$user_id ,"get_time"=> time(), "status"=> 0 );
			$getCData["active_code"] = isset($coupontype["act_code"]) ?  trim($coupontype["act_code"]) :"";
			$getCData["coupon_name"] = isset($coupontype["name"]) ?  trim($coupontype["name"]) :"";
			$getCData["coupon_amount"] = isset($coupontype["money"]) ?  floatval($coupontype["money"]) :0.00;
			$getCData["use_starttime"] = isset($coupontype["start_time"]) ?  strtotime($coupontype["start_time"]) :0;
			$getCData["use_endtime"] = isset($coupontype["end_time"]) ?  strtotime($coupontype["end_time"]) :0;
			$cpoffenMdlOffen = app::get('sysuser')->model('cpoffen');//getOffenCoupon(488);
			$result_num = $cpoffenMdlOffen->insert($getCData);
			if(!$result_num){
				return false;
			}
			return $result_num ;
	}


	/**2016/9/14 xch 
	 *  生成券码
	 * return boolean
	 */
	
	function _createCSn($userid , $cdtype = null)
	{
		$cdtype_code = (isset($cdtype) && !empty($cdtype["act_code"])) ? $cdtype["act_code"] : "OFN"; 
		//取用户ID的末两位；
		$secondchar = strlen("{$userid}")>3 ?  substr("{$userid}",-3) : "{$userid}";
		//取用户ID的末两位；
		$firstchar = strlen($cdtype_code)>3 ?  substr("{$cdtype_code}",0,3) :$cdtype_code;
		$rand_num = rand(10000,99999);
		//中间6位
		$passwordChar = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$passwordLength = 6;
		$max = strlen($passwordChar) - 1;
		$rand_num = '';
		for ($i = 0; $i < $passwordLength; ++$i) {  
		    $rand_num .= $passwordChar[rand(0, $max)];
		}
		return $firstchar . $rand_num .$secondchar ;
	}
    
    
    

}
