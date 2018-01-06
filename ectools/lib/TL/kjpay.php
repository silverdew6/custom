<?php
use Endroid\QrCode\X509;
use Endroid\QrCode\phpseclib\Crypt\Hash;
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
/**
 * 外部支付方式网关统一的接口方法
 * @auther shopex ecstore dev dev@shopex.cn
 * @version 0.1
 * @package ectools.lib.payment
 */
class ectools_TL_kjpay
{
    protected $key                  = '1234567890';
    //商户号
    protected $merchantId           = '009440353114138';
    protected $orderExporeDatetime  = 30;     //订单过期时间;

    /**
     * 构造方法
     * @params string - app id
     * @return null
     */
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
    }

    //用户注册请求接口
    public function UserInfo($userId)
    {
        $url = 'https://cashier.allinpay.com/usercenter/merchant/UserInfo/reg.do';
        $userStr = 'tlpay-jmc170930jmc-' . $userId . '-999';
        //签名串的首尾都要加'&'
        $param['signType'] = '0'; //0-MD5，1-商户使用证书签名
        $param['merchantId'] = $this->merchantId;  //商户号
        $param['partnerUserId'] = $userStr;//签名时为小写
        $param['signMsg'] = $this->getsign($param, 1); //签名
        $bizString = $this->ToUrlParams($param);

        $url = $url . "?" . $bizString;
        $json = $this->https_request($url);//返回一个json值
        $arr = json_decode($json, true);//json->array
        if ($arr['resultCode'] == '000') {
            $objUser = app::get('sysuser')->model('tl_mem');
            $data = array(
                'user_id' => $userId,
                'memid' => $arr['userId'],
                'memstr' => $userStr,
            );
            $res_user = $objUser->save($data);
            if ($res_user) {
                return $arr['userId'];
            }
        } else {
            return false;
        }
    }
    //支付
    public function pay($data)
    {
        $url                            = $this->serverUrl($data['type']);
        //固参
        $param['inputCharset']          = '1';
        $param['pickupUrl']             = $data['pickupUrl'];     //'http://www.gojmall.com/wap/tlpick.html';
        $param['receiveUrl']            = $data['receiveUrl'];
        $param['version']               = 'v1.0';
        $param['language']              = '1';
        $param['signType']              = '0';
        $param['merchantId']            = $this->merchantId;
        $param['orderNo']               = $data['orderNo'];
        $param['orderAmount']           = $data['orderAmount'];                          //测试金额1分
        $param['orderCurrency']         = '0';
        $param['orderDatetime']         =  $data['orderDatetime'];  //商户订单提交时间，精确到秒 '20171009150913';
        $param['orderExpireDatetime']   = $this->orderExporeDatetime;     //订单过期时间，<=180,单位为分
        if (!empty($data['userid'])) {
            $param['productName']       = trim($data['productName']);
            $param['ext1']              = "<USER>" . $data['userid'] . "</USER>";
        }
        $param['payType']               = $data['kjType'];
        $param['signMsg']               = $this->getsign($param);

        if (trim($data['type']) == 'H5') {
            //var_dump($data);exit;
            echo $this->get_html($param , $url);
            exit;
        }

        $bizString                      = $this->ToUrlParams($param);
        $url                            = $url . "?" . $bizString;
        Header("Location:".$url);
    }
    //单笔订单查询接口
    public function gateway($data)
    {
        $time                   = date('YmdHis',time());
        $paytype                = 'gateway';
        $url                    = $this->serverUrl($paytype);

        $param['merchantId']    = $this->merchantId;
        $param['version']       = 'v1.5';
        $param['signType']      = '0';
        $param['orderNo']       = $data['orderNo'];
        $param['orderDatetime'] = $data['orderDatetime'];
        $param['queryDatetime'] = $time;
        $param['signMsg']       = $this->getsign($param);

        $bizString              = $this->ToUrlParams($param);
        $url                    = $url."?".$bizString;
        $json                   = $this->https_request($url);//访问订单查询接口 返回字符串

        $str                    = rtrim(trim($json),'&');
        $arr_res                = explode('&',$str);
        $arr                    = [];
        foreach($arr_res as $vv){
            $arr_vv             = [];
            $arr_vv             = explode('=',$vv);
            $arr[$arr_vv[0]]    = $arr_vv[1];
        }
        if(!empty($arr['ERRORCODE'])){
            return $arr['ERRORMSG'];
        }

        if($arr['payResult'] != 1){
            return '支付失败！';   //支付成功
        }

        $bufVerifySrc = "";
        if($arr["merchantId"] != "")
            $bufVerifySrc = $bufVerifySrc."merchantId=".trim($arr["merchantId"])."&"; 		//merchantId

        if($arr["version"] != "")
            $bufVerifySrc = $bufVerifySrc."version=".trim($arr["version"])."&";		//version

        if($arr["language"] != "")
            $bufVerifySrc = $bufVerifySrc."language=".trim($arr["language"])."&";		//language

        if($arr["signType"] != "")
            $bufVerifySrc = $bufVerifySrc."signType=".trim($arr["signType"])."&";		//signType

        if($arr["payType"] != "")
            $bufVerifySrc = $bufVerifySrc."payType=".trim($arr["payType"])."&";		//payType

        if($arr["paymentOrderId"] != "")
            $bufVerifySrc = $bufVerifySrc."paymentOrderId=".trim($arr["paymentOrderId"])."&";		//paymenrOrderId

        if($arr["orderNo"] != "")
            $bufVerifySrc = $bufVerifySrc."orderNo=".trim($arr["orderNo"])."&";		///orderNo

        if($arr["orderDatetime"] != "")
            $bufVerifySrc = $bufVerifySrc."orderDatetime=".trim($arr["orderDatetime"])."&";		//orderDatetime

        if($arr["orderAmount"] != "")
            $bufVerifySrc = $bufVerifySrc."orderAmount=".trim($arr["orderAmount"])."&";		//orderAmount

        if($arr["payDatetime"] != "")
            $bufVerifySrc = $bufVerifySrc."payDatetime=".trim($arr["payDatetime"])."&";		//payDatetime

        if($arr["payAmount"] != "")
            $bufVerifySrc = $bufVerifySrc."payAmount=".trim($arr["payAmount"])."&";		//payAmount

        if($arr["ext1"] != "")
            $bufVerifySrc = $bufVerifySrc.urldecode("ext1=".$arr["ext1"])."&";		//ext1

        if($arr["ext2"] != "")
            $bufVerifySrc = $bufVerifySrc.urldecode("ext2=".$arr["ext2"])."&";		//ext2

        if($arr["payResult"] != "")
            $bufVerifySrc = $bufVerifySrc."payResult=".trim($arr["payResult"])."&";		//payResult

        if($arr["errorCode"] != "")
            $bufVerifySrc = $bufVerifySrc."errorCode=".trim($arr["errorCode"])."&";		//errorCode

        if($arr["returnDatetime"] != "")
            $bufVerifySrc = $bufVerifySrc."returnDatetime=".trim($arr["returnDatetime"]).'&';

        $bufVerifySrc .= 'key='.$this->key;
        $verifyMsg = $arr['signMsg'];
        $signMsg = strtoupper(md5($bufVerifySrc));
        if($verifyMsg == $signMsg){
            return '验签成功!';
        }else{
            return '验签失败!';
        }
    }
    //单笔订单退款申请接口
    public function refund($data)
    {
        $paytype   = 'gateway';
        $url       = $this->serverUrl($paytype);
        $param['version']       = 'v2.3';
        $param['signType']      = '0';
        $param['merchantId']    = $this->merchantId;
        $param['orderNo']       = $data['orderNo'];//'Tl-11710091509174603';//
        $param['refundAmount']  = $data['orderAmount'];
        $param['orderDatetime'] = $data['orderDatetime']; //'20171009150913'; //
        $param['signMsg']       = $this->getsign($param);
        $bizString  = $this->ToUrlParams($param);
        $url        = $url."?".$bizString;
        $json       = $this->https_request($url);//访问订单查询接口 返回字符串
        $str        = rtrim(trim($json),'&');
        $arr_res    = explode('&',$str);
        $arr = [];
        foreach($arr_res as $vv){
            $arr_vv             = explode('=',$vv);
            $arr[$arr_vv[0]]    = $arr_vv[1];
        }

        if($arr['ERRORCODE']){
            return $arr['ERRORMSG'];
        }
        if($arr['refundResult'] != 20){
            return '联机退款申请失败';
        }
        //开始组验签源串
        $bufVerifySrc = "";
        if($arr["merchantId"] != "")
            $bufVerifySrc = $bufVerifySrc."merchantId=".$arr["merchantId"]."&"; 		//merchantId

        if($arr["version"] != "")
            $bufVerifySrc = $bufVerifySrc."version=".$arr["version"]."&";		//version

        if($arr["signType"] != "")
            $bufVerifySrc = $bufVerifySrc."signType=".$arr["signType"]."&";		//signType

        if($arr["orderNo"] != "")
            $bufVerifySrc = $bufVerifySrc."orderNo=".$arr["orderNo"]."&";		///orderNo

        if($arr["orderAmount"] != "")
            $bufVerifySrc = $bufVerifySrc."orderAmount=".$arr["orderAmount"]."&";		//orderAmount

        if($arr["orderDatetime"] != "")
            $bufVerifySrc = $bufVerifySrc."orderDatetime=".$arr["orderDatetime"]."&";		//orderDatetime

        if($arr["refundAmount"] != "")
            $bufVerifySrc = $bufVerifySrc."refundAmount=".$arr["refundAmount"]."&";		//refundAmount

        if($arr["refundDatetime"] != "")
            $bufVerifySrc = $bufVerifySrc."refundDatetime=".$arr["refundDatetime"]."&";		//refundDatetime

        if($arr["refundResult"] != "")
            $bufVerifySrc = $bufVerifySrc."refundResult=".$arr["refundResult"]."&";		//refundResult

        if($arr["errorCode"] != "")
            $bufVerifySrc = $bufVerifySrc."errorCode=".$arr["errorCode"]."&";		//errorCode

        if($arr["returnDatetime"] != "")
            $bufVerifySrc = $bufVerifySrc."returnDatetime=".$arr["returnDatetime"]."&";		//returnDatetime

        $bufVerifySrc = $bufVerifySrc."key=".$this->key;		//key

        $verifyMsg = $arr['signMsg'];
        $venMsg = strtoupper(md5($bufVerifySrc));
        $verifyResult = 0;
        if($verifyMsg == $venMsg){
            $verifyResult =1;
        }
        if($verifyResult){
            return '退款受理中!';
        }else{
            return '验签失败';
        }
    }
    //退款状态接口查询
    public function refundQuery($data)
    {
        $paytype = 'refundquery';
        $url = $this->serverUrl($paytype);

        $param['version']       = 'v2.4';
        $param['signType']      = '0';
        $param['merchantId']    = $this->merchantId;
        $param['orderNo']       = $data['orderNo'];
        $param['refundAmount']  = '1';
        $param['signMsg']       = $this->getsign($param);

        $bizString              = $this->ToUrlParams($param);
        $url                    = $url."?".$bizString;
        $json                   = $this->https_request($url);//访问订单查询接口 返回字符串
        $str                    = trim($json);
        $arr_res                = explode('|',$str);
        $kk                     = count($arr_res)-2;

        $tt                     = "/TKSUCC000+[1-8]/";
        preg_match($tt,$json,$arr_res);
        $str = '';
        if($arr_res[0] == 'TKSUCC0001'){
            $str = '退款未受理中';
        }else if($arr_res[0] == 'TKSUCC0002'){
            $str = '退款待审核中';
        }else if($arr_res[0] == 'TKSUCC0003'){
            $str = '退款审核通过';
        }else if($arr_res[0] == 'TKSUCC0004'){
            $str = '退款冲销';
        }else if($arr_res[0] == 'TKSUCC0005'){
            $str = '退款处理中';
        }else if($arr_res[0] == 'TKSUCC0006'){
            $str = '退款成功';
        }else if($arr_res[0] == 'TKSUCC0007'){
            $str = '退款失败';
        }else if($arr_res[0] == 'TKSUCC0008'){
            $str = '退款审核不通过';
        }else{
            $str = '订单有误';
        }
        return $str;
    }
    //MD5签名,不判断为空的条件
    protected function getsign($data, $type = 0)
    {
        $str = '';
        foreach ($data as $kk => $vv) {
            if ($vv != null && $vv != '') {
                $str .= $kk . '=' . $vv . '&';
            }
        }
        $key = $this->key;
        $str .= 'key=' . $key;
        if ($type != 0) {
            $str = '&' . $str . '&';
        }
        $signMsg = strtoupper(md5($str));
        return $signMsg;
    }
    //提交数据
    public function https_request($url, $data = null)
    {
        //初始化
        $curl = curl_init();
        //设置选项，包括URL
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        //如果$data不为空，用post传参
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 1返回内容 0输出内容
        //执行并获取结果
        $output = curl_exec($curl);
        //释放curl句柄
        curl_close($curl);

        return $output;
    }
    //设置传参格式
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    //支付结果回调
    public function receive($data)
    {
        //先对待支付订单做判断
        $objTrade               = app::get('systrade')->model('trade');
        $filterBill['tid']      = $data['orderNo'];
        $tradeList              = $objTrade -> getRow('status' ,$filterBill);
        if($tradeList['status'] != 'WAIT_BUYER_PAY'){
            return '请确认订单状态';
        }
        $orderNo = $data['orderNo'];
        if ($data['payResult'] == 1) {    //支付成功
            $param['merchantId']        = $data["merchantId"];
            $param['version']           = $data['version'];
            $param['language']          = $data['language'];
            $param['signType']          = $data['signType'];
            $param['payType']           = $data['payType'];
            $param['issuerId']          = $data['issuerId'];
            $param['paymentOrderId']    = $data['paymentOrderId'];
            $param['orderNo']           = $data['orderNo'];
            $param['orderDatetime']     = $data['orderDatetime'];
            $param['orderAmount']       = $data['orderAmount'];
            $param['payDatetime']       = $data['payDatetime'];
            $param['payAmount']         = $data['payAmount'];
            $param['ext1']              = $data['ext1'];
            $param['ext2']              = $data['ext2'];
            $param['payResult']         = $data['payResult'];  //为1时支付成功
            $param['errorCode']         = $data['errorCode'];
            $bufSignSrc = '';
            foreach ($param as $kk => $vv) {
                if ($vv != '') {
                    $bufSignSrc .= $kk . '=' . $vv . '&';
                }
            }
            $param['returnDatetime'] = $data['returnDatetime'];
            if ($param['returnDatetime'] != '') {
                $bufSignSrc .= 'returnDatetime=' . $param['returnDatetime'];
            }
            if ($param['signType'] == 1) {  //测试未通过
                $verifyResult = $this->signCheck($bufSignSrc, $data["signMsg"]);
            } else {
                if ($data["signMsg"] == strtoupper(md5($bufSignSrc . "&key=" . $this->key))) {
                    $str = 'succ';
                }
            }
        }
        if (empty($str)) {
            $ret_data = array(
                'orderNo'       => $orderNo,
                'str'           => 'failed',
                'time'          => time()
            );
        }else{
            $ret_data = array(
                'orderNo'       => $orderNo,
                'str'           => $str,
                'money'         => $data['payAmount']/100,
                'time'          => time(),
                'merId'         => $data["merchantId"],
                'paymentOrd'    => $data['paymentOrderId'],
                'type'          => $data['type']
            );
        }
        return $ret_data;
    }
    public function get_html($data, $url)
    {
        $StrHtml = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta http-equiv="Content-Language" content="zh-CN"/>
	<meta http-equiv="Expires" CONTENT="0">        
	<meta http-equiv="Cache-Control" CONTENT="no-cache">        
	<meta http-equiv="Pragma" CONTENT="no-cache">
	<title>通联网上支付平台通联网上支付平台-商户接口范例-支付请求信息签名</title>
	<link href="css.css" rel="stylesheet" type="text/css">
</head>
<body>	
    <form id="myForm" action="' . $url . '" method="post">
	<input type="hidden" name="inputCharset" id="inputCharset" value="' . $data['inputCharset'] . '" />
	<input type="hidden" name="pickupUrl" id="pickupUrl" value="' . $data['pickupUrl'] . '"/>
	<input type="hidden" name="receiveUrl" id="receiveUrl" value="' . $data['receiveUrl'] . '" />
	<input type="hidden" name="version" id="version" value="' . $data['version'] . '"/>
	<input type="hidden" name="language" id="language" value="' . $data['language'] . '" />
	<input type="hidden" name="signType" id="signType" value="' . $data['signType'] . '"/>
	<input type="hidden" name="merchantId" id="merchantId" value="' . $data['merchantId'] . '" />
	<input type="hidden" name="payerName" id="payerName" value="' . $data['payerName'] . '"/>
	<input type="hidden" name="payerEmail" id="payerEmail" value="' . $data['payerEmail'] . '" />
	<input type="hidden" name="payerTelephone" id="payerTelephone" value="' . $data['payerTelephone'] . '" />
	<input type="hidden" name="payerIDCard" id="payerIDCard" value="' . $data['payerIDCard'] . '" />
	<input type="hidden" name="pid" id="pid" value="' . $data['pid'] . '"/>
	<input type="hidden" name="orderNo" id="orderNo" value="' . $data['orderNo'] . '" />
	<input type="hidden" name="orderAmount" id="orderAmount" value="' . $data['orderAmount'] . '"/>
	<input type="hidden" name="orderCurrency" id="orderCurrency" value="' . $data['orderCurrency'] . '" />
	<input type="hidden" name="orderDatetime" id="orderDatetime" value="' . $data['orderDatetime'] . '" />
	<input type="hidden" name="orderExpireDatetime" id="orderExpireDatetime" value="' . $data['orderExpireDatetime'] . '"/>
	<input type="hidden" name="productName" id="productName" value="' . $data['productName'] . '" />
	<input type="hidden" name="productPrice" id="productPrice" value="' . $data['productPrice'] . '" />
	<input type="hidden" name="productNum" id="productNum" value="' . $data['productNum'] . '"/>
	<input type="hidden" name="productId" id="productId" value="' . $data['productId'] . '" />
	<input type="hidden" name="productDesc" id="productDesc" value="' . $data['productDesc'] . '" />
	<input type="hidden" name="ext1" id="ext1" value="' . $data['ext1'] . '" />
	<input type="hidden" name="ext2" id="ext2" value="' . $data['ext2'] . '" />
	<input type="hidden" name="extTL" id="extTL" value="' . $data['extTL'] . '" />
	<input type="hidden" name="payType" value="' . $data['payType'] . '" />
	<input type="hidden" name="issuerId" value="' . $data['issuerId'] . '" />
	<input type="hidden" name="pan" value="' . $data['pan'] . '" />
	<input type="hidden" name="tradeNature" value="' . $data['tradeNature'] . '" />
	<input type="hidden" name="customsExt" value="' . $data['customsExt'] . '" />
	<input type="hidden" name="signMsg" id="signMsg" value="' . $data['signMsg'] . '" />
	
<!--================= post 方式提交支付请求 end =====================-->
</form>
<script type="text/javascript">document.getElementById("myForm").submit();</script></body></html>';

        return $StrHtml;
    }
    //接入互联网网关地址,默认为正式环境
    protected function serverUrl($paytype, $type = 0)
    {
        $url = '';
        if ($paytype == 'B2C' || $paytype == 'B2B' || $paytype == 'PC') {
            $url = 'https://cashier.allinpay.com/gateway/index.do';
            if (($paytype == 'B2C' || $paytype == 'B2B') && $type != 0) {
                $url = 'http://ceshi.allinpay.com/gateway/index.do';
            }
        } else if ($paytype == 'H5' || $paytype == 'WAP') {
            $url = 'https://cashier.allinpay.com/mobilepayment/mobile/SaveMchtOrderServlet.action';
            if ($paytype == 'WAP' && $type != 0) {
                $url = 'http://ceshi.allinpay.com/mobilepayment/mobile/SaveMchtOrderServlet.action';
            }
        } else if ($paytype == 'gateway') {

            if ($type != 0) {
                $url = 'http://ceshi.allinpay.com/gateway/index.do';
            } else {
                $url = 'https://cashier.allinpay.com/gateway/index.do';
            }
        } else if ($paytype == 'mchtoq') {

            if ($type != 0) {
                $url = 'http://ceshi.allinpay.com/mchtoq/index.do';
            } else {
                $url = 'https://cashier.allinpay.com/mchtoq/index.do';
            }
        } else if ($paytype == 'refundquery') {
            if ($type != 0) {
                $url = 'http://ceshi.allinpay.com/mchtoq/refundQuery';
            } else {
                $url = 'https://cashier.allinpay.com/mchtoq/refundQuery';
            }
        } else if ($paytype == 'mchtRate') {
            if ($type != 0) {
                $url = 'http://ceshi.allinpay.com/mchtoq/mchtRate';
            } else {
                $url = ' https://cashier.allinpay.com/mchtoq/mchtRate';
            }
        }else if($paytype == 'download'){
            $url = 'https://merchant.allinpay.com/ms/onlinebill/download';
        }
        return $url;
    }
}
