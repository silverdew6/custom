<?php
class topm_ctl_paycenter extends topm_controller{

    public function __construct($app)
    {
        parent::__construct();
        //$this->setLayoutFlag('paycenter');
        $this->setLayoutFlag('cart');
        // 检测是否登录
        if( !userAuth::check() )
        {
            redirect::action('topm_ctl_passport@signin')->send();exit;
        }
    }
    public function index($filter=null)
    {
        if(!$filter)
        {
            $filter = input::get();
        }

        if(isset($filter['tid']) && $filter['tid'])
        {
            $pagedata['payment_type'] = "offline";
            $ordersMoney = app::get('topm')->rpcCall('trade.money.get',array('tid'=>$filter['tid']),'buyer');

            if($ordersMoney)
            {
                foreach($ordersMoney as $key=>$val)
                {
                    $newOrders[$val['tid']] = $val['payment'];
                    $newMoney += $val['payment'];
                }
                $paymentBill['money'] = $newMoney;
                $paymentBill['cur_money'] = $newMoney;
            }
            $pagedata['trades'] = $paymentBill;
            return $this->page('topm/payment/offline.html', $pagedata);
        }

        if($filter['newtrade'])
        {
            $newtrade = $filter['newtrade'];
            unset($filter['newtrade']);
        }

        //获取可用的支付方式列表
        $payType['platform'] = 'iswap';
        $payments = app::get('topm')->rpcCall('payment.get.list',$payType,'buyer');
		if($payments){
	         	foreach($payments as $k=>$cpp){
	         		if(isset($cpp["app_id"]) && trim($cpp["app_id"])=="wxpayjsapi" && trim($cpp["platform"])=="iswap"){
	         			$tempArr = $cpp;
	         			$tempArr["app_id"] = "wxqrpay"; //微信扫码付款 
	         			$tempArr["app_display_name"]="微信扫码支付";
	         			$payments[$k]["app_display_name"]="微信客户端支付";
	         			$tempArr["app_class"]="ectools_payment_plugin_wxqrpay";
	         			$payments = array_merge($payments,array($tempArr));unset($tempArr);
	         		}
	         	}
	        }
 		//这里做个埋点，获取openid的
 		 $isOpenid_pays =  array('wxpayjsapi');
        //因为微信支付太恶心了，要求有用户的openid才能支付，所以做这个埋点临时使用，以后再慢慢看这个埋点怎么弄
        foreach($payments as $paymentKey => $payment)
        {
            if(isset($payment['app_id']) && in_array($payment['app_id'], $isOpenid_pays))
            {
                if(!kernel::single('topm_wechat_wechat')->from_weixin())
                {
                    unset($payments[$paymentKey]);
                    continue;
                }
                //获取基础信息，不需要授权的情况下，获取用户的open_id;
                //不弹出微信授权页面：scope=snsapi_base，弹出微信授权页面：scope=snsapi_userinfo  
                $payInfo = app::get('topm')->rpcCall('payment.get.conf', array('app_id' => 'wxpayjsapi'));//var_dump($payInfo);exit;
               /* $wxAppId = isset($payInfo['setting']['appId']) ? $payInfo['setting']['appId'] : '';
                $wxAppsecret = isset($payInfo['setting']['Appsecret']) ? $payInfo['setting']['Appsecret'] : "";
                if(!input::get('code')) {
                    $url = url::action('topm_ctl_paycenter@index',$filter);
                    kernel::single('topm_wechat_wechat')->get_code($wxAppId, $url);
                }else{
                    $code = input::get('code');
                    $openid = kernel::single('topm_wechat_wechat')->get_openid_by_code($wxAppId, $wxAppsecret, $code);
                    if($openid == null) return $this->splash('failed', 'back',  app::get('topm')->_('获取openid失败'));
                    $pagedata['openid'] = $openid;
                }*/
            }
        }


        //17/10/14 为通联微信支付获取openid start
/*            $wxAppId                        = 'wxd5e1e4e198f0e216';
            $wxAppsecret                    = "bbec12ea2e6d6d0c810d373039c67caf";
            if(!input::get('code')) {
                $url                        = url::action('topm_ctl_paycenter@index',$filter);
                kernel::single('topm_wechat_wechat')->get_code($wxAppId, $url);
            }else{
                $code                       = input::get('code');
                $openid                     = kernel::single('topm_wechat_wechat')->get_openid_by_code($wxAppId, $wxAppsecret, $code);
                if($openid == null) $openid = 'error';//$this->splash('failed', 'back',  app::get('topm')->_('获取openid失败'));
                $pagedata['openid']         = $openid;
            }*/
        //17/10/14 为通联微信支付获取openid end

        $filter['fields'] = "*";
        $paymentBill = app::get('topm')->rpcCall('payment.bill.get',$filter,'buyer');

        //检测订单中的金额是否和支付金额一致 及更新支付金额
        $trade = $paymentBill['trade'];
        $tids['tid'] = implode(',',array_keys($trade));
        $ordersMoney = app::get('topm')->rpcCall('trade.money.get',$tids,'buyer');

        if($ordersMoney)
        {
            foreach($ordersMoney as $key=>$val)
            {
                $newOrders[$val['tid']] = $val['payment'];
                $newMoney += $val['payment'];
            }

            $result = array(
                'trade_own_money' => json_encode($newOrders),
                'money' => $newMoney,
                'cur_money' => $newMoney,
                'payment_id' => $filter['payment_id'],
            );

            if($newMoney != $paymentBill['cur_money'])
            {
                try{
                    app::get('topm')->rpcCall('payment.money.update',$result);
                }
                catch(Exception $e)
                {
                    $msg = $e->getMessage();
                    $url = url::action('topm_ctl_member_trade@tradeList');
                    return $this->splash('error',$url,$msg,true);
                }
                $trades['money'] = $newMoney;
                $trades['cur_money'] = $newMoney;
            }
        }

        $pagedata['tids'] = $tids['tid'];
        $pagedata['trades'] = $paymentBill;
        $pagedata['payments'] = $payments;
        $pagedata['newtrade'] = $newtrade;
        $pagedata['hasDepositPassword'] = app::get('topc')->rpcCall('user.deposit.password.has', ['user_id'=>userAuth::id()]);//var_dump($payments[0]);exit;
        return $this->page('topm/payment/index.html', $pagedata);
    }
    public function createPay()
    {
        $filter = input::get();
        $filter['user_id'] = userAuth::id();
        $filter['user_name'] = userAuth::getLoginName();

        try
        {
            $paymentId = kernel::single('topm_payment')->getPaymentId($filter);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topm_ctl_member_trade@index');
            echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';
            exit;
        }
        $url = url::action('topm_ctl_paycenter@index',array('payment_id'=>$paymentId,'merge'=>$ifmerge));
        return $this->splash('success',$url,$msg,true);
    }
    public function dopayment()
    {
        $postdata = input::get();
        $payment=[];
        $payment['pay_app_id']=$postdata['pay_app_id'];
        $payment['payment_id']=$postdata['payment_id'];
        $payment['money']=$postdata['money'];
        $payment['tids']=$postdata['tids'];

        //tl快捷支付方式
        if($payment['pay_app_id'] == 'H5' || $payment['pay_app_id'] == 'W02'){
            $payment['type']                = $payment['pay_app_id'];
            $payment['pay_app_id']          = 'TL-pay';
            $payment['openid']              =$postdata['openid'];
        }

        $payment['deposit_password']    = $postdata['deposit_password'];
        $payment['user_id']             = userAuth::id();


        if(!$payment['pay_app_id'])
        {
            echo '<meta charset="utf-8"><script>alert("请选择支付方式"); window.close();</script>';
            exit();
        }
        $payment['platform'] = "wap";
        try
        {
            if($payment['pay_app_id'] == 'deposit' && $postdata['deposit_password'] == '')
            {
                throw new LogicException('请输入预存款支付密码！');
            }
           //支付方式处理
             $res = app::get('topm')->rpcCall('payment.trade.pay',$payment);
            if($payment['pay_app_id'] == 'TL-pay')
            {
                    return $res['payinfo'];
            }
            if($payment['pay_app_id'] == 'wxpayjsapi' && !empty($res) && $res !== true){
                return $res;
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topm_ctl_paycenter@index',array('payment_id'=>$payment['payment_id']));
            echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';
            exit();
        }
        //支付处理返回判断
        $url = url::action('topm_ctl_paycenter@finish',array('payment_id'=>$payment['payment_id']));
        return $this->splash('success',$url,null,true);
    }
    //手机端支付回调
    public function tlpick(){
        $dataPost = input::get();      //PC回调返回数组

        try
        {
            if(empty($dataPost))
            {
                throw new LogicException('接收数据为空');
            }
            $objMdlTradePaybill     = app::get('ectools')->model('trade_paybill');
            $filterBill['tid']      = $dataPost['orderNo'];
            $billList               = $objMdlTradePaybill->getRow('payment_id',$filterBill);
            if(empty($billList)){
                throw new Exception('订单明细查询失败');
            }

            $filePay['payment_id'] = $billList['payment_id'];

            if($dataPost['payType'] == '33')
            {
                //快捷支付
                $dataPost['type']       = 'H5';
                $payer                  = app::get('ectools')->rpcCall('tlpay.kjpay.receive',$dataPost);
                if(is_array($payer)){
                    $paytable = app::get('ectools')->rpcCall('tlpay.sql.take',$payer);
                }else{
                    throw new LogicException($payer);
                }
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topm_ctl_paycenter@index',$filePay);
            echo  "<meta charset='utf-8'><input type='hidden' id='msg' value='".$msg."'><input type='hidden' id='url' value='".$url."'><script>alert(document.getElementById('msg').value);location.href=document.getElementById('url').value;</script>";exit();

            //echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';exit();
        }

        $url = url::action('topm_ctl_paycenter@finish',$filePay);
        return redirect::to($url);
    }
    public function finish()
    {
        $postdata = input::get();
        try
        {
            $params['payment_id'] = $postdata['payment_id'];
            $params['fields'] = 'payment_id,status,pay_app_id,pay_name,money,cur_money,payed_time,created_time';
            $result = app::get('topm')->rpcCall('payment.bill.get',$params);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();		 
        }

        //生成报文
        //$params['payment_id']='15113014540544614851';
//        $params=[];
//        $params['payment_id'] = $postdata['payment_id'];
//        app::get('sysshop')->rpcCall('xml.message.create',$params);

		$trades = $result['trade_own_money'];
        $result['num'] = count($trades);
        $pagedata['msg'] = $msg;
        $pagedata['payment'] = $result;	 
        return $this->page('topm/payment/finish.html', $pagedata);
    }
}
