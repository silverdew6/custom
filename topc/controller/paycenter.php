<?php
class topc_ctl_paycenter extends topc_controller
{

    public function __construct($app)
    {
        parent::__construct();
        $this->setLayoutFlag('paycenter');
        // 检测是否登录
    }

    public function index()
    {
        $filter = input::get();
        if (isset($filter['tid']) && $filter['tid']) {
            $pagedata['payment_type'] = "offline";
            $ordersMoney = app::get('topc')->rpcCall('trade.money.get', array('tid' => $filter['tid']), 'buyer');

            if ($ordersMoney) {
                foreach ($ordersMoney as $key => $val) {
                    $newOrders[$val['tid']] = $val['payment'];
                    $newMoney += $val['payment'];
                }
                $paymentBill['money'] = $newMoney;
                $paymentBill['cur_money'] = $newMoney;
            }
            $pagedata['trades'] = $paymentBill;
            $pagedata['payment_type'] = "offline";
            $pagedata['mainfile'] = "topc/payment/payment.html";
            return $this->page('topc/payment/index.html', $pagedata);
        }

        if ($filter['newtrade']) {
            $newtrade = $filter['newtrade'];
            unset($filter['newtrade']);
        }

        if ($filter['merge']) {
            $ifmerge = $filter['merge'];
            unset($filter['merge']);
        }

        //获取可用的支付方式列表
        $filter['fields'] = "*";
        $paymentBill = app::get('topc')->rpcCall('payment.bill.get', $filter, 'buyer');
        if ($paymentBill['status'] == "succ") {
            return $this->finish(['payment_id' => $paymentBill['payment_id']]);
        }

        //检测订单中的金额是否和支付金额一致 及更新支付金额
        $trade = $paymentBill['trade'];
        $tids['tid'] = implode(',', array_keys($trade));
        $ordersMoney = app::get('topc')->rpcCall('trade.money.get', $tids, 'buyer');

        if ($ordersMoney) {
            foreach ($ordersMoney as $key => $val) {
                $newOrders[$val['tid']] = $val['payment'];
                $newMoney += $val['payment'];
            }
            $result = array(
                'trade_own_money' => json_encode($newOrders),
                'money' => $newMoney,
                'cur_money' => $newMoney,
                'payment_id' => $filter['payment_id'],
            );

            if ($newMoney != $paymentBill['cur_money']) {
                try {
                    app::get('topc')->rpcCall('payment.money.update', $result);
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                    $url = url::action('topc_ctl_member_trade@tradeList');
                    return $this->splash('error', $url, $msg, true);
                }
                $paymentBill['money'] = $newMoney;
                $paymentBill['cur_money'] = $newMoney;
            }
        }

        $payType['platform'] = 'ispc';
        $payments = app::get('topc')->rpcCall('payment.get.list', $payType, 'buyer');//var_dump($payments);exit;

        $pagedata['tids'] = $tids['tid'];
        $pagedata['trades'] = $paymentBill;
        $pagedata['payments'] = $payments;//获取支付方式
        $pagedata['newtrade'] = $newtrade;
        $pagedata['mainfile'] = "topc/payment/payment.html";
        $pagedata['hasDepositPassword'] = app::get('topc')->rpcCall('user.deposit.password.has', ['user_id' => userAuth::id()]);

        return $this->page('topc/payment/index.html', $pagedata);
    }

    public function createPay()
    {
        $filter = input::get();
        $filter['user_id'] = userAuth::id();
        $filter['user_name'] = userAuth::getLoginName();
        if ($filter['merge']) {
            $ifmerge = $filter['merge'];
            unset($filter['merge']);
        }

        try {
            $paymentId = kernel::single('topc_payment')->getPaymentId($filter);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $url = url::action('topc_ctl_member_trade@tradeList');
            echo '<meta charset="utf-8"><script>alert("' . $msg . '");location.href="' . $url . '";</script>';
            exit;
        }
        $url = url::action('topc_ctl_paycenter@index', array('payment_id' => $paymentId, 'merge' => $ifmerge));
        return $this->splash('success', $url, $msg, true);
    }

    public function dopayment()
    {
        $postdata = input::get();
        $payment = $postdata['payment'];
        $payment['deposit_password'] = $postdata['deposit_password'];
        $payment['user_id'] = userAuth::id();
        $payment['platform'] = "pc";
        if ($payment['pay_app_id'] == 'PC' || $payment['pay_app_id'] == 'A01' || $payment['pay_app_id'] == 'W01') {
            $payment['type'] = $payment['pay_app_id'];
            $payment['pay_app_id'] = 'TL-pay';

            //$filterBill['tid|in']      = ['31711110955084899','31711110949234899'];

            /*$filePay['orderNo'] = '31711110955084899';//'31711110955084899';//'31711110949234899';
            $tradeData['fields'] = 'status,created_time';
            $tradeData['tid'] = $filePay['orderNo'];
            $tradeData['fields'] = 'payment,created_time';
            $tradeInfo = app::get('systrade')->rpcCall('trade.get',$tradeData);
            $time                       = $tradeInfo['created_time'];

            //$filePay['orderDatetime']     = date('YmdHis',$time);
            $filePay['orderDatetime'] = '20171009150913';
            $filePay['orderAmount']     = $tradeInfo['payment']*100;
//            $res = app::get('ectools')->rpcCall('tlpay.kjpay.refund',$filePay);
//            $Objdata            = kernel::single('ectools_TL_kjpay');
//            $res                = $Objdata-> gateway($filePay);


            $para['orderNo']     = $filePay['orderNo'];
            $para['orderAmount']    = $filePay['orderAmount'];//交易金额
            $Objdata            = kernel::single('ectools_TL_wxpay');
//            $res                = $Objdata-> query($para);
            $res                = $Objdata-> cancel($para);
            var_dump($res);exit;*/

        }

        try {
            $res = app::get('topc')->rpcCall('payment.trade.pay', $payment);//订单支付请求支付网关
            if ($payment['pay_app_id'] == 'TL-pay' && is_array($res)) {
                $money = $res['money'];
                $res['money'] = sprintf("%.2f", $money);
                return $this->page('topc/payment/tlpick.html', $res);
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->errorPay($payment['payment_id'], $msg);
        }
        //查看订单付款状态并做出判断
        $url = url::action('topc_ctl_paycenter@finish', array('payment_id' => $payment['payment_id']));
        return $this->splash('success', $url, $msg, true);
    }

    public function tlpick()
    {
        $dataPost = input::get();      //PC回调返回数组

        try {
            if (empty($dataPost)) {
                throw new LogicException('接收数据为空');
            }
            $objMdlTradePaybill = app::get('ectools')->model('trade_paybill');
            $filterBill['tid'] = $dataPost['orderNo'];
            $billList = $objMdlTradePaybill->getRow('payment_id', $filterBill);
            if (empty($billList)) {
                throw new Exception('订单明细查询失败');
            }

            $filePay['payment_id'] = $billList['payment_id'];

            if ($dataPost['payType'] == '33') {
                //快捷支付
                $dataPost['type'] = 'PC';
                $payer = app::get('ectools')->rpcCall('tlpay.kjpay.receive', $dataPost);
                if (is_array($payer)) {
                    $paytable = app::get('ectools')->rpcCall('tlpay.sql.take', $payer);
                } else {
                    throw new LogicException($payer);
                }
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $url = url::action('topc_ctl_paycenter@index', $filePay);
            echo "<meta charset='utf-8'><input type='hidden' id='msg' value='" . $msg . "'><input type='hidden' id='url' value='" . $url . "'><script>alert(document.getElementById('msg').value);location.href=document.getElementById('url').value;</script>";
            exit();

            //echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';exit();
        }
        return $this->finish($filePay);
    }

    //用来确认支付单是否支付成功
    public function checkPayments()
    {
        $postdata = input::get();
        if (!is_numeric($postdata['payment_id'])) {
            $this->splash('failed', null, "payment_id格式错误", true);
            exit;
        }
        $params['payment_id'] = $postdata['payment_id'];
        $result = app::get('topc')->rpcCall('payment.checkpayment.statu', $params);
        return $result;
    }

    public function finish($postdata = array())
    {
        if (!$postdata) {
            $postdata = input::get();
        }

        //查看订单付款状态并做出判断
        $params['payment_id'] = $postdata['payment_id'];
        $result = app::get('topc')->rpcCall('payment.checkpayment.statu', $params);//根据支付单检查支付单的状态
        if ($result != 'succ') {
            $msg = '订单支付失败，请重试';
            return $this->errorPay($params['payment_id'], $msg, $result);
        }

        try {
            $params['payment_id'] = $postdata['payment_id'];
            $params['fields'] = 'payment_id,status,pay_app_id,pay_name,money,cur_money';
            $result = app::get('topc')->rpcCall('payment.bill.get', $params);//获取支付单信息
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }


        //生成报文
        //$params['payment_id']='15113014540544614851';
//              $params=[];
//              $params['payment_id'] = $postdata['payment_id'];
//        app::get('sysshop')->rpcCall('xml.message.create',$params);

        $result['num'] = count($result['trade']);
        $pagedata['msg'] = $msg;
        $pagedata['payment'] = $result;
        $pagedata['mainfile'] = "topc/payment/finish.html";
        return $this->page('topc/payment/index.html', $pagedata);
    }

    /**
     *  订单错误页面提示
     * @param int $paymentId
     * @param string $msg
     * @param string $result
     * @return void
     * */
    public function errorPay($paymentId, $msg = '', $result = '')
    {
        $postdata = input::get();
        if ($postdata['payment_id']) {
            $paymentId = $postdata['payment_id'];
        }
        if (!$paymentId) {
            kernel::abort('404');
        }
        $params['payment_id'] = $paymentId;

        $notice = '订单支付失败，请重试';
        $msg = $msg ? $msg : $notice;
        $pagedata = array();

        //status表示订单是否存在
        $pagedata['status'] = true;
        $pagedata['msg'] = $msg;

        //判断订单状态
        if (!$result) {
            $result = app::get('topc')->rpcCall('payment.checkpayment.statu', $params);
            if (!$result) {
                $pagedata['msg'] = '订单不存在';
                $pagedata['status'] = false;
                return $this->page('topc/payment/error.html', $pagedata);
            }
        }

        if ($result != 'succ') {
            //获取订单详情
            $params['fields'] = 'cur_money';
            $paymentBill = app::get('topc')->rpcCall('payment.bill.get', $params);
            $trade = $paymentBill['trade'];
            $tids = array_keys($trade);
            $iparams['tid'] = $tids;
            $iparams['user_id'] = userAuth::id();
            $iparams['fields'] = "tid,orders.title";
            $itrade = app::get('topc')->rpcCall('trade.get', $iparams);
            $orders = $itrade['orders'];
            $pagedata['cur_money'] = $paymentBill['cur_money'];
            $pagedata['orders'] = $orders;
            $pagedata['payment_id'] = $paymentId;

            return $this->page('topc/payment/error.html', $pagedata);
        } else {
            return redirect::action('topc_ctl_paycenter@finish', array('payment_id' => $postdata['payment_id']));
        }

    }

    //测试接口 不用时屏蔽路由
    public function test()
    {
//        $filterBill['tid']      = '21712290947464726';
//        $objMdlTradePaybill     = app::get('ectools')->model('trade_paybill');
//        $billList               = $objMdlTradePaybill->getRow('payment_id',$filterBill);
//        $params=[];
//        $params['payment_id'] = $billList['payment_id'];//var_dump($params);exit;
//        app::get('sysshop')->rpcCall('xml.message.create',$params);
//        echo '报文生成';


        //throw new \LogicException('售后退款操作测试');

        $tlpay['orderAmount'] = 154.00 * 100;
//                $tlpay['orderDatetime']     = date('YmdHis', $time);
        $tlpay['orderNo'] = '31712171447404967';

        $kjp = '收银宝 商户服务平台--';

        $Objdata = kernel::single('ectools_TL_wxpay');
        $payerM = $Objdata->cancel($tlpay);//退款

        if (trim($payerM) == '原交易不存在') {
            throw new \LogicException('原交易不存在');
        } else if (trim($payerM) != '退款成功') {

            $payerQ = $Objdata->query($tlpay);//订单查询
            var_dump($payerQ);
            exit;
        }
        echo '退款成功';exit;
    }
}






