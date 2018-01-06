<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topm_ctl_default extends topm_controller
{
	//下面这个构造方法是雷成德临时加进去的，由于对时间比较紧张，后面再优化2015.11.19。
	public function __construct()
	{
		parent::__construct();
        $code = $_GET['code'];
        $next_url  = isset($_GET["state"]) ? trim($_GET["state"]) :"";
        //商城域名 ： $http_host = "http://www.myjmall.com"; //正式
        $http_host = app::get('sysconf')->getConf('site.sitehost');
        if(!$http_host) { $http_host = "http://www.mjm.net"; }
        //微信公众号账号： 测试 wx06db345d9bee6589  ———— c598082c783270a56fa7cfae7bc35eed
        //微信公众号账号： 正式  wxe55aa19f35f85ca7  ———— 9878c9dfda0535f9a29737d1bf6a1e55
        $trust = kernel::single('sysuser_passport_trust_manager')->getTrustObjectByFlag("wapweixin");
        $wxsetting = $trust->getSetting();
        if(!$wxsetting || empty($wxsetting["appKey"])){
        	$wxsetting["appKey"]="wxe55aa19f35f85ca7";
        }
        if(!$wxsetting || empty($wxsetting["appSecret"])){
        	$wxsetting["appSecret"]="9878c9dfda0535f9a29737d1bf6a1e55";
        }
        $wxsetting["appKey"]="wxd5e1e4e198f0e216";
        $wxsetting["appSecret"]="bbec12ea2e6d6d0c810d373039c67caf";

     	/*
      	 *	Debug 区域
		 */
		if(isset($code)&&!empty($code)){
			$appid =  $wxsetting["appKey"];
			$secret = $wxsetting["appSecret"];
	        kernel::single('base_session')->start();
	        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
	        $acc_token = $this->getSslPage($url);
	        $acc_token2 = json_decode($acc_token,true);
	        $openid  = $acc_token2['openid'];
	        //获取access_token
	        $access_url ="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	        $ken = $this->getSslPage($access_url);  //全局token
	        $ken = json_decode($ken,true);
	        $access_token = $ken['access_token'];
	        $userinfo ="https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}";
	        $userinfo = $this->getSslPage($userinfo);
	        $userinfo = json_decode($userinfo,true);
			$openid     = trim($userinfo['openid']);
			$fields = "user_id,mobile,email,login_account";
			$filter = array('openid'=>$openid);
			$udata = app::get('sysuser')->model('account')->getRow($fields, $filter);
			$rede_url ="/";
			//用户之前已经授过权了，就直接登录进入后台
			if ($udata && intval($udata["user_id"])>0){
				header("Content-type: text/html; charset=utf-8");
				userAuth::login($udata['user_id'], $udata['login_account']);
				//登录成功，送UID给线下优惠券
            	userAuth::initoffenCoupon(userAuth::id(),false); //不提示
				//直接登录进入管理后台 http://www.mjm.net/wap/member-index.html
				$rede_url = !empty($next_url) ? $next_url : $http_host."/wap/member-index.html";
				$goonhtml = '<div style="width: 70%;margin: 15% auto;border: 1px solid #CCC;border-radius: 16px;text-align: center;padding: 0;font-size: 1.5em;line-height: 4em;background: rgba(223, 231, 236, 0.1);color: #FF9800;">
      <h3 style="font-size: 2.5em;">微信登录成功</h3><label style="color: #999;">正在进入个人中心...</label></div>';
				$goonhtml .=  "<script type=\"text/javascript\">var goMempos = function(){window.location.href = '{$rede_url}';};setTimeout(goMempos(),1000);</script>"; 
				exit($goonhtml);
			}else{
				$_SESSION['openid'] = $openid;  //刚授权，还没绑定用户,存进seeion
				$wxuser_arr = array("openid"=>$openid,"nickname"=>trim($userinfo['nickname']));
				$wchar = base64_encode(json_encode($wxuser_arr));
				$rede_url = $http_host."/wap/passport-wechat.html?w={$wchar}";
				//return redirect::action('topm_ctl_passport@wechat')."?w=".$wchar;
			}
			//重定向到新的地址去
			echo "<script>window.location.href = '{$rede_url}'; </script>"; exit;
		}
	}
	
	//这个是我手写的一个方法，专门负责接收发发送微信端的接口值2015.11.19
	function getSslPage($url,$post_data= false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if(false){
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);// post的变量
		}
		/*if($post_data && !empty($post_data)){
		
		}*/
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

    public function index()
    {
        $GLOBALS['runtime']['path'][] = array('title'=>app::get('topm')->_('首页〉'),'link'=>kernel::base_url(1));
        $this->setLayoutFlag('index');
        return $this->page();
    }

    public function switchToPc()
    {
        //$code = $_GET['code'];var_dump($code);exit;
        setcookie('browse', 'pc');
        return redirect::route('topc');
    }
}
