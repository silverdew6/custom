<?php
class topc_ctl_trade extends topc_controller{

    var $noCache = true;

    public function __construct(&$app)
    {
        parent::__construct();
        theme::setNoindex();
        theme::setNoarchive();
        theme::setNofolow();
        theme::prependHeaders('<meta name="robots" content="noindex,noarchive,nofollow" />\n');
        $this->title=app::get('topc')->_('订单中心');
        // 检测是否登录
        if( !userAuth::check() )
        {
            redirect::action('topc_ctl_passport@signin')->send();exit;
        }
    }
    public function create()
    {
        $postData = input::get();//var_dump($postData);
        $postData['mode'] = $postData['mode'] ? $postData['mode'] :'cart';

        $cartFilter['mode'] = $postData['mode'];
        $cartFilter['needInvalid'] = false;
        $cartFilter['platform'] = 'pc';
        $md5CartFilter = array('user_id'=>userAuth::id(), 'platform'=>'pc', 'mode'=>$cartFilter['mode'], 'checked'=>1);
        $cartInfo = app::get('topc')->rpcCall('trade.cart.getBasicCartInfo', $md5CartFilter, 'buyer');
        // 校验购物车是否为空
        if (!$cartInfo)
        {
            $msg = app::get('topc')->_("购物车信息为空或者未选择商品");
            return $this->splash('false', '', $msg, true);
        }
        //echo '<pre>';print_r($cartInfo);exit();
        //判断有购物车是否有直邮的产品,直邮需要详细的身份证信息
        $tax_idds = array();
        $istax_idcard = false;

        //17/07/12新增
        $tax='';//单笔订单中的商品类型相同

        if(!$cartInfo)return false ;
        foreach($cartInfo as $kv=>$vcm){
        	$tttax = isset($vcm["tax"]) ? intval($vcm["tax"]) : false ;
        	if($tttax && !in_array($tttax, $tax_idds)) $tax_idds[] = $tttax;
            $tax=$tttax;

        	if($vcm && $tttax >1){    //17/08/17 xx
        		$istax_idcard = true;//只要有tax >1 ，即2，3就需要进行身份证效验
        	}        	
        }
        $isrequire_Temp = true ; //默认是需要验证TemplateId
        if($tax_idds && !empty($tax_idds)){
        	sort($tax_idds);
        	$tax_idds = implode("-",$tax_idds);//变成字符串，1－3
        	if($tax_idds=="3"||$tax_idds=="2" ||$tax_idds=="2-3") $isrequire_Temp = false;  // 只有tax = 3  不需要验证template ,   	 
        }
        // 校验购物车是否发生变化
        $md5CartInfo = md5(serialize(utils::array_ksort_recursive($cartInfo, SORT_STRING)));
        if( $postData['md5_cart_info'] != $md5CartInfo )
        {
            $msg = app::get('topc')->_("购物车数据发生变化，请刷新后确认提交");
            return $this->splash('false', '', $msg, true);
        }
        unset($postData['md5_cart_info']);

        if(empty($postData['addr_id']) && empty($postData['addr']))   //改 09/04,针对表单的两种提交方式做判断
        {
            $msg = app::get('topc')->_("请先确认收货地址");
            return $this->splash('success', '', $msg, true);
        }
        else
        {
            if(empty($postData['addr_id'])){                      //改 09/04,针对表单的两种提交方式做判断
                $postData['addr_id'] = $postData['addr'];
            }

            //身份证实名认证,card_id,name,当类型属性为保税时 17/08/21 start
            if($istax_idcard && trim($postData['card_id'])!='') {                          //$tax>1
                if (!$this->check_identity($postData['card_id'])) {
                    $msg = app::get('topc')->_("身份证格式错误");
                    return $this->splash('success', '', $msg, true);
                }
                if($tttax==4){ //实名认证
                    //认证
                    $code = $this->real_name_auth($postData['name'], $postData['card_id']);
                    if ($code != 1) {
                        if ($code == 2 || $code == 3) {
                            $msg = app::get('topc')->_('姓名与身份证号不一致，请重新输入!');
                            return $this->splash('success', '', $msg, true);
                        } elseif ($code = 11 || $code == 12 || $code == 13 || $code == 14) {
                            $msg = app::get('topc')->_('系统忙，请稍后再试!');
                            return $this->splash('success', '', $msg, true);
                        }
                    }
                    //认证通过则入库
                    $codec = app::get('topc')->rpcCall('user.card.create', $postData);
                    if (!$codec) {
                        $msg = app::get('topc')->_('身份证添加失败!');
                        return $this->splash('success', '', $msg, true);
                    }
                }elseif($tttax == 3 || $tttax == 2){
                    //修改数据库信息
                    app::get('sysuser')->model('user_addrs')->update(['card_id'=>$postData['card_id']],['addr_id'=>$postData['addr_id']]);
                }
            }
            //身份证实名认证,card_id,name,当类型属性为保税时 17/08/21 end

            $addr = app::get('topc')->rpcCall('user.address.info',array('addr_id'=>$postData['addr_id'],'user_id'=>userAuth::id()));
            list($regions,$region_id) = explode(':',$addr['area']);
            list($state,$city,$district) = explode('/',$regions);
            //添加参数中身份证号码看业务类型tax > 1 来判断 是否必填 
            $param1 =array(
            	 'state' => $state,
                 'addr' => $addr['addr'] ,
                 'name' => $addr['name'],
                 'mobile' => $addr['mobile']
            );
            $param2 =array(
            	'state' => 'required',
                'addr' => 'required',
                'name' => 'required',
                'mobile' => 'required|mobile'
            );
            $param_text =array(
            	 'state' => '收货地区不能为空!',
                 'addr' => '收货地址不能为空!',
                 'name' => '收货人姓名不能为空！',
                 'mobile' => '手机号码必填!|手机号码格式不正确!'
            );

            //验证性
            $validator = validator::make($param1,$param2,$param_text);
            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();
                foreach( $messages as $error )
                {
                    return $this->splash('error',null,$error[0]);
                }
            }
        }
        //支付方式 先在线支付
        if(!$postData['payment_type'])
        {
            $msg = app::get('topc')->_("请先确认支付类型");
            return $this->splash('success', '', $msg, true);
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
                [$postData['invoice_content']],['max:100'], ['发票内容最大为100个字符!']
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
        //echo '<pre>';print_r($postData);exit();
        //店铺配送方式处理
        $shipping = "";
        if( $postData['distribution'])
        {
            foreach($postData['distribution'] as $k=>$v)
            {
                //验证店铺类型
                $shopdata = app::get('topc')->rpcCall('shop.get.detail',array('shop_id'=>$k,'fields'=>'shop_type'))['shop'];
                $ifOpenZiti = app::get('syslogistics')->getConf('syslogistics.ziti.open');
                $ifOpenOffline = app::get('ectools')->getConf('ectools.payment.offline.open');
				//如果是完税之外的选择默认包邮模板ID
				/*if($istax_idcard){
					$postData['shipping'][$k]['template_id'] = 10000;	//包邮模板  by XCH 
           		}*/
                //验证非自营时，支付方式“货到付款”问题
                if(($postData['payment_type'] == "offline")){
                    if(($shopdata['shop_type'] != "self") || ($shopdata['shop_type'] == "self" && $ifOpenOffline == "false"))
                    {
                        $msg = app::get('topc')->_("您的支付方式选择有误");
                        return $this->splash('error', '', $msg, true);
                    }
                }
				//选择快递配送 // by xch 25号之前，直邮使用默认的包邮-> 修改为部分满2件，或满150包邮 
                if($v['type'] == 1){
                    if($postData['shipping'][$k]['template_id'] <= 0 && $isrequire_Temp)
                    {
                        $msg = app::get('topc')->_("请选择店铺配送方式");
                        return $this->splash('error', '', $msg, true);
                    }
                    $shipping .= $k.":".$postData['shipping'][$k]['template_id'].";"; //把店铺选择的快递方式用分隔副来操作；
                }elseif($v['type'] == 0){
                    //验证是否有自提资格
                    if($shopdata['shop_type'] != "self" || $ifOpenZiti == "false")
                    {
                        $msg = app::get('topc')->_("您的配送方式选择有误");
                        return $this->splash('error', '', $msg, true);
                    }
                    if(!$postData['ziti'][$k]['ziti_id'])
                    {
                        $msg = app::get('topc')->_("您已选择自提，请选择自提地址");
                        return $this->splash('error', '', $msg, true);
                    }
                    $shipping .= $k.":0;";
                    $zitiAddr = app::get('topc')->rpcCall('logistics.ziti.get',array('id'=>$postData['ziti'][$k]['ziti_id']));

                    $areaIds = explode('/',$region_id);
                    $checkAreaIds =  count($areaIds) == 2 ? $zitiAddr['area_city_id'] : $zitiAddr['area_state_id'];
                    if( $checkAreaIds != $areaIds[0] )
                    {
                        $msg = app::get('topc')->_("请重新选择自提地址");
                        return $this->splash('error', '', $msg, true);
                    }

                    $ziti .= $k.":".$zitiAddr['area'].$zitiAddr['addr'].";";
                }
            }
            unset($postData['shipping']);
            unset($postData['ziti']);
        }
        else
        {
            $msg = app::get('topc')->_("请选择店铺配送方式");
            return $this->splash('error', '', $msg, true);
        }
        $postData['shipping'] = $shipping;//每个店铺的配送货方式都在这里（1:25;2:5;）
        if($ziti) {
            $postData['ziti'] = $ziti;
        }
        $postData['source_from'] = 'pc';
        $obj_filter = kernel::single('topc_site_filter');
        $postData = $obj_filter->check_input($postData); //系统过滤特殊字符
		//登录用户判断
        $postData['user_id'] = userAuth::id();
        $postData['user_name'] = userAuth::getLoginName();
        $postData['tax']=$tax;

        try
        {
           $createFlag = app::get('topc')->rpcCall('trade.create',$postData,'buyer'); //订单拆分入库
           if( $createFlag )
           {
               $countData = app::get('topc')->rpcCall('trade.cart.getCount', ['user_id' => userAuth::id()], 'buyer');
               userAuth::syncCookieWithCartNumber($countData['number']);
               userAuth::syncCookieWithCartVariety($countData['variety']);
           }
        }
        catch(Exception $e)
        {
            return $this->splash('error',null,$e->getMessage(),true);
        }
        try
        {
            if($postData['payment_type'] == "online")
            {
                $params['tid'] = $createFlag;
                $params['user_id'] = userAuth::id();
                $params['user_name'] = userAuth::getLoginName();
                $paymentId = kernel::single('topc_payment')->getPaymentId($params);
                $redirect_url = url::action('topc_ctl_paycenter@index',array('payment_id'=>$paymentId,'merge'=>true));
            }
            else
            {
                $redirect_url = url::action('topc_ctl_paycenter@index',array('tid' => implode(',',$createFlag)));
            }
        }
        catch(Exception $e)
        {

            $msg = $e->getMessage();
            $url = url::action('topc_ctl_member_trade@tradeList');
            return $this->splash('success',$url,$msg,true);
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


