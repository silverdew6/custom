<?php
use Endroid\QrCode\QrCode;
class ectools_TL_wxpay{
    protected $cusid    = '142581072993330';
    protected $appid    = '00008692';
    protected $key      = '1234567890';
    protected $version ;
    protected $url      = 'https://vsp.allinpay.com/apiweb/unitorder/';

    function __construct(){
        header("Content-Type: text/html;charset=utf-8");
        //配置测试用正式环境
        $this->key      = '5698741236';
        $this->cusid    = '454584053118737';
        $this->appid    = '00013329';
        $this->version  = '11';

        $this->appUtil  = kernel::single('ectools_TL_AppUtil');
    }
    //微信支付测试
    public function paytest($data){
        $url        = $this->url.'pay';
        $AppUtil    = $this->appUtil;
        $paytype    = $data['type'];

        if($paytype == 'W02'){
            $validtime  = 15;  //<60
            $acct       = $data['openid'];
            $notify_url = url::to('wap/TLNotify.html');
        }else{
            $notify_url = url::to('TLNotify.html');
        }
        $param['cusid']     = $this->cusid;
        $param['appid']     = $this->appid;
        $param['version']   = $this->version;
        $param['trxamt']    = $data['orderAmount'];
        $param['reqsn']     = $data['orderNo'];
        $param['paytype']   = $paytype;
        $param['randomstr'] = $AppUtil->getNonceStr();            //随机字符串
        $param['body']      = $data['productName'];            //订单标题
        if(!empty($validtime)){
            $param['validtime'] = $validtime;
        }
        if(!empty($acct)){
            $param['acct'] = $acct;         //微信公众号无法测试
        }
        $param['notify_url']    = $notify_url;
        $param['sign']      = $AppUtil->SignArray($param,$this->key);
        $paramsStr          = $AppUtil->ToUrlParams($param);
        $rsp                = $this->request($url, $paramsStr);
        $rspArray           = json_decode($rsp, true);

        //验签
        if($this->validSign($rspArray)){   //验签成功,则显示后续
            if($rspArray['trxstatus'] == '0000' ){  //trxid--交易流水号，chnltrxid--渠道平台交易单号
                $retData['payinfo']     = $rspArray['payinfo'];    //二维码中数据
                $retData['payment_id']  = $data['payment_id'];

                if($paytype == 'A01' ||$paytype == 'W01'){
                    $retData['type']        = $paytype;//return $retData;
                    $retData['orderNo']     = $data['orderNo'];
                    $retData['money']     = $data['orderAmount']/100;
                    //获取二维码路径
                    $qrcode_url         = $rspArray['payinfo'];
                    $retData['url']     = $this->_build_qrcode_img_copy($qrcode_url);
                    return $retData;
                }else{
                    return $retData;
                }
            }else{
                if(!empty($rspArray['errmsg'])){
                    return $rspArray['errmsg'];
                }
                $str = '';
                switch ($rspArray['trxstatus']){
                    case 3045:
                        $str='交易超时';break;
                    case 3088:
                        $str='交易超时';break;
                    case 3008:
                        $str='余额不足';break;
                    case 3999:
                        $str='交易失败';break;
                    case 2008:
                        $str='交易处理中';break;
                    case 3050:
                        $str='交易已撤销';break;
                    default :
                        $str='交易失败';break;
                }
                return $str;
            }
        }else{
            return $rspArray["retmsg"];
        }
    }
    //微信，支付宝交易查询,返回查询状态
    public function query($data){
        $url                        = $this->url.'query';
        $AppUtil                    = $this->appUtil;
        $param['cusid']             = $this->cusid;
        $param['appid']             = $this->appid;
        $param['version']           = $this->version;
        $param['reqsn']             = $data['orderNo'];
        $param['randomstr']         = $AppUtil->getNonceStr();;
        $param['sign']              = $AppUtil->SignArray($param,$this->key);//签名
        $paramsStr                  = $AppUtil->ToUrlParams($param);
        $rsp                        = $this->request($url, $paramsStr);
        $rspArray                   = json_decode($rsp, true);
        /* echo "<pre>";
        print_r($rspArray);
        echo "</pre>";exit;*/
        if($this->validSign($rspArray)){  //交易单号
            $retData ['trxstatus']  = $rspArray['trxstatus'];
            $retData ['type']       = $rspArray['trxcode'];
            if($rspArray['trxstatus'] == '0000'){
                $retData ['paymentOrd'] = $rspArray['trxid'];//第三方交易单号
                $retData ['orderNo']    = $rspArray['reqsn'];
                $retData ['merId']      = $rspArray['cusid'];
                return $retData;
            }else{
                $str = '';
                switch ($rspArray['trxstatus']){
                    case 3045:
                        $str='交易超时';break;
                    case 3088:
                        $str='交易超时';break;
                    case 3008:
                        $str='余额不足';break;
                    case 3999:
                        $str='交易失败';break;
                    case 2008:
                        $str='交易处理中';break;
                    case 3050:
                        $str='交易已撤销';break;
                    default :
                        $str='交易失败';break;
                }
                return $str;
            }
        }
        return false;
    }
    //取消交易 //退款
    /*public function cancel($data){
        $url                = $this->url.'cancel';
        $AppUtil            = $this->appUtil;

        $param['cusid']     = $this->cusid;
        $param['appid']     = $this->appid;
        $param['version']   = $this->version;
        $param['reqsn']     = $data['orderNo'];
        $param['trxamt']    = $data['orderAmount'];//交易金额
        $param["oldreqsn"]  = $data['orderNo'];
        $param['randomstr'] = $AppUtil->getNonceStr();
        $param['sign']      = $AppUtil->SignArray($param,$this->key);

        $paramsStr          = $AppUtil->ToUrlParams($param);

        $rsp                = $this->request($url, $paramsStr);
        $rspArray           = json_decode($rsp, true);//return $rspArray;
        if($this->validSign($rspArray)){
            if($rspArray['trxstatus'] == '0000'){
                return '退款成功';
            }
        }else{
            return false;
        }
    }*/
    //退款
    public function cancel($data){
        $url = "https://vsp.allinpay.com/apiweb/unitorder/refund";
        $AppUtil            = $this->appUtil;

        $param['cusid']     = $this->cusid;
        $param['appid']     = $this->appid;
        $param['version']   = $this->version;
        $param['trxamt']    = $data['orderAmount'];//交易金额
        $param['reqsn']     = $data['orderNo'];
        $param["oldreqsn"]  = $data['orderNo'];
        $param['randomstr'] = $AppUtil->getNonceStr();
        $param['sign']      = $AppUtil->SignArray($param,$this->key);

        $paramsStr          = $AppUtil->ToUrlParams($param);

        $rsp                = $this->request($url, $paramsStr);
        $rspArray           = json_decode($rsp, true);//return $rspArray;
        /*echo "<pre>";
        print_r($rspArray);
        echo "</pre>";exit;*/
        if($this->validSign($rspArray)){
            if($rspArray['trxstatus'] == '0000'){
                return '退款成功';
            }
        }
        return $rspArray['retmsg'];
    }
    //生成二维码
    private function _build_qrcode_img_copy($url){
        $qrCode = new QrCode();
        return $qrCode
            ->setText($url)
            ->setSize(200)
            ->setPadding(30)
            ->setErrorCorrection(1)
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabelFontSize(16)
            ->getDataUri('png');
    }
    //发送请求操作仅供参考,不为最佳实践
    function request($url,$params){
        $ch = curl_init();
        $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
        curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//如果不加验证,就设false,商户自行处理
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $output = curl_exec($ch);
        curl_close($ch);
        return  $output;
    }
    //验签
    function validSign($array){
        $AppUtil = $this->appUtil;
        if("SUCCESS"==$array["retcode"]){
            $signRsp = strtolower($array["sign"]);
            $array["sign"] = "";
            $sign =  strtolower($AppUtil->SignArray($array,$this->key));
            if($sign==$signRsp){
                return $sign;
            }
            else {
                //echo "验签失败:".$signRsp."--".$sign;
                return FALSE;
            }
        }
        else{
            //echo $array["retmsg"];
            return FALSE;
        }

        return FALSE;
    }
    //回调
    public function notify($data){
        $AppUtil = $this->appUtil;
        $params = array();
        $str = '';
        foreach($data as $key=>$val) {//动态遍历获取所有收到的参数,此步非常关键,因为收银宝以后可能会加字段,动态获取可以兼容由于收银宝加字段而引起的签名异常
            $params[$key] = $val;
            $str .= $key.'='.$val.'&';
        }
        //将获取的字段写入日志文件
//        $path = $_SERVER['DOCUMENT_ROOT'].'/logs/'.date('Ymd').'.log';
//        $fp=fopen($path,'a+');
//        $con= "\r\n".$str;
//        fwrite($fp,$con);
//        fclose($fp);

        if(count($params)<1){//如果参数为空,则不进行处理
            return "error";
        }

        //验签处理
        $sign           = $params['sign'];
        unset($params['sign']);
        $params['key']  = $this->key;
        $mySign         = $AppUtil->SignArray($params, $this->key);

        if(strtolower($sign) == strtolower($mySign)){//验签成功

            //此处进行业务逻辑处理
            $orderNo    = $data['cusorderid'];   //商户订单号
            $money      = $data['trxamt']/100;
            if( $data['trxstatus']  == '0000' ){

                if( $data['trxcode'] == 'VSP501' ){
                    $type = 'W01';
                }else if($data['trxcode']=='VSP511'){
                    $type = 'A01';
                }

                $ret_data   = array(
                    'orderNo'       => $orderNo,
                    'str'           => 'succ',
                    'money'         => $money,
                    'time'          => time(),
                    'merId'         => '',//$data["cusid"],    //商户号
                    'paymentOrd'    => '',//$data['chnltrxid'],   //渠道交易单号
                    'type'          => $type                 //交易类型
                );
            }else{
                $ret_data = array(
                    'orderNo'       => $orderNo,
                    'str'           => 'failed',
                    'time'          => time()
                );
            }
            if( $ret_data ){
                $Paydata            = kernel::single('ectools_TL_tlpay');
                $Paydata->payTable($ret_data);
            }
            return "success";
        }
        else{
            return  "erro";
        }
    }
}