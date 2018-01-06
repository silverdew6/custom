<?php
class ectools_api_payment_tlwx{
    public $apiDescription = "通联微信支付";
    public function getParams()
    {
        $return['params'] = array(
            'orderNo'                   => ['type'=>'string','valid'=>'', 'description'=>'订单号', 'default'=>'required', 'example'=>''],
            'orderAmount'               => ['type'=>'string','valid'=>'', 'description'=>'订单金额', 'default'=>'', 'example'=>''],
            'productName'               => ['type'=>'string','valid'=>'', 'description'=>'商品描述', 'default'=>'', 'example'=>''],
            'type'                      => ['type'=>'string','valid'=>'', 'description'=>'支付类型', 'default'=>'', 'example'=>''],
            'openid'                    => ['type'=>'string','valid'=>'', 'description'=>'openID', 'default'=>'', 'example'=>''],
            'platform'                  => ['type'=>'string','valid'=>'', 'description'=>'网页类型', 'default'=>'', 'example'=>''],
            'payment_id'                => ['type'=>'string','valid'=>'', 'description'=>'支付单号', 'default'=>'', 'example'=>''],
        );
        return $return;
    }

    //微信支付测试
    public function paytest($params){
        return 456;
        $Objdata                        = kernel::single('ectools_TL_wxpay');
        $res                            = $Objdata-> paytest($params);
        return $res;
    }
    //微信，支付宝交易查询,返回查询状态
    public function query($params){
        $Objdata                        = kernel::single('ectools_TL_wxpay');
        $res                            = $Objdata-> query($params);
        return $res;
    }
    //取消交易 //退款
    public function cancel($params){
        $Objdata                        = kernel::single('ectools_TL_wxpay');
        $res                            = $Objdata-> cancel($params);
        return $res;

    }

}


