<?php
/**
 * @author chenping<chenping@shopex.cn>
 * @version 2011-11-15 10:50:00 星期二
 * @package messenger
 * @description 由于短信平台接口变更，调整为新的业务接口
 */

class system_messenger_smschg{

    /**
     * 服务器时间接口
     * http://newsms.ex-sandbox.com/sms_webapi/(内网)
     * http://webapi.sms.shopex.cn(线上)
     */
    private $timeUrl = 'http://webapi.sms.shopex.cn';

    /**
     * 免登录地址
     * http://newsms.ex-sandbox.com(内网)
     * http://sms.shopex.cn(线上)
     */
    private $passLoginUrl = 'http://sms.shopex.cn/?';

    /**
     * 用户短信账户信息获取接口
     * http://webpy.ex-sandbox.com/(内网)
     * http://api.sms.shopex.cn(线上)
     */
    private $accountUrl = 'http://api.sms.shopex.cn';

    /**
     * 短信发送接口
     * http://webpy.ex-sandbox.com/(内网)
     * http://api.sms.shopex.cn(线上)
     */
    private $sendUrl = 'http://api.sms.shopex.cn';

    /**
     * 激活码接口
     * http://newsms.ex-sandbox.com/sms_webapi/(内网)
     * http://webapi.sms.shopex.cn(线上)
     */
    private $codeUrl = 'http://webapi.sms.shopex.cn';

    public function __construct() {
        $this->httpClient = kernel::single('base_httpclient');
        $this->httpClient->set_timeout(6);

        //启用测试数据
        //$this->testInit();
    }

    /**
     * @description 验证激活码
     * @access public
     * @param void
     * @return void
     */
    public function checkCode(&$msg) {
        $data['certi_app'] = 'sms.check_active';
        $data['entId'] = $this->getEntId();
        $data['entPwd'] = $this->getEntAc();
        $data['active_code'] = app::get('system')->getConf('activation_code');
        if(!$data['active_code']){$msg = app::get('system')->_('验证码不存在！');return false;}
        $data['license'] = $this->getCerti();
        if($data['license']==false){$msg = app::get('system')->_('no license');return false;}

        $data['source'] = $this->getSource();
        $data['version'] = '1.0';
        $data['format'] = 'json';
        $data['timestamp'] = $this->getTime($msg);
        if($data['timestamp']==false)return false;

        $data['certi_ac'] = $this->make_shopex_ac($data,$this->getSourceToken());

        $result = $this->httpClient->post($this->codeUrl,$data);

        if($result['res']=='succ'){
            $msg = app::get('system')->_('生成激活码成功');
            return true;
        }elseif($result['res']=='fail'){
            $msg = $result['info'];
            return false;
        }
        return false;
    }

    /**
     * @description 生成激活码
     * @access public
     * @param void
     * @return void
     */
    public function createCode(&$msg) {
        $data['certi_app'] = 'sms.create_code';
        $data['entId'] = $this->getEntId();
        $data['entPwd'] = $this->getEntAc();

        $data['mobile'] = app::get('system')->getConf('store.mobile');
        if(!$data['mobile']){$msg = app::get('system')->_('手机号不能为空！');return false;}
        $data['license'] = $this->getCerti();
        if($data['license']==false){$msg = app::get('system')->_('no license');return false;}

        $data['source'] = $this->getSource();
        $data['version'] = '1.0';
        $data['format'] = 'json';
        $data['timestamp'] = $this->getTime($msg);
        if($data['timestamp']==false)return false;
        $data['certi_ac'] = $this->make_shopex_ac($data,$this->getSourceToken());

        $result = $this->httpClient->post($this->codeUrl,$data);
        $result = json_decode($result,true);

        if($result['res']=='succ'){
            $msg = app::get('system')->_('生成激活码成功');
            return true;
        }elseif($result['res']=='fail'){
            $msg = $result['info'];
            return false;
        }
        return false;
    }
    
    
    /**
     * 新的短信接口： http://web.cr6868.com/default.aspx
     * http://web.cr6868.com/asmx/smsservice.aspx?
     * 账号：13686422883
     * 密码：276473
       $contents=>{
            [phones] => 13048945610
            [content] => 您的验证码：138636请在页面中输入完成验证，不要把此验证码泄露给任何人，如非本人操作，请忽略此信息【精茂电商】
        }
     * 
     */
     function sendNmsg($contents){
     	header("Content-Type: text/html; charset=UTF-8");
     	$smsApiUrl = "http://web.cr6868.com/asmx/smsservice.aspx?";
		$accountInfo = array("uId"=> "13686422883", "pwd"=>"456A57CBBAD9FA2598C0EF8B1EAD");
		
		$sms_plat  = "duanxinwang"; 
		//默认使用cr6868平台的API接口通道；
		if($sms_plat && $sms_plat=="duanxinwang"){
			$smsApiUrl = "http://web.duanxinwang.cc/asmx/smsservice.aspx?";
			$accountInfo = array("uId"=> "13825250495", "pwd"=>"F5A8C176D532AF71A4D3D0E889FC");
		}
		
		$params='';//要post的数据
		$contents and $contents = reset($contents); //取第一个号；
		//以下信息自己填以下
		$mobile= isset($contents["phones"])? trim($contents["phones"]) : '13686422883';//手机号
		$argv = array(
			'name'=>$accountInfo["uId"], //必填参数。用户账号
			'pwd'=>$accountInfo["pwd"], //必填参数。（web平台：基本资料中的接口密码）
			'content'=>$contents["content"], //必填参数。发送内容（1-500 个汉字）UTF-8编码
			'mobile'=>$mobile, //必填参数。手机号码。多个以英文逗号隔开
			'stime'=>'', //可选参数。发送时间，填写时已填写的时间发送，不填时为当前时间发送
			'sign'=> isset($contents["compay_sign"])? trim($contents["compay_sign"]):'精茂电商', //必填参数。用户签名。
			'type'=>'pt', //必填参数。固定值 pt
			'extno'=>'110' //可选参数，扩展码，用户定义扩展码，只能为数字
		);
		//构造要post的字符串
		//echo $argv['content'];
		$flag = 0;
		foreach ($argv as $key=>$value) {
			if ($flag!=0) { $params .= "&"; $flag = 1; }
			$params.= $key."="; $params.= urlencode($value); $flag = 1;// urlencode($value);
		}
		//API接口地址参数
		$http_url = $smsApiUrl.$params; //提交的url地址
     	logger::info("messenger_sms_post:".print_r($http_url,1));
        $result = $this->httpClient->get($http_url,false); // GET 方式
        //print_r($result);
        logger::info("messenger_sms_result:".$result);
        if($result && substr($result,0,1)=="0"){
        	$msg = app::get('system')->_('短信发送成功！');return true;
        }else{
        	//10＝用户名或密码错误
        	$msg = app::get('system')->_('短信发送失败！');
            throw new \LogicException($msg);return false;
        }
     }


    /**
     * @description 短信发送
     * @access public
     * @param void
     * @return void
     */
    public function send($contents,$config) {
    	//使用新的短信接口
    	return $this->sendNmsg($contents); 
    	
        $data['certi_app'] = 'sms.send';
        $data['entId'] = $this->getEntId();
        $data['entPwd'] = $this->getEntAc();
        $data['license'] = $this->getCerti();
        //s:132:"a:3:{s:6:"ent_id";s:14:"88150701294304";s:6:"ent_ac";s:32:"fb80e82d100364080f1d31ad48a13a8a";s:9:"ent_email";s:14:"mixu@zh718.com";}";
        /*$data['entId'] = "88151001310291";
        $data['entPwd'] = "4125734c965d188f71caba950355c541";
        $data['license']="1514578536";*/

        //当在shopex内网开发时，配置测试内容时使用
        if($config['entId'] && $config['entPwd'] && $config['license'])
        {
            $data['entId']  = $config['entId'];
            $data['entPwd'] = $config['entPwd'];
            $data['license'] =$config['license'];
        }

        if($data['license']==false)
        {
            $msg=app::get('system')->_('no license');
            throw new \LogicException($msg);
            return false;
        }
        $data['source'] = $this->getSource($config['specialChannel']);
        
        $data['encoding'] = 'utf8';

        //是否需要回复
        if($config['use_reply']==1) $data['use_reply'] = 1;
        //发送类型  fan-out:群发 notice通知
        $data['sendType'] = $config['sendType'] ? $config['sendType'] : 'notice';

        if(!$contents)
        {
            $msg=app::get('system')->_('手机短信不能为空！');
            throw new \LogicException($msg);
            return false;
        }
        $data['contents'] = json_encode($contents);

        //是否使用黑名单 1:是 0:否
        $data['use_backlist'] = $config['use_backlist'] ? $config['use_backlist'] : 1;
        $data['version'] = '1.0';
        $data['format'] = 'json';
        $data['timestamp'] = $this->getTime($msg);
        if($data['timestamp']==false)
        {
            throw new \LogicException($msg);
            return false;
        }
        $data['certi_ac'] = $this->make_shopex_ac($data,$this->getSourceToken($config['specialChannel']));
		
        logger::info("messenger_sms_post:".print_r($data,1));
        $result = $this->httpClient->post($this->sendUrl,$data);
        logger::info("messenger_sms_result:".$result);
        $result = json_decode($result,true);

        if($result['res']=='succ') {
            $msg = app::get('system')->_('短信发送成功！');
            return true;
        }elseif($result['res']=='fail'){
            $msg = $result['info'];
            throw new \LogicException($msg);
            return false;
        }else{
            $msg = app::get('system')->_('短信发送失败！');
            throw new \LogicException($msg);
            return false;
        }
    }

    /**
     * @description 获取免登录地址
     * @access public
     * @param void
     * @return void
     */
    public function getSmsBuyUrl() {
        $iBase64 = kernel::single('system_messenger_iBase64');

        $data['biz_id'] = $iBase64->encode($this->getSource());
        $data['entid'] = $this->getEntId();
        $data['ac'] = md5($data['entid'].$this->getEntAc());
        $data['t'] = $this->getTime($msg);

        $params['ctl'] = 'sms';
        $params['act'] = 'prdsList';
        $params['source'] = $iBase64->encode(implode('|',$data));

        $url = $this->passLoginUrl.http_build_query($params);
        return $url;
    }

    /**
     * @description 获取用户短信账户信息
     * @access public
     * @param void
     * @return void
     */
    public function getSmsAccount(&$msg) {
        $data['certi_app'] = 'sms.info';
        $data['entId'] = $this->getEntId();
        $data['entPwd'] = $this->getEntAc();
        $data['source'] = $this->getSource();
        $data['version'] = '1.0';
        $data['format'] = 'json';
        $data['timestamp'] = $this->getTime($msg);
        if($data['timestamp']==false) return false;
        $data['certi_ac'] = $this->make_shopex_ac($data,$this->getSourceToken());

        $result = $this->httpClient->post($this->accountUrl,$data);
        $result = json_decode($result,true);
        if($result['res']=='succ'){
            return $result;
        }elseif($result['res']=='fail'){
            $msg = $result['info'];
            return false;
        }
        return false;
    }

    /**
     * @description 获取服务器时间戳
     * @access public
     * @param void
     * @return void
     */
    public function getTime(&$msg) {
        $data['certi_app'] = 'sms.servertime';
        $data['version'] = '1.0';
        $data['format'] = 'json';
        $data['timestamp'] = '';
        $data['certi_ac'] = $this->make_shopex_ac($data,'SMS_TIME');
        $result = $this->httpClient->post($this->timeUrl,$data);

        $result = json_decode($result,true);
        if($result['res']=='succ'){
            return $result['info'];
        }elseif($result['res']=='fail'){
            $msg = $result['info'];
            return false;
        }else{
            $msg = app::get('system')->_('接口地址无法相应！');
            return false;
        }
    }

    /**
     * @description 获取license
     * @access public
     * @param void
     * @return void
     *
     * 测试License: 1997371231
     */
    public function getCerti() {
        #return 1997371231;
        if($this->certi){
            return $this->certi;
        }elseif(base_certificate::get('certificate_id')){
            return  base_certificate::get('certificate_id');
        }else{
            return false;
        }
    }

    /**
     * @description 设置license
     * @access public
     * @param string $certi
     * @return void
     */
    public function setCerti($certi) {
        $this->certi = $certi;
    }

    /**
     * @description 获取token
     * @access public
     * @param void
     * @return void
     *
     * 测试Token: efa5552656ed12705a4afb3502405e45d48f948001291de47ac6fd17bd19013c
     */
    public function getToken() {
        if($this->token){
            return $this->token;
        }elseif(base_certificate::get('token')){
            return  base_certificate::get('token');
        }else{
            return false;
        }
    }

    /**
     * @description 设置token
     * @access public
     * @param string $token
     * @return void
     */
    public function setToken($token) {
        $this->token = $token;
    }

    /**
     * @description 获取企业帐号
     * @access public
     * @param void
     * @return String
     *
     * 测试企业帐号: 113110510556
     */
    public function getEntId() {
        if($this->entid) {
            return $this->entid;
        }else{
            return base_enterprise::ent_id();
        }
    }

    /**
     * @description 设置企业帐号
     * @access public
     * @param string $entid
     * @return void
     */
    public function setEntId($entid) {
        $this->entid = $entid;
    }

    /**
     * @description 获取企业密码
     * @access public
     * @param void
     * @return String
     *
     * 测试企业密码: md5('123jjzh'.'ShopEXUser');
     */
    public function getEntAc() {
        if($this->entac) {
            return $this->entac;
        }else{
            return base_enterprise::ent_ac();
        }
    }

    /**
     * @description 设置企业密码
     * @access public
     * @param string $entac
     * @return void
     */
    public function setEntAc($entac) {
        $this->entac  =$entac;
    }

    /**
     * @description 获取业务产品ID
     * @access public
     * @param void
     * @return void
     *
     * 测试产品ID:338049
     */
    public function getSource($specialChannel=false) {
        if( $specialChannel && defined('URGENT_SOURCE_ID') ){
            return URGENT_SOURCE_ID;
        }
        #return defined('SOURCE_ID') ? SOURCE_ID : '373615';
        return defined('SOURCE_ID') ? SOURCE_ID : '533218';
    }

    /**
     * @description 业务产品对应的Token
     * @param bool $specialChannel 是否要走特殊的短信通道（验证码）
     * @access public
     * @param void
     * @return void
     *
     * 测试产品token:ac584f6d022ead5f4d8b5d1e6a80a7d1
     */
    public function getSourceToken($specialChannel=false) {
        if( $specialChannel && defined('URGENT_SOURCE_TOKEN') ){
            return URGENT_SOURCE_TOKEN;
        }
        #return defined('SOURCE_TOKEN') ? SOURCE_TOKEN: 'c634a9b816f26956391a56cd6474b15c';
        return defined('SOURCE_TOKEN') ? SOURCE_TOKEN: '0c4b3f44cee06df91b76deaa57608fbb';
    }

    public function make_shopex_ac($temp_arr,$token){
        ksort($temp_arr);
        $str = '';
        foreach($temp_arr as $key=>$value){
            if($key!='certi_ac') {
                $str.= $value;
            }
        }
        return strtolower(md5($str.strtolower(md5($token))));
    }

    /**
     * @description 测试初始化
     * @access public
     * @param void
     * @return void
     */
    public function testInit() {

         // 服务器时间接口(内网)
         $this->timeUrl = 'http://newsms.ex-sandbox.com/sms_webapi/';

         // 免登录地址(内网)
        $this->passLoginUrl = 'http://newsms.ex-sandbox.com/?';

        //用户短信账户信息获取接口(内网)
        $this->accountUrl = 'http://webpy.ex-sandbox.com/';

        //短信发送接口(内网)
        $this->sendUrl = 'http://webpy.ex-sandbox.com/';

        //激活码接口(内网)
        $this->codeUrl = 'http://newsms.ex-sandbox.com/sms_webapi/';

        //测试license
        //$this->certi = '1997371231';

        //测试token
        //$this->token = 'efa5552656ed12705a4afb3502405e45d48f948001291de47ac6fd17bd19013c';

        //测试企业帐号
        //$this->entid = '113110510556';

        //测试企业密码
        //$this->entac = md5('123jjzh'.'ShopEXUser');
    }
}
?>
