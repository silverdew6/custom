<?php
class ectools_api_payment_tlsql{
    public $apiDescription = "通联支付数据操作";
    public function getParams()
    {
        $return['params'] = array(
            'orderNo'       => ['type'=>'string','valid'=>'', 'description'=>'订单号', 'default'=>'required', 'example'=>''],
            'str'           => ['type'=>'string','valid'=>'', 'description'=>'交易状态', 'default'=>'required', 'example'=>''],
            'money'         => ['type'=>'string','valid'=>'', 'description'=>'交易金额', 'default'=>'', 'example'=>''],
            'time'          => ['type'=>'string','valid'=>'', 'description'=>'交易时间', 'default'=>'', 'example'=>''],
            'merId'         => ['type'=>'string','valid'=>'', 'description'=>'', 'default'=>'', 'example'=>''],
            'paymentOrd'    => ['type'=>'string','valid'=>'', 'description'=>'', 'default'=>'', 'example'=>''],
            'type'          => ['type'=>'string','valid'=>'', 'description'=>'交易类型', 'default'=>'', 'example'=>''],
        );
        return $return;
    }
    //数据库操作
    public function payTable($params){
        $orNo           = $params['orderNo'];
        $str            = $params['str'];
        $money          = $params['money'];
        $time           = $params['time'];
        $merId          = $params['merId'];
        $paymentOrd     = $params['paymentOrd'];
        $type           = trim($params['type']);

        $paytype        = 'TL';
        if(!empty($type)){
            $paytype = 'TL-'.$type;
        }
        //订单信息
        $objTrade               = app::get('systrade')->model('trade');
        $objOrder               = app::get('systrade')->model('order');
        //支付相关
        $objMdlPayment          = app::get('ectools')->model('payments');
        $objMdlTradePaybill     = app::get('ectools')->model('trade_paybill');
        $filterBill['tid']      = $orNo;

        if($str == 'succ'){
            //支付单参数
            $retPay['money']       = $money;   //需支付的金额
            $retPay['cur_money']   = $money;   //支付货币金额
            $retPay['status']      = $str;
            $retPay['pay_type']    = 'online';
            $retPay['pay_app_id']  = "TL-pay";
            $retPay['pay_name']    = '通联';
            $retPay['payed_time']  = $time;
            $retPay['account']     = $merId;   //收款账号,商户号
            $retPay['bank']        = $paytype;//app::get('ectools')->_($paytype);//收款银行
            $retPay['currency']    = 'CNY';
            $retPay['paycost']     = '0.000';    //支付网关费用
            $retPay['modified_time']     = $time;    //最后更新时间
            $retPay['trade_no']    = $paymentOrd;       //通联订单号

            //支付明细
            $retBill['status']     = $str;
            $retBill['payment']    = $money;
            $retBill['payed_time'] = $time;
            $retBill['modified_time']    = $time;

            //订单相关
            $retTrade['status']     = 'WAIT_SELLER_SEND_GOODS';
            $retTrade['payment']    = $money;
            $retTrade['payed_fee']  = $money;
            $retTrade['pay_time']   = $time;
            $retTrade['modified_time'] = $time;

            //订单明细
            $retOrd['pay_time']         = $time;
            $retOrd['modified_time']    = $time;
            $retOrd['status'] = 'WAIT_SELLER_SEND_GOODS';

        }else{
            //支付单参数
            $retPay['status']            = $str;
            $retPay['modified_time']     = $time;    //最后更新时间

            //支付明细
            $retBill['status']           = $str;
            $retBill['modified_time']    = $time;

            //订单相关
            $retTrade['status'] = 'TRADE_CLOSED_BY_SYSTEM';   //订单支付失败时需提交，取消订单信息
            $retTrade['cancel_reason'] = 'tl支付未在指定时间内完成支付';
            $retTrade['modified_time'] = $time;

            //订单明细
            $retOrd['pay_time']         = $time;
            $retOrd['modified_time']    = $time;
            $retOrd['status']           = 'TRADE_CLOSED_BEFORE_PAY';

        }
        $db                              = app::get('systrade')->database();
        $db->beginTransaction();
        try{
            $resBill = $objMdlTradePaybill->update( $retBill,$filterBill);
            if(!$resBill)
            {
                throw new Exception('trade_paybill表操作失败');
            }//修改支付明细信息

            $billList = $objMdlTradePaybill->getRow('payment_id',$filterBill);
            if(empty($billList)){
                throw new Exception('订单明细查询失败');
            }
            $filePay['payment_id'] = $billList['payment_id'];

            if($str == 'failed'){
                $Row = 'shop_id,user_id,cancel_reason,pay_type,payed_fee';
                $tradeList = $objTrade -> getRow($Row ,$filterBill);

                //关闭订单详情
                $retCancel['user_id']        = $tradeList['shop_id'];
                $retCancel['shop_id']        = $tradeList['user_id'];
                $retCancel['tid']            = $orNo;
                $retCancel['pay_type']       = $tradeList['pay_type'];
                $retCancel['payed_fee']      = $tradeList['payed_fee'];
                $retCancel['reason']         = $tradeList['cancel_reason'];
                $retCancel['shop_reject_reason'] = null;
                $retCancel['cancel_from']    = 'shopadmin';
                $retCancel['process']        = '3';
                $retCancel['refunds_status'] = 'SUCCESS';
                $retCancel['created_time']   = time();
                $retCancel['modified_time']  = time();

                $objCancel = app::get('systrade')->model('trade_cancel');
                $result     = $objCancel -> save($retCancel);
                if(!$result)
                {
                    throw new Exception('数据插入失败');
                }
            }
            $resPay = $objMdlPayment->update( $retPay , $filePay );   //修改支付信息
            if(!$resPay)
            {
                throw new Exception('payments表操作失败');
            }
            $db->commit();
        }
        catch(Exception $e)
        {
            $db->rollback();
            throw $e;exit();
        }

        $db                              = app::get('systrade')->database();
        $db->beginTransaction();
        try{

            $resTrade = $objTrade -> update( $retTrade , $filterBill );   //修改订单信息
            if(!$resTrade)
            {
                throw new Exception('trade表操作失败');
            }
            $resOrder = $objOrder -> update(  $retOrd, $filterBill );
            if(!$resOrder)
            {
                throw new Exception('order表操作失败');
            }
            $db->commit();
        }
        catch(Exception $e)
        {
            $db->rollback();
            throw $e;exit();
        }

        //生成报文
        app::get('sysshop')->rpcCall('xml.message.create',$filePay);
        return $filePay;
}
}


