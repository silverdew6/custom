<?php
class ectools_api_payment_declare{
    public $apiDescription = "通联报关";
    public function getParams()
    {
        $return['params'] = array(
            'payment_id' => ['type'=>'string','valid'=>'', 'description'=>'支付id', 'default'=>'', 'example'=>''],
        );
        return $return;
    }
    //报关提交
    public function subMess($params){
        //$url                        = 'http://w.allinpaysz.com/CRM/gateway_customs.action';//正式环境
        $url = 'http://119.29.103.158:9090/CRM/gateway_customs.action';
        $key                        = 'e763845c89aa4641b1c4500db92784f6';

        $decData['amountPaid']      =   '1';
        $decData['ebpCode']         =   'test';
        $decData['ebpName']         =   'test';
        $decData['merchantNo']      =   '600024';
        $decData['payerIdNumber']   =   '460031198806150001';
        $decData['payerName']       =   '张三';
        $decData['telephone']       =   '13800138000';

        $decData['payTime']         =   '20171024090415';
        $decData['orderNo']         =   '660112';
        $decData['transactionNo']   =   '660112895';
        $decData['submitFlowNo']    =   '201710240904151149';    //具有唯一性

        $jsonData                   =   $this->getSign($decData,$key);
        $jsoninfo                   =   $this->http_post_json($url,$jsonData);
        $user                       =   json_decode($jsoninfo,true);
        if($user['code'] == 0){
            //报文提交成功
            return $user;
        }else if($user['coed'] == -1){
            return $user['message'];         //报错信息
        }

    }

    //报关查询
    public function query($params){
        //$url = 'http://w.allinpaysz.com/CRM/gateway_query.action';
        $url = 'http://119.29.103.158:9090/CRM/gateway_query.action';//测试
        $key                        = 'e763845c89aa4641b1c4500db92784f6';
        $decData['merchantNo']      =   '600024';
        $decData['orderNo']         =   '660112';
        $jsonData                   =   $this->getSign($decData,$key);
        $jsoninfo                   =   $this->http_post_json($url,$jsonData);
        $user                       =   json_decode($jsoninfo,true);
        if($user['code']==0){
            //访问成功

        }else{
            //访问失败

        }
        return $user;
    }

    //报关上传，无用
    public function insert($params){

        //$url = 'http://w.allinpaysz.com/CRM/gateway_insert.action';
        $url                            = 'http://119.29.103.158:9090/CRM/gateway_insert.action';//测试
        $key                            = 'ef989515dfaf4ad5b5382dbdb412e236';

        $decData['merchantNo']          =   '600036';
        $decData['networkNo']           =   '100020091218001';
        $decData['tradeType']           =   1;
        $decData['merchantOrderNo']     =   '201710241037177066';
        $decData['flowNo']              =   '201710241037177066';
        $decData['amount']              =   100;
        $decData['createTime']          =   '20171024103717';

        $jsonData                       =   $this->getSign($decData,$key);
        $jsoninfo                       =   $this->http_post_json($url,$jsonData);
        $user                           =   json_decode($jsoninfo,true);
        if($user['retcode'] == 1){
            return $user['retmsg']; //交易记录添加完成
        }else if($user['code'] == -1){
            return $user['message'];
        }
    }

    //生成签名串,返回要传输的json数据
    protected function getSign(&$array,$key){
        ksort($array);
        $buff                   = "";
        foreach ($array as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff .= 'key='.$key;
        $array['sign']              = strtoupper(md5($buff));
        $jsonData                   = $this->jsons_encode($array);
        return $jsonData;
    }
    //数组转换成字符串时保留中文字符
    protected function jsons_encode(&$array){
        foreach($array as $key=>$value){
            $array[$key] = urlencode($value);
        }
        return urldecode(json_encode($array));
    }
    //程序用curl访问接口$url,参数为$data,$data可为json格式
    protected function http_post_json($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); //200
        return $response;
    }

}


