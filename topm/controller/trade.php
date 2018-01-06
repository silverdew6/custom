<?php
class topm_ctl_trade extends topm_controller{

    var $noCache = true;

    public function __construct(&$app)
    {
        parent::__construct();
        theme::setNoindex();
        theme::setNoarchive();
        theme::setNofolow();
        theme::prependHeaders('<meta name="robots" content="noindex,noarchive,nofollow" />\n');
        $this->title=app::get('topm')->_('订单中心');
        // 检测是否登录
        if( !userAuth::check())
        {
            redirect::action('topm_ctl_passport@signin')->send();exit;
        }
    }

    public function tradeDetail()
    {
        echo "detail";
    }

    public function create()
    {
        $postData = input::get();
        $postData['mode'] = $postData['mode'] ? $postData['mode'] :'cart';
        $cartFilter['mode'] = $postData['mode'];
        $cartFilter['needInvalid'] = false;
        $cartFilter['platform'] = 'wap';
        $md5CartFilter = array('user_id'=>userAuth::id(), 'platform'=>'wap', 'mode'=>$cartFilter['mode'], 'checked'=>1);
        $cartInfo = app::get('topm')->rpcCall('trade.cart.getBasicCartInfo', $md5CartFilter, 'buyer');
        $is_tax_flag = true ; //默认是不需要
        //判断 是不是完税，是则不验证身份证；
        $tax='';                    //17/08/09 单笔订单中的商品类型相同
        if($cartInfo){
        	foreach($cartInfo as $kx=> $onecitem){
        	    $tax=$onecitem["tax"];
        		if($onecitem && isset($onecitem["tax"]) && intval($onecitem["tax"])>1){ //xx 2017/4/7 修改tax=2/3时身份证必填 08/17  xx
        			$is_tax_flag = false ;break;
        		}
        	}
        }
        // 校验购物车是否为空
        if (!$cartInfo)
        {
            $msg = app::get('topm')->_("购物车信息为空或者未选择商品");
            return $this->splash('false', '', $msg, true);
        }
        // 校验购物车是否发生变化
        $md5CartInfo = md5(serialize(utils::array_ksort_recursive($cartInfo, SORT_STRING)));
        if( $postData['md5_cart_info'] != $md5CartInfo )
        {
            $msg = app::get('topm')->_("购物车数据发生变化，请刷新后确认提交");
            return $this->splash('false', '', $msg, true);
        }
        unset($postData['md5_cart_info']);
        if(!$postData['addr_id']){
            $msg .= app::get('topm')->_("请先确认收货地址");
            return $this->splash('success', '', $msg, true);
        }
        else
        {
            $addr = app::get('topm')->rpcCall('user.address.info',array('addr_id'=>$postData['addr_id'],'user_id'=>userAuth::id()));
            list($regions,$region_id) = explode(':',$addr['area']);
            list($state,$city,$district) = explode('/',$regions);

            if (!$state )
            {
                $msg .= app::get('topm')->_("收货地区不能为空！")."<br />";
            }

            if (!$addr['addr'])
            {
                $msg .= app::get('topm')->_("收货地址不能为空！")."<br />";
            }

            if (!$addr['name'])
            {
                $msg .= app::get('topm')->_("收货人姓名不能为空！")."<br />";
            }

            if (!$addr['mobile'] && !$addr['phone'])
            {
                $msg .= app::get('topm')->_("手机或电话必填其一！")."<br />";
            }

            if (strpos($msg, '<br />') !== false)
            {
                $msg = substr($msg, 0, strlen($msg) - 6);
            }
            if($msg)
            {
                return $this->splash('error', '', $msg, true);
            }
         }

        if(!$is_tax_flag && $postData['cd_pd']!='0' && trim($postData['card_id'])==''){
                $msg = app::get('topm')->_('身份证不能为空!');
                return $this->splash('error', '', $msg, true);
        }

        //2017/08/22 新加直邮才需要使用身份证号 start
        if(!$is_tax_flag && trim($postData['card_id'])!=''){ //是完税，需要身份证号
            if (!$this->check_identity($postData['card_id'])) {
                $msg = app::get('topm')->_("身份证格式错误");
                return $this->splash('success', '', $msg, true);
            }
            if(intval($onecitem["tax"]) == 4){ //实名认证
                //认证
                $code = $this->real_name_auth($postData['name'], $postData['card_id']);
                if ($code != 1) {
                    if ($code == 2 || $code == 3) {
                        $msg = app::get('topm')->_('姓名与身份证号不一致，请重新输入!');
                        return $this->splash('success', '', $msg, true);
                    } elseif ($code = 11 || $code == 12 || $code == 13 || $code == 14) {
                        $msg = app::get('topm')->_('系统忙，请稍后再试!');
                        return $this->splash('success', '', $msg, true);
                    }
                    //认证通过则入库
                    $codec = app::get('topm')->rpcCall('user.card.create', $postData);
                    if (!$codec) {
                        $msg = app::get('topm')->_('身份证添加失败!');
                        return $this->splash('success', '', $msg, true);
                    }
                }
            }elseif(intval($onecitem["tax"]) == 3 || intval($onecitem["tax"]) == 2 ){
                //修改数据库信息
                app::get('sysuser')->model('user_addrs')->update(['card_id'=>$postData['card_id']],['addr_id'=>$postData['addr_id']]);
            }
        }
        //2017/08/22 新加直邮才需要使用身份证号 end
        if(!$postData['payment_type'])
        {
            $msg = app::get('topm')->_("请先确认支付类型");
            return $this->splash('success', '', $msg, true);
        }
        else
        {
            $postData['payment_type'] = $postData['payment_type'] ? $postData['payment_type'] : 'online';
        }
        //发票信息
        if($postData['invoice'])
        {
            foreach($postData['invoice'] as $key=>$val)
            {
                $postData[$key] = $val;
            }
            unset($postData['invoice']);
        }
        if($postData['invoice_content'])
        {
            $validator = validator::make(
                [$postData['invoice_content']],
                ['max:100'],
                ['发票内容最大为100个字符!']
            );
            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();
                foreach( $messages as $error )
                {
                    return $this->splash('error', '', $error[0], true);
                }
            }
        }

        //店铺配送方式处理
        $shipping = "";
        if( $postData['shipping'])
        {
            foreach($postData['shipping'] as $k=>$v)
            {
                //验证店铺类型
                $shopdata = app::get('topm')->rpcCall('shop.get.detail',array('shop_id'=>$k,'fields'=>'shop_type'))['shop'];
                $ifOpenZiti = app::get('syslogistics')->getConf('syslogistics.ziti.open');
                $ifOpenOffline = app::get('ectools')->getConf('ectools.payment.offline.open');

                //验证非自营时，支付方式“货到付款”问题
                if(($postData['payment_type'] == "offline" ) )
                {
                    if(($shopdata['shop_type'] != "self") || ($shopdata['shop_type'] == "self" && $ifOpenOffline == "false"))
                    {
                        $msg = app::get('topm')->_("您的支付方式选择有误");
                        return $this->splash('error', '', $msg, true);
                    }
                }
                $shipping .= $k.":".$v['template_id'].";";
                if($v['template_id'] == 0)
                {
                    //验证是否有自提资格
                    if( $shopdata['shop_type'] != "self" || $ifOpenZiti == "false")
                    {
                        $msg = app::get('topm')->_("您的配送方式选择有误");
                        return $this->splash('error', '', $msg, true);
                    }

                    if(!$postData['ziti'][$k]['ziti_addr'])
                    {
                        $msg = app::get('topm')->_("您已选择自提，请选择自提地址");
                        return $this->splash('error', '', $msg, true);
                    }
                    $zitiAddr = app::get('topm')->rpcCall('logistics.ziti.get',array('id'=>$postData['ziti'][$k]['ziti_addr']));
                    $ziti .= $k.":".$zitiAddr['area'].$zitiAddr['addr'].";";
                }

                if( $v['template_id'] == '-1' )
                {
                    $msg = app::get('topm')->_("请选择店铺配送方式");
                    return $this->splash('error', '', $msg, true);
                }
            }
            unset($postData['shipping']);
            unset($postData['ziti']);
        }
        $postData['shipping'] = $shipping;
        if($ziti)
        {
            $postData['ziti'] = $ziti;
        }
        $postData['source_from'] = 'wap';

        $obj_filter = kernel::single('topm_site_filter');
        $postData = $obj_filter->check_input($postData);

        $postData['user_id'] = userAuth::id();
        $postData['user_name'] = userAuth::getLoginName();
        $postData['tax']=$tax;//17/08/09

        try
        {

           $createFlag = app::get('topm')->rpcCall('trade.create',$postData,'buyer');
        }
        catch(Exception $e)
        {
            return $this->splash('error',null,$e->getMessage(),true);
        }

        try{
            if($postData['payment_type'] == "online")
            {
                $params['tid'] = $createFlag;
                $params['user_id'] = userAuth::id();
                $params['user_name'] = userAuth::getLoginName();
                $paymentId = kernel::single('topm_payment')->getPaymentId($params);
                $redirect_url = url::action('topm_ctl_paycenter@index',array('payment_id'=>$paymentId,'merge'=>true));
            }
            else
            {
                $redirect_url = url::action('topm_ctl_paycenter@index',array('tid' => implode(',',$createFlag)));
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topm_ctl_member_trade@tradeList');
            return $this->splash('error',$url,$msg,true);
        }
        return $this->splash('success',$redirect_url,'订单创建成功',true);
    }

    /**2016/3/20 lcd
     * 验证18位身份证（计算方式在百度百科有）
     * @param  string $id 身份证
     * return boolean
     */
    protected function check_identity($id='')
    {
        $set = array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
        $ver = array('1','0','x','9','8','7','6','5','4','3','2');
        $arr = str_split($id);
        $sum = 0;
        for ($i = 0; $i < 17; $i++)
        {
            if (!is_numeric($arr[$i]))
            {
                return false;
            }
            $sum += $arr[$i] * $set[$i];
        }
        $mod = $sum % 11;
        if (strcasecmp($ver[$mod],$arr[17]) != 0)
        {
            return false;
        }
        return true;
    }
    //身份实名认证
    protected function real_name_auth($name,$card){
        $post_data = array () ;
        $post_data [ 'appkey' ] = "a526aa804c157c4782f6d38f7dd1482b" ;
        $post_data [ 'name' ] = $name ;
        $post_data [ 'cardno' ] = $card ;
        $url = 'http://api.id98.cn/api/idcard' ;
        $o = "" ;
        foreach ( $post_data as $k => $v )
        {
            $o .= " $k = " . urlencode ( $v ) . " & " ;
        }
        $post_data = substr ( $o , 0 ,- 1 ) ;
        $ch = curl_init () ;
        curl_setopt ( $ch , CURLOPT_POST , 1 ) ;
        curl_setopt ( $ch , CURLOPT_HEADER , 0 ) ;
        curl_setopt ( $ch , CURLOPT_URL , $url ) ;
        curl_setopt ( $ch , CURLOPT_POSTFIELDS , $post_data ) ;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $obj=json_decode($response);
        $code= $obj->code;
        return $code;
    }
}
