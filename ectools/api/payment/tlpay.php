<?php
class ectools_api_payment_tlpay{
    public $apiDescription = "通联支付回调";
    public $key = '5698741236';
    public function getParams()
    {
        $return['params'] = array(
            'acct'          => ['type'=>'string','valid'=>'', 'description'=>'支付人帐号', 'default'=>'', 'example'=>''],
            'appid'         => ['type'=>'string','valid'=>'', 'description'=>'应用ID', 'default'=>'', 'example'=>''],
            'chnltrxid'     => ['type'=>'string','valid'=>'', 'description'=>'渠道交易单号', 'default'=>'', 'example'=>''],
            'cusid'         => ['type'=>'string','valid'=>'', 'description'=>'商户号', 'default'=>'', 'example'=>''],
            'cusorderid'    => ['type'=>'string','valid'=>'', 'description'=>'商户订单号', 'default'=>'', 'example'=>''],
            'outtrxid'      => ['type'=>'string','valid'=>'', 'description'=>'收银宝平台流水号', 'default'=>'', 'example'=>''],
            'paytime'       => ['type'=>'string','valid'=>'', 'description'=>'交易完成时间', 'default'=>'', 'example'=>''],
            'sign'          => ['type'=>'string','valid'=>'', 'description'=>'签名信息', 'default'=>'', 'example'=>''],
            'termauthno'    => ['type'=>'string','valid'=>'', 'description'=>'终端授权码', 'default'=>'', 'example'=>''],
            'termrefnum'    => ['type'=>'string','valid'=>'', 'description'=>'终端参考号', 'default'=>'', 'example'=>''],
            'termtraceno'   => ['type'=>'string','valid'=>'', 'description'=>'终端流水号', 'default'=>'', 'example'=>''],
            'trxcode'       => ['type'=>'string','valid'=>'', 'description'=>'交易类型', 'default'=>'', 'example'=>''],
            'trxamt'        => ['type'=>'string','valid'=>'', 'description'=>'交易金额', 'default'=>'', 'example'=>''],
            'trxdate'       => ['type'=>'string','valid'=>'', 'description'=>'交易请求日期', 'default'=>'', 'example'=>''],
            'trxid'         => ['type'=>'string','valid'=>'', 'description'=>'收银宝平台流水号', 'default'=>'', 'example'=>''],
            'trxstatus'     => ['type'=>'string','valid'=>'', 'description'=>'交易状态', 'default'=>'', 'example'=>''],

        );
        return $return;
    }
    public function notify($params)
    {
        unset($params['oauth']);
        //验签
        $paydata = array();
        $str = '';
        foreach($params as $key=>$val) {//动态遍历获取所有收到的参数,此步非常关键,因为收银宝以后可能会加字段,动态获取可以兼容由于收银宝加字段而引起的签名异常
            $paydata[$key] = $val;
            $str .= $key.'='.$val.'&';
        }
        if($str == ''){
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
            $str = 'ERROR--{IP:'.$user_IP.'}{time:'.date('His').'}';
        }

        //将获取的字段写入日志文件
        $path = $_SERVER['DOCUMENT_ROOT'].'/logs/'.date('Ymd').'.log';
        $fp=fopen($path,'a+');
        $con= "\r\n".$str;
        fwrite($fp,$con);
        fclose($fp);

        if(count($paydata)<1){//如果参数为空,则不进行处理
            return "error";
        }

        if($this->ValidSign($paydata, $this->key)){//验签成功
            //验签成功进行数据库操作
            $orderNo    = $paydata['cusorderid'];   //商户订单号
            $str        = 'succ';
            $money      = $paydata['trxamt']/100;
            if( $paydata['trxstatus']  == '0000' ){
                if($paydata['trxcode'] == 'VSP501') {
                    $type = 'W01';

                    $user_id=userAuth::id();
                    if(!empty($user_id)){
                        $filterAcc['user_id']       = $user_id;
                        $objMdlAcc                  = app::get('sysuer')->model('account');
                        $accList                    = $objMdlAcc->getRow('openid',$filterAcc);
                        if(!empty($accList['openid']) && $paydata['acct'] == $accList['openid']){
                            $type = 'W02';
                        }
                    }
                    if(empty($paydata['acct']) || $paydata['acct']=='000000'){
                        $type = 'W02';
                    }
                }else if($paydata['trxcode']=='VSP511'){
                    $type = 'A01';
                }
                $ret_data   = array(
                    'orderNo'       => $orderNo,
                    'str'           => $str,
                    'money'         => $money,
                    'time'          => time(),
                    'merId'         => $paydata["outtrxid"],    //商户号
                    'paymentOrd'    => $paydata['trxid'],   //渠道交易单号
                    'type'          => $type                 //交易类型
                );
            }else{
                $ret_data = array(
                    'orderNo'       => $paydata['cusorderid'],
                    'str'           => 'failed',
                    'time'          => time()
                );
            }
            if( is_array($ret_data) ){

                $res = app::get('ectools')->rpcCall('tlpay.sql.take',$ret_data);
//                $res = $this->payTable($ret_data);
                if(is_array($res)){
                    return 'SUCCESS';
                }

            }
        }
            return 'error';

    }
    /**
     * 将参数数组签名
     */
    protected function SignArray(array $array,$appkey){
        $array['key'] = $appkey;// 将key放到数组中一起进行排序和组装
        ksort($array);
        $blankStr = $this->ToUrlParams($array);
        $sign = strtoupper(md5($blankStr));
        return $sign;
    }

    protected  function ToUrlParams(array $array)
    {
        $buff = "";
        foreach ($array as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 校验签名
     * @param array 参数
     * @param unknown_type appkey
     */
    protected  function ValidSign(array $array,$appkey){
        $sign = $array['sign'];
        unset($array['sign']);
        $array['key'] = $appkey;
        $mySign = $this->SignArray($array, $appkey);
        return strtolower($sign) == strtolower($mySign);
    }
}


