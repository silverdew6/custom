<?php
class ectools_api_payment_tlkj{
    public $apiDescription = "通联快捷支付";


    public function getParams()
    {
        $return['params'] = array(
            'orderNo'           => ['type'=>'string','valid'=>'', 'description'=>'订单号', 'default'=>'required', 'example'=>''],
            'orderAmount'       => ['type'=>'string','valid'=>'', 'description'=>'订单金额', 'default'=>'', 'example'=>''],
            'productName'       => ['type'=>'string','valid'=>'', 'description'=>'商品描述', 'default'=>'', 'example'=>''],
            'type'              => ['type'=>'string','valid'=>'', 'description'=>'交易类型', 'default'=>'', 'example'=>''],
            'orderDatetime'     => ['type'=>'string','valid'=>'', 'description'=>'交易时间', 'default'=>'', 'example'=>''],
            'userid'            => ['type'=>'string','valid'=>'', 'description'=>'用户ID', 'default'=>'', 'example'=>''],

        );
        return $return;
    }
    //用户注册请求接口
    public function UserInfo($params)
    {
        $userId                         = $params['userid'];
        $Objdata                        = kernel::single('ectools_TL_kjpay');
        $res                            = $Objdata-> UserInfo($userId);
        return $res;
    }
    //支付
    public function pay($params)
    {
        if ($params['type'] == 'PC' || $params['type'] == 'H5')
        {
            $params['kjType']               = 33;
            //获取memid
            $objUser                        = app::get('sysuser')->model('tl_mem');
            $res_user                       = $objUser->getRow('memid', ['user_id' => $params['userid']]);
            if (!empty($res_user))
            {
                $params['userid']           = $res_user['memid'];
            } else {
                $params['userid']           = $this->UserInfo($params);
            }
            if (empty($params['userid']))
            {
                return '获取通联用户编号失败';
            }
        }
        if($params['type'] == 'PC'){
            $params['receiveUrl']       = url::to('finish.html');
            $params['pickupUrl']        = url::to('tlpick.html');
        }else{
            $params['receiveUrl']       = url::to('wap/finish.html');
            $params['pickupUrl']        = url::to('wap/tlpick.html');
        }
        $Objdata                        = kernel::single('ectools_TL_kjpay');
        $res                            = $Objdata->pay($params);

        return $res;
    }
    //单笔订单查询接口
    public function gateway($params){
        $Objdata                        = kernel::single('ectools_TL_kjpay');
        $res                            = $Objdata-> gateway($params);
        return $res;
    }
    //单笔订单退款申请接口
    public function refund($params)
    {
        $Objdata                = kernel::single('ectools_TL_kjpay');
        $payerM                 = $Objdata->refund($params);
        return $payerM;
    }
    //退款状态接口查询
    public function refundQuery($params)
    {
        $Objdata                        = kernel::single('ectools_TL_kjpay');
        return                            $Objdata-> refundQuery($params);
    }
}


