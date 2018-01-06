<?php
class ectools_api_payment_kjreceive{
    public $apiDescription      = "通联快捷支付回调";
    public function getParams()
    {
        $return['params'] = array(
            'type'                      => ['type'=>'string','valid'=>'', 'description'=>'快捷支付类型', 'default'=>'required', 'example'=>''],
            'merchantId'                => ['type'=>'string','valid'=>'', 'description'=>'商户号', 'default'=>'required', 'example'=>''],
            'version'                   => ['type'=>'string','valid'=>'', 'description'=>'网关返回支付结果接口版本', 'default'=>'required', 'example'=>''],
            'language'                  => ['type'=>'string','valid'=>'', 'description'=>'网页显示语言种类', 'default'=>'', 'example'=>''],
            'signType'                  => ['type'=>'string','valid'=>'', 'description'=>'签名类型', 'default'=>'required', 'example'=>''],
            'payType'                   => ['type'=>'string','valid'=>'', 'description'=>'支付方式', 'default'=>'', 'example'=>''],
            'issuerId'                  => ['type'=>'string','valid'=>'', 'description'=>'发卡方机构代码', 'default'=>'', 'example'=>''],
            'paymentOrderId'            => ['type'=>'string','valid'=>'', 'description'=>'通联订单号', 'default'=>'required', 'example'=>''],
            'orderNo'                   => ['type'=>'string','valid'=>'', 'description'=>'商户订单号', 'default'=>'required', 'example'=>''],
            'orderDatetime'             => ['type'=>'string','valid'=>'', 'description'=>'商品订单提交时间', 'default'=>'required', 'example'=>''],
            'orderAmount'               => ['type'=>'string','valid'=>'', 'description'=>'商户订单金额', 'default'=>'required', 'example'=>''],
            'payDatetime'               => ['type'=>'string','valid'=>'', 'description'=>'支付完成时间', 'default'=>'required', 'example'=>''],
            'payAmount'                 => ['type'=>'string','valid'=>'', 'description'=>'订单实际支付金额', 'default'=>'required', 'example'=>''],
            'ext1'                      => ['type'=>'string','valid'=>'', 'description'=>'扩展字段1', 'default'=>'', 'example'=>''],
            'ext2'                      => ['type'=>'string','valid'=>'', 'description'=>'扩展字段2', 'default'=>'', 'example'=>''],
            'payResult'                 => ['type'=>'string','valid'=>'', 'description'=>'处理结果', 'default'=>'required', 'example'=>''],
            'errorCode'                 => ['type'=>'string','valid'=>'', 'description'=>'错误代码', 'default'=>'', 'example'=>''],
            'returnDatetime'            => ['type'=>'string','valid'=>'', 'description'=>'结果返回时间', 'default'=>'required', 'example'=>''],
            'signMsg'                   => ['type'=>'string','valid'=>'', 'description'=>'签名字符串', 'default'=>'required', 'example'=>''],
        );
        return $return;
    }
    //支付结果回调
    public function receive($params)
    {
        $Objdata                        = kernel::single('ectools_TL_kjpay');
        $res                            = $Objdata-> receive($params);
        return $res;
    }
}


