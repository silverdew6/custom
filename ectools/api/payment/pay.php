<?php
class ectools_api_payment_pay{
    public $apiDescription = "订单支付请求支付网关";
    public function getParams()
    {
        $return['params'] = array(
            'payment_id' => ['type'=>'string','valid'=>'required', 'description'=>'支付单编号', 'default'=>'', 'example'=>''],
            'pay_app_id' => ['type'=>'string','valid'=>'required', 'description'=>'支付方式', 'default'=>'', 'example'=>'alipay'],
            'platform' => ['type'=>'string','valid'=>'required', 'description'=>'来源平台（wap、pc）', 'default'=>'pc', 'example'=>'pc'],
            'money' => ['type'=>'string','valid'=>'required', 'description'=>'支付金额', 'default'=>'', 'example'=>'234.50'],
            'deposit_password' => ['type'=>'string','valid'=>'', 'description'=>'预存款支付密码', 'default'=>'', 'example'=>'234.50'],
            'user_id' => ['type'=>'string','valid'=>'required', 'description'=>'用户id', 'default'=>'', 'example'=>'1'],
            'tids' => ['type'=>'string','valid'=>'required', 'description'=>'被支付的订单号集合,用逗号隔开', 'default'=>'', 'example'=>'1241231213432,2354234523452'],
            //'itemtype' => ['type'=>'string','valid'=>'', 'description'=>'商品类型', 'default'=>'', 'example'=>''],
        );
        return $return;
    }
    public function doPay($params)
    {
        if(!$params['platform'])
        {
            $params['platform'] = "pc";
        }
        $objMdlPayments = app::get('ectools')->model('payments');
        $objMdlPayBill = app::get('ectools')->model('trade_paybill');
        $paymentBill = $objMdlPayments->getRow('payment_id,status,money,pay_type,currency,cur_money,pay_app_id',array('payment_id'=>$params['payment_id']));
        //订单状态判断
        $tradeData['tid'] = $params['tids'];
        $tradeData['fields'] = 'status,created_time';
        $tradeInfo = app::get('systrade')->rpcCall('trade.get',$tradeData);
        if($tradeInfo['status']=='TRADE_CLOSED')
        {
            throw new Exception('该订单已经被取消！');
        }
        if($paymentBill['status'] == 'succ' || $paymentBill['status'] == 'progress')
        {
            throw new Exception('该订单已经支付');
        }
        $tradePayBill = $objMdlPayBill->getList('tid',array('payment_id'=>$params['payment_id']));
        $payTids = array_bind_key($tradePayBill,'tid');
        $tids['tid'] = $params['tids'];
        $tids['fields'] = "payment,tid,status,order.title";
        $trades = app::get('ectools')->rpcCall('trade.get.list',$tids);
        $totalMoney = array_sum(array_column($trades['list'],'payment'));
        if((string)$totalMoney != $params['money'])
        {
            throw new Exception('订单金额与需要支付金额不一致，请核对后支付');
        }
        $db = app::get('ectools')->database();
        $db->beginTransaction();
        try{
            $return_url = array("topc_ctl_paycenter@finish",array('payment_id'=>$params['payment_id']));
            if($params['platform'] == "wap")
            {
                $return_url = array("topm_ctl_paycenter@finish",array('payment_id'=>$params['payment_id']));
            }
            $paymentData = array(
                'money' => $params['money'],
                'cur_money' => $params['money'],
                'status' => 'paying',
                'pay_app_id' => $params['pay_app_id'],
                'return_url' => $return_url,
            );
            $paymentFilter['payment_id'] = $params['payment_id'];
            $result = $objMdlPayments->update($paymentData,$paymentFilter);

            if(!$result)
            {
                throw new Exception('支付失败，支付单更新失败');
            }

            foreach($trades['list'] as $val)
            {
                $data['payment'] = $val['payment'];
                $data['status'] = 'paying';
                $data['modified_time'] = time();
                $filter['tid'] = $val['tid'];
                $filter['payment_id'] = $params['payment_id'];
                $result = $objMdlPayBill->update($data,$filter);           //result=1
                $params['item_title'][] = $val['order'][0]['title'];

                if(!$result)
                {
                    throw new Exception('支付失败，支付单明细更新失败');
                }
                if($payTids[$val['tid']])
                {
                    unset($payTids[$val['tid']]);
                }
            }

            if($payTids)
            {
                $deleteParams['tid'] = array_keys($payTids);
                $deleteParams['payment_id'] = $params['payment_id'];
                $result = $objMdlPayBill->delete($deleteParams);
                if(!$result)
                {
                    throw new Exception('支付失败，清除过期数据失败');
                }
            }

            $db->commit();
        }
        catch(Exception $e)
        {
            $db->rollback();
            throw $e;
        }
        //pc快捷支付
        if($params['pay_app_id']=='TL-pay'){
            $tlpay['platform']      = $params['platform'];
            $tlpay['payment_id']    = $params['payment_id'];
            $tlpay['orderNo']       = $params['tids'];
            $tlpay['orderAmount']   = $params['money']*100;
            $tlpay['productName']   = $params['item_title'][0];
            $tlpay['type']          = $params['type'];   //用来区分类型
            if($params['type']=='PC' || $params['type']=='H5'){
                $time                       = $tradeInfo['created_time'];
                $tlpay['orderDatetime']     = date('YmdHis',$time);
                $tlpay['userid']            = $params['user_id'];
                $res                        = app::get('ectools')->rpcCall('tlpay.kjpay.pay',$tlpay);
                if($res){
                    throw new Exception($res);
                }else{
                    exit;
                }
            }else{   //支付宝，微信
                $objMdlAcc          = app::get('sysuser')->model('account');
                $resOp              = $objMdlAcc->getRow('openid',['user_id'=>$params['user_id']]);
                $tlpay['openid']    = $resOp['openid'];                            //var_dump($tlpay);exit;
                $Objdata            = kernel::single('ectools_TL_wxpay');
                //return  $Objdata->cancel($tlpay);//取消交易
                $res                = $Objdata-> paytest($tlpay);
                if(is_array($res)){
                    return $res;
                }else{
                    throw new Exception($res);
                }
            }
        }
        $paymentBill['pay_app_id'] = $params['pay_app_id'];
        $paymentBill['item_title'] = $params['item_title'][0];
        $paymentBill['deposit_password'] = $params['deposit_password'];
        $objPayment = kernel::single('ectools_pay');
        $result = $objPayment->generate($paymentBill);

        if(!$result)
        {
            throw new Exception('支付失败,请求支付网关出错');
        }else if(!empty($result) && $result !== true){
            return $result;
        }else{
            return true;
        }

    }
}


