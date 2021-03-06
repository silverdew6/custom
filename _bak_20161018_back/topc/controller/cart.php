<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topc_ctl_cart extends topc_controller {

    public function __construct(&$app)
    {
        parent::__construct();
        // 检测是否登录
        if( !userAuth::check() )
        {
            redirect::action('topc_ctl_passport@signin')->send();exit;
        }
    }
    /**
     * 获取购物车数据，再加工成，按业务类型分离
     * 分成完税和直邮两大类
     * 完税tax_1：（ 
     * 		店铺1： info (店铺信息) list （按地区拆数组）
     * 		店铺2： info (店铺信息) list （按地区拆数组）
     * ），
     * 直邮tax_3：（ 
     * 		店铺1： info (店铺信息) list （按地区拆数组）
     * 		店铺2： info (店铺信息) list （按地区拆数组）
     * ），
     */
     function __getNewCartInfo($oldcartInfo){
     	if(!$oldcartInfo) return false;
     	$lastData = array();
     	foreach($oldcartInfo as $shopid=> $shcart){
     		//店铺有订单
     		if(isset($shcart["object2"]) && !empty($shcart["object2"])){     			
     			foreach($shcart["object2"] as $tax_regid => $cartdetail ){
     				if(isset($tax_regid) && trim($tax_regid) !=""){
     					$tax_temp = substr($tax_regid,0,1); //取业务类型 
     					unset($shcart["object"],$shcart["object2"]);
     					if(intval($tax_temp) == 1){ 
     						//完税
     						empty($lastData["tax_1"][$shopid]["info"])  and $lastData["tax_1"][$shopid]["info"] = $shcart; //店铺信息挂上去；
     						$lastData["tax_1"][$shopid]["list"][$tax_regid] = $cartdetail;
     					}else if(intval($tax_temp) ==3){ 
     						//直邮
     						$lastData["tax_3"][$shopid]["list"][$tax_regid] = $cartdetail;
     						empty($lastData["tax_3"][$shopid]["info"])  and $lastData["tax_3"][$shopid]["info"] = $shcart; //店铺信息挂上去；
     					}
     				}
     			}
     		}
     	}
     	return $lastData ;
     }
     
    

    public function index()
    {
        $this->setLayoutFlag('cart');
        header("cache-control: no-store, no-cache, must-revalidate");
        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');
        $cartData = app::get('topc')->rpcCall('trade.cart.getCartInfo', array('platform'=>'pc','user_id'=>userAuth::id()), 'buyer');
       
        //获取的数据重新格式化
        //$pagedata["resultCartList"] = $this->__getNewCartInfo($cartData['resultCartData']);
        $pagedata['aCart'] = $cartData['resultCartData'];
		//print_r($cartData['resultCartList']);
        $pagedata['totalCart'] = $cartData['totalCart'];//购物车统计
        // 店铺可领取优惠券
        foreach ($pagedata['aCart'] as &$v) {
            $params = array(
                'page_no' => 0,
                'page_size' => 1,
                'fields' => '*',
                'shop_id' => $v['shop_id'],
                'platform' => 'pc',
                'is_cansend' => 1,
            );
            $couponListData = app::get('topc')->rpcCall('promotion.coupon.list', $params, 'buyer');
            if($couponListData['count']>0)
            {
                $v['hasCoupon'] = 1;
            }
        }

        return $this->page('topc/cart/index.html', $pagedata);
    }

    public function ajaxBasicCart()
    {
        $cartData = app::get('topc')->rpcCall('trade.cart.getCartInfo', array('platform'=>'pc','user_id'=>userAuth::id()), 'buyer');

        $pagedata['aCart'] = $cartData['resultCartData'];

        $pagedata['totalCart'] = $cartData['totalCart'];

        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');

        foreach(input::get('cart_shop') as $shopId => $cartShopChecked)
        {
            $pagedata['selectShop'][$shopId] = $cartShopChecked=='on' ? true : false;
        }
        $pagedata['selectAll'] = input::get('cart_all')=='on' ? true : false;

        $msg = view::make('topc/cart/cart_main.html', $pagedata)->render();

        return $this->splash('success',null,$msg,true);
    }

    public function updateCart()
    {	
        $mode = input::get('mode');
		$cart_shop = input::get('cart_shop');
		$f_cart_shop = input::get('f_cart_shop');
		$p_cart_shop = input::get('p_cart_shop');
        $obj_type = input::get('obj_type','item');
        $postCartId = input::get('cart_id');
        $postCartNum = input::get('cart_num');
        $postPromotionId = input::get('promotionid');
		$objMdlCart_tax = app::get('systrade')->model('cart_tax');
        $params = array();
	    $userid  =	userAuth::id();
        foreach ($postCartId as $cartId => $v)
        {
            $data['mode'] = $mode;
            $data['obj_type'] = $obj_type;
            $data['cart_id'] = intval($cartId);
            $data['totalQuantity'] = intval($postCartNum[$cartId]);
            $data['selected_promotion'] = intval($postPromotionId[$cartId]);
            $data['user_id'] = $userid;
            if($v=='1')
            {
                $data['is_checked'] = '1';
            }
            if($v=='0')
            {
                $data['is_checked'] = '0';
            }
            $params[] = $data;
        } 
		try
        {
        	//购物车数据更新
            foreach($params as $updateParams)
            {
                $data = app::get('topc')->rpcCall('trade.cart.update',$updateParams);
                if( $data === false )
                {
                    $msg = app::get('topc')->_('更新失败');
                    return $this->splash('error',null,$msg,true);
                }
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
		//检查勾选保税或者直邮2016/3/13 lcd
        $key = array_search('1', $postCartId);  //返回数组中的键名
		$objMdlCart = app::get('systrade')->model('cart');
        if($key){	//如果勾选了复选框
			$filter['cart_id']= $key;
			$carts = $objMdlCart->getRow('user_id,tax_sea_region,tax,shop_id',$filter); 
		    $finish['user_id']= $carts['user_id'];
		    $cartlists =	$objMdlCart->getList('tax,cart_id,tax_sea_region,shop_id',$finish);
	    	if($carts['tax']==1){	//当前商品完税则其它 直邮都 禁止
				foreach ($cartlists  as $cart)
				{
					if($cart['tax']==1){
			           $tax_ifter['cart_id']=$cart['cart_id'];
					   $tax_data['select_tax']=0;
					   $objMdlCart->update($tax_data,$tax_ifter);//激活所有完税
					}else{
					   $tax_ifter['cart_id']=$cart['cart_id'];
					   $tax_data['select_tax']=1;
					   $objMdlCart->update($tax_data,$tax_ifter);//禁止所有直邮
					}
				}
			}else{ 
				//保税和直邮    // 把完税都禁掉
				foreach ($cartlists  as $cart)
				{
					//if(($cart['shop_id']==$carts['shop_id'])&&($cart['tax_sea_region']==$carts['tax_sea_region']))
					if($cart['tax']!=1 ) 
					{
				        $tax_ifter['cart_id']=$cart['cart_id'];
						$tax_datas['select_tax']=0;
						$objMdlCart->update($tax_datas,$tax_ifter); //激活所有直邮
					}else{
						$tax_ifter['cart_id']=$cart['cart_id'];
				    	$tax_datas['select_tax']=1;
				   		$objMdlCart->update($tax_datas,$tax_ifter);//禁止所有完税
					}
				}
			}
        }else{		
        	//什么也不勾选的情况2016/3/16lcd (LLLL 9月20 号看到此)
	         $filter['user_id']=$userid;
	         $cart_ids = $objMdlCart->getList('cart_id', $filter);
	         foreach ($cart_ids as  $vv)
			 {
			  $tax_ifter['cart_id']=$vv['cart_id'];
			  $tax_cart['select_tax']=0;
			  $objMdlCart->update($tax_cart,$tax_ifter);
			 }
		}
		
		//业务模式和区域勾选项。
     	foreach ($f_cart_shop as $shop_taxId => $o)
        {
             $dataes['tax_id'] = intval($shop_taxId);
             $dataes['user_id'] = $userid;
             $cart_tax = $objMdlCart_tax->getRow('cart_tax_id',$dataes);
	         if($cart_tax){
	         	$data_b['select_id']=$o;
		     	$result = $objMdlCart_tax->update($data_b,$dataes);
		     }else{
				$f_cart['tax_id']       =  $dataes['tax_id'];
				$f_cart['user_id']      =  $dataes['user_id'];
			    $f_cart['select_id']    =  $o;
		     	if($f_cart['cart_tax_id']){
				  		unset($f_cart['cart_tax_id']);
			  	}
	        	$objMdlCart_tax->insert($f_cart);
	   		}
		}
		//print_r($p_cart_shop);
	 	//业务模式和区域屏蔽项。
     	foreach ($p_cart_shop as $shop_taxId => $p)
        {
            $dataes_p['tax_id'] = intval($shop_taxId);
            $dataes_p['user_id'] = $userid;
            $d['disabled_id'] = $p;
		    $result = $objMdlCart_tax->update($d,$dataes_p);
		}
	
        $cartData = app::get('topc')->rpcCall('trade.cart.getCartInfo', array('platform'=>'pc', 'user_id'=>userAuth::id()), 'buyer');
        $pagedata['aCart'] = $cartData['resultCartData'];
        // 临时统计购物车页总价数量等信息
        $totalWeight = 0;
        $totalNumber = 0;
        $totalPrice = 0;
        $totalDiscount = 0;
        $totalTax_rate_price  = 0; //2016/3/10 lcd 添加消费税
		$totalTeg_rate_price  = 0; //2016/3/10 lcd 添加增值税率
        foreach($cartData['resultCartData'] as $v) //是按照店铺来汇总的，例如，购物车里面有5个店铺，那就计算5个店铺的和
        {
		
            $totalWeight += $v['cartCount']['total_weight'];
            $totalNumber += $v['cartCount']['itemnum'];
			$totalTax_rate_price   += $v['cartCount']['tax_rate_price']; //2016/3/10 lcd 添加税费
			$totalTeg_rate_price   += $v['cartCount']['reg_rate_price']; //2016/3/10 lcd 添加税费
            $totalPrice += $v['cartCount']['total_fee'];
            $totalDiscount += $v['cartCount']['total_discount'];
			if($v['cartCount']['tax']){
				$tax                  = $v['cartCount']['tax'];
			}
        }
	// 总金额+税费超过50计算运费，2016/3/10 lcd
    if($tax==1){
		$totalTax_rate_price =0;
		$totalTeg_rate_price =0;
		} //完税的情况，税为0,如果其他店铺没有勾选，那么久是null，多个店铺也就是完税的情况。所以还是0

		 $totalAfterDiscount=ecmath::number_minus(array($totalPrice, $totalDiscount))+$totalTax_rate_price+$totalTeg_rate_price;

        $totalCart['tax'] = $tax;
        $totalCart['totalWeight'] = $totalWeight;
        $totalCart['number'] = $totalNumber;
        $totalCart['totalPrice'] = $totalPrice;
		$totalCart['totalTax_rate_price'] = $totalTax_rate_price; //2016/3/10 lcd 添加消费税费
		$totalCart['totalTeg_rate_price'] = $totalTeg_rate_price; //2016/3/10 lcd 添加增值税费
        $totalCart['totalAfterDiscount'] = $totalAfterDiscount;
        $totalCart['totalDiscount'] = $totalDiscount;
		$totalCart['totalFirstDiscount'] = ecmath::number_minus(array($totalPrice, $totalDiscount));  //不计算税费的总价
        $pagedata['totalCart'] = $totalCart;

        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');

        foreach(input::get('cart_shop') as $shopId => $cartShopChecked)
        {
            $pagedata['selectShop'][$shopId] = $cartShopChecked=='on' ? true : false;
        }
        $pagedata['selectAll'] = input::get('cart_all')=='on' ? true : false;

        // 店铺可领取优惠券
        foreach ($pagedata['aCart'] as &$v) {
            $params = array(
                'page_no' => 0,
                'page_size' => 1,
                'fields' => '*',
                'shop_id' => $v['shop_id'],
                'platform' => 'pc',
                'is_cansend' => 1,
            );
            $couponListData = app::get('topc')->rpcCall('promotion.coupon.list', $params, 'buyer');
            if($couponListData['count']>0)
            {
                $v['hasCoupon'] = 1;
            }
        }


        $countData = app::get('topc')->rpcCall('trade.cart.getCount', ['user_id' => userAuth::id()], 'buyer');
        userAuth::syncCookieWithCartNumber($countData['number']);
        userAuth::syncCookieWithCartVariety($countData['variety']);
		$msg = view::make('topc/cart/cart_main.html', $pagedata)->render();   //2016/3/13lcd更换了一下位置，开始在$countData 第177行上面

        return $this->splash('success',null,$msg,true);
    }

    /**
     * @brief 加入购物车
     *
     * @return
     */
    public function add()
    {
        $mode = input::get('mode');
        $obj_type = input::get('obj_type');

        $params['obj_type'] = $obj_type ? $obj_type : 'item';  //默认商品
        $params['mode'] = $mode ? $mode : 'cart';
        $params['user_id'] = userAuth::id();
        if( $params['obj_type']=='package' )
        {
            $package_id = input::get('package_id');
            $params['package_id'] = intval($package_id);
            $skuids = input::get('package_item');
            $tmpskuids = array_column($skuids, 'sku_id');
            $params['package_sku_ids'] = implode(',', $tmpskuids);
            $params['quantity'] = input::get('package-item.quantity', 1);
        }
        if( $params['obj_type']=='item')
        {
            $quantity = input::get('item.quantity');
            $params['quantity'] = $quantity ? $quantity : 1; //购买数量，如果已有购买则累加
            $params['sku_id'] = intval(input::get('item.sku_id'));
        }
        try
        {
        	//当前商品录入购物车
            $data = app::get('topc')->rpcCall('trade.cart.add', $params, 'buyer');
            if( $data === false )
            {
                $msg = app::get('topc')->_('加入购物车失败');
                return $this->splash('error',null,$msg,true);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
        if( $params['mode'] == 'fastbuy' )
        {
            $url = url::action('topc_ctl_cart@checkout',array('mode'=>'fastbuy') );
        }
        //购物车信息统计
        $countData = app::get('topc')->rpcCall('trade.cart.getCount', ['user_id' => userAuth::id()], 'buyer');
        userAuth::syncCookieWithCartNumber($countData['number']);
        userAuth::syncCookieWithCartVariety($countData['variety']);
        return $this->splash('success',$url,$msg,true);
    }

    public function removeCart()
    {
        $postCartIdsData = input::get('cart_id');
        $tmpCartIds = array();
        foreach ($postCartIdsData as $cartId => $v)
        {
            if($v=='1')
            {
                $tmpCartIds['cart_id'][] = $cartId;
            }
        }
        $params['cart_id'] = implode(',',$tmpCartIds['cart_id']);
        if(!$params['cart_id'])
        {
            return $this->splash('error',null,'请选择需要删除的商品！',true);
        }
        $params['user_id'] = userAuth::id();

        try
        {
            $res = app::get('topc')->rpcCall('trade.cart.delete',$params);
            if( $res === false )
            {
                throw new Exception(app::get('topc')->_('删除失败'));
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
        $countData = app::get('topc')->rpcCall('trade.cart.getCount', ['user_id' => userAuth::id()], 'buyer');
        userAuth::syncCookieWithCartNumber($countData['number']);
        userAuth::syncCookieWithCartVariety($countData['variety']);

        $url = url::action('topc_ctl_cart@index');
        return $this->splash('success',$url,'删除成功',true);
    }

    public function saveAddress()
    {
        $userId = userAuth::id();
        $postData = input::get();
        try
        {
            $validator = validator::make(
                [
                 'addr' => $postData['addr'] ,
                 'name' => $postData['name'],
                 'mobile' => $postData['mobile'],
                 'zip' =>$postData['zip'],
				 'card_id' =>$postData['card_id'],
                ],
                [
                'addr' => 'required',
                'name' => 'required',
                'mobile' => 'required|mobile',
                 'zip' =>'numeric',
				 'card_id' =>'required',
                ],
                [
                 'addr' => '会员街道地址必填!',
                 'name' => '收货人姓名未填写!',
                 'mobile' => '手机号码必填!|手机号码格式不正确!',
                 'zip' =>'邮编必须为6位数的整数',
				 'card_id' =>'身份证必填',
                ]
            );
            $validator->newFails();
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }


        $postData['area'] = rtrim(input::get()['area'][0],',');

        $postData['user_id'] = $userId;
        $area = app::get('topc')->rpcCall('logistics.area',array('area'=>$postData['area']));

        if($area)
        {
            $areaId =  str_replace(",","/", $postData['area']);
            $postData['area'] = $area . ':' . $areaId;
        }
        else
        {
            $msg = app::get('topc')->_('地区不存在!');
            return $this->splash('error',null,$msg);
        }
		//$postData['card_id']  验证身份证 2016/3/20
       if(!$this->check_identity($postData['card_id']))
        {
           $msg = app::get('topc')->_('身份证格式不正确!');
            return $this->splash('error',null,$msg);
        }


        try
        {

            app::get('topc')->rpcCall('user.address.add',$postData);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();

            return $this->splash('error',null,$msg);
        }

        /*获取收货地址 start*/
        $params['user_id'] = userAuth::id();
        $userAddrList = app::get('topc')->rpcCall('user.address.list',$params);
        $userAddrList = $userAddrList['list'];
        foreach ($userAddrList as &$addr) {
            list($regions,$region_id) = explode(':', $addr['area']);
            $addr['region_id'] = str_replace('/', ',', $region_id);
        }
        $pagedata['userAddrList'] = $userAddrList;
        return view::make('topc/checkout/address/addr_list.html', $pagedata);
        /*收货地址 end*/
    }
/**2016/3/20 lcd
 * 验证18位身份证（计算方式在百度百科有）
 * @param  string $id 身份证
 * return boolean
 */

function check_identity($id='')
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



    public function addr_dialog()
    {
        return view::make('topc/checkout/address/addr_dialog.html');
    }

    public function checkout()
    {
        $this->setLayoutFlag('order_index');
        header("cache-control: no-store, no-cache, must-revalidate");
        $postData =utils::_filter_input(input::get());
        $cartFilter['mode'] = $postData['mode'] ? $postData['mode'] :'cart';//默认为加到购物车
        $pagedata['mode'] = $postData['mode'];

        /*获取收货地址 start*/
        $params['user_id'] = userAuth::id();
        $userAddrList = app::get('topc')->rpcCall('user.address.list',$params);
        $userAddrList = $userAddrList['list'];
        foreach ($userAddrList as &$addr) {
            list($regions,$region_id) = explode(':', $addr['area']);
            $addr['region_id'] = str_replace('/', ',', $region_id);
            if(isset($addr['card_id']) && trim($addr['card_id']) != ""){ //身份证四取前3后四位;
            	$newaddr = substr($addr['card_id'],0,3). "****".substr($addr['card_id'],-4);
            	$addr["card_id"] = $newaddr ;
            }
        }
        $pagedata['userAddrList'] = $userAddrList;
        /*收货地址 end*/

		/*获取购买人身份证号码*/
        /*$objMdlSysuser = app::get('sysuser')->model('user');
        $sysuserUser = $objMdlSysuser->getRow('usercard',$params);
        $pagedata['buycard'] = $sysuserUser['usercard'];*/
        $pagedata['buycard']=1;

        // 商品信息
        $cartFilter['needInvalid'] = false;
        $cartFilter['platform'] = 'pc';
        $cartFilter['user_id'] = userAuth::id();
        $cartInfo = app::get('topc')->rpcCall('trade.cart.getCartInfo', $cartFilter,'buyer');
        if(!$cartInfo)
        {
            $resetUrl = url::action('topc_ctl_default@index');
            return $this->splash('failed', $resetUrl);
        }

        $isSelfShop = true;
        $pagedata['ifOpenOffline'] = app::get('ectools')->getConf('ectools.payment.offline.open');
        $pagedata['ifOpenZiti'] =app::get('syslogistics')->getConf('syslogistics.ziti.open');
        
        //print_r($cartInfo);
        //判断所有订单商品是完税还是真邮，如两者都有，则需要显示两种配送方式。
        foreach($cartInfo['resultCartData'] as $key=>$val)
        {
            if($val['shop_type'] != "self")
            {
                $isSelfShop = false;
            }
            else
            {
                $isSelfShopArr[] = $val['shop_id'];
            }
        }
        //$pagedata['istax_types'] = $tax_index_ids ? implode("-",$tax_index_ids) : 1; //显示参数有1，3 或1－3
        $pagedata['isSelfShop'] = $isSelfShop;
        $pagedata['cartInfo'] = $cartInfo;
        
        //print_r($cartInfo);

        //用户验证购物车数据是否发生变化
        $md5CartFilter = array('user_id'=>userAuth::id(), 'platform'=>'pc', 'mode'=>$cartFilter['mode'], 'checked'=>1);
        $md5CartInfo = md5(serialize(utils::array_ksort_recursive(app::get('topc')->rpcCall('trade.cart.getBasicCartInfo', $md5CartFilter, 'buyer'), SORT_STRING)));
        $pagedata['md5_cart_info'] = $md5CartInfo;

        //获取默认图片信息
        $pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');

        // 刷新结算页则失效前面选则的优惠券
        $shop_ids = array_keys($pagedata['cartInfo']['resultCartData']);
        foreach($shop_ids as $sid)
        {
            $apiParams = array(
                'coupon_code' => '-1',
                'shop_id' => $sid,
            );
            app::get('topc')->rpcCall('trade.cart.cartCouponCancel', $apiParams, 'buyer');
        }

        $pagedata['if_open_point_deduction'] = app::get('topc')->rpcCall('point.setting.get',['field'=>'open.point.deduction']);
        return $this->page('topc/checkout/index.html', $pagedata);
    }
	/**
	 * 计算订单中所有费用（运费 + 订单费用 ）
	 * 统计中带有一些：
	 * 1、包邮：   完税产品：满50包邮
	 * 
	 * 2、活动：   满多少减多少。  
	 */
    public function total()
    {
        $postData = input::get();
        if($postData['current_shop_id'])
        {
            $current_shop_id = $postData['current_shop_id'];
            unset($postData['current_shop_id']);
        }
        $params['user_id'] = userAuth::id();
        $params['addr_id'] = $postData['addr_id'];
        $params['fields'] = 'area';
        $addr = app::get('topc')->rpcCall('user.address.info',$params,'buyer');
        list($regions,$region_id) = explode(':', $addr['area']);

        $cartFilter['needInvalid'] = $postData['checkout'] ? false : true;
        $cartFilter['platform'] = 'pc';
        $cartFilter['user_id'] = userAuth::id();
        $cartFilter['mode'] = $postData['mode'] ? $postData['mode'] :'cart';
        $cartInfo = app::get('topc')->rpcCall('trade.cart.getCartInfo', $cartFilter, 'buyer');
        $allPayment = 0;
        $objMath = kernel::single('ectools_math');
        $objTradeCreate = kernel::single("systrade_data_trade_create");
        $tempId = 0 ;//模板ID
        //print_r($cartInfo);
        foreach ($cartInfo['resultCartData'] as $shop_id => $tval) {
        	//region_id = 440000/440300/440305        	 //mark = 
        	$otherparam = array("user_id"=>$params['user_id'],"region_id" => $region_id,"area_id"=>$params['addr_id'],"order_mark"=> "","payment_type"=> $postData["payment_type"],"source_from"=> "pc" );
        	//快递方式（以后会改成 数组来传值）
        	$tempId = isset($postData['shipping'][$shop_id]["template_id"])? intval($postData['shipping'][$shop_id]["template_id"]): 0; 
        	
            //当自提时，运费默认为0
            /*if($postData['distribution'][$tval['shop_id']]['type'] == 0) {
                $tempId = 0;  //不用运费
            }*/
            //结构排序
        	$shop_orderinfo  = $objTradeCreate->init_shopOrderData($tval,$tempId,$params['user_id'], $otherparam);
        	//print_r($shop_orderinfo);
        	$sea_reginlist = array();
        	if($shop_orderinfo && !empty($shop_orderinfo["orderlist"])){
        		foreach($shop_orderinfo["orderlist"] as $sr_id => $mvk){
        			unset($mvk["orders"]);
        			$sea_reginlist[$sr_id] = $mvk;
        		}
        	}
            /*          	统计当前店铺下面要拆成几个订单
            $number = 0;
            if($tval["object2"] && !empty($tval["object2"])){
            	foreach($tval["object2"] as $k=> $v){
            		if($k && trim($k)!="" && isset($v) && !empty($v) && count($v)>0){
            			$number++ ; //有效的订单数据
            		}
            	}
            }
            $totalParams = array(
                'discount_fee' => $tval['cartCount']['total_discount'],
                'total_fee' => $tval['cartCount']['total_fee'],
                'total_weight' => $tval['cartCount']['total_weight'],
                'shop_id' => $tval['shop_id'],
                'template_id' => $tempId,
                'region_id' => str_replace('/', ',', $region_id),
                'usedCartPromotionWeight' => $tval['usedCartPromotionWeight'],
            );
            if($number) $totalParams["order_number"] = intval($number) > 1 ? intval($number) :1;
            //print_r($totalParams);
            //查询运费
            $totalInfo = app::get('topc')->rpcCall('trade.price.total',$totalParams,'buyer');
            
            //获取当前地址的单个邮费额；
            $current_postfee = isset($totalInfo["post_fee"])? floatval($totalInfo["post_fee"]) : 0;
            if(intval($number)>0 && floatval($current_postfee)>0 ){
            	$countPay = floatval($totalInfo["total_fee"]) + $number * floatval($current_postfee) - floatval($totalInfo["discount_fee"]); 
            	$totalInfo["payment"]= floatval($totalInfo["payment"]) >0 ? $totalInfo["payment"] : $countPay; //计算中包括邮费，并除出优惠部分
            }
            */
            //店铺统计数据
        	$shopTotal = isset($shop_orderinfo["shop_total"]) ? $shop_orderinfo["shop_total"] : false ; 
        	
        	//当前店铺总支付数
        	$shop_paymoney =  isset($shopTotal["all_order_payment"]) ? floatval($shopTotal["all_order_payment"]) : 0 ;
            
            //总支付额、、 多个店铺相加
            $trade_data['allPayment'] = $objMath->number_plus(array($trade_data['allPayment'], $shop_paymoney));
            
            if($current_shop_id && $shop_id != $current_shop_id){
                continue;
            }
            //店铺统计： 拆单，拆成多少个单，就需要有几个快递费用；
			$trade_data['shop'][$shop_id]['shop_id'] = $shop_id;									 //2016/3/23 店铺id雷成德
            $trade_data['shop'][$shop_id]['tax'] = $tval['cartCount']['tax'];	//2016/3/23 业务模式雷成德
            $trade_data['shop'][$shop_id]['child_post_num'] = intval($shopTotal['all_order_nums'])>0 ? intval($shopTotal['all_order_nums']) :1; //订单数量
            $trade_data['shop'][$shop_id]['tax_rate_price'] = $tval['cartCount']['tax_rate_price'];//2016/3/23 税雷成德
			$trade_data['shop'][$shop_id]['reg_rate_price'] = $tval['cartCount']['reg_rate_price'];//2016/3/23 税雷成德
			$trade_data['shop'][$shop_id]['payment'] 		= $shop_paymoney ;
            $trade_data['shop'][$shop_id]['total_fee'] 		= $shopTotal['all_order_totalamount'];
            $trade_data['shop'][$shop_id]['discount_fee'] 	= $shopTotal['all_order_totaldisamount'];
            $trade_data['shop'][$shop_id]['obtain_point_fee'] = 0 ;
            $trade_data['shop'][$shop_id]['post_fee'] 		= $shopTotal['all_order_totalpostfee'];
            $trade_data['shop'][$shop_id]['totalWeight']  	=  $shopTotal['all_order_totalweight'];
            //新加一个对像是子订单统计数据
            $trade_data['shop'][$shop_id]['clist']  		=  $sea_reginlist;
        }
        // print_r($trade_data);
        return response::json($trade_data);exit;
    }

    public function getCoupons()
    {
        $filter['pages'] = 1;
        $pageSize = 100;
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => $pageSize,
            'fields' => '*',
            'user_id' => userAuth::id(),
            'shop_id' => intval(input::get('shop_id')),
            'is_valid' => 1,
            'platform' => 'pc',
        );
        $couponListData = app::get('topc')->rpcCall('user.coupon.list', $params, 'buyer');
        $pagedata['couponList'] = $couponListData['coupons'];
        // $pagedata['count'] = $couponListData['count'];

        return  view::make('topc/checkout/coupons.html', $pagedata)->render();
    }

    public function useCoupon()
    {
        try
        {
            $mode = input::get('mode');
            $buyMode = $mode ? $mode :'cart';
            $apiParams = array(
                'coupon_code' => input::get('coupon_code'),
                'mode' => $buyMode,
                'platform' => 'pc',
            );
            if( app::get('topc')->rpcCall('promotion.coupon.use', $apiParams,'buyer') )
            {
                $msg = app::get('topc')->_('使用优惠券成功！');
                return $this->splash('success', null, $msg, true);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
    }

    public function cancelCoupon()
    {
        try
        {
            $apiParams = array(
                'coupon_code' => input::get('coupon_code'),
                'shop_id' => input::get('shop_id'),
            );
            if( app::get('topc')->rpcCall('trade.cart.cartCouponCancel', $apiParams,'buyer') )
            {
                $msg = app::get('topc')->_('取消优惠券成功！');
                return $this->splash('success', null, $msg, true);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
    }

    /**
     * @brief 获取指定店铺的配送方式
     *
     * @return json
     */
    public function getDtyList()
    {
        $postData = input::get();
        if(!$postData['shop_id']) return null;
        $tmpParams = array(
            'shop_id' => $postData['shop_id'],
			'select_tax' => $postData['select_tax'],//2016/4/5  业务模式  雷成德
			'select_region' => $postData['select_region'],  //2016/4/5 业务区域  雷成德
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $dtytmpls = app::get('topc')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        $dtytmpls = $dtytmpls['data'];
        if(!$dtytmpls) return null;
        $fareParams['template_id'] = implode(',',array_column($dtytmpls,'template_id'));
        $fareParams['weight'] = $postData['weight'];
        $fareParams['areaIds'] = $postData['areaId'];
        $fareList = app::get('topc')->rpcCall('logistics.fare.count',$fareParams);

        foreach($dtytmpls as $key=>$val)
        {
            $feeConf = $val['fee_conf'];

            $dtytmpls[$key]['post_fee'] = $fareList[$val['template_id']];
        }
        return response::json($dtytmpls);
    }

    /**
     * @brief 获取上门自取的地址列表
     *
     * @return html
     */
    public function getZitiList()
    {
        $postData = input::get();
        $params['user_id'] = userAuth::id();
        $params['addr_id'] = $postData['addr_id'];
        $params['fields'] = "area";
        $addrInfo= app::get('topc')->rpcCall('user.address.info',$params);
        $area = explode(':',$addrInfo['area']);
        $area = implode(',',explode('/',$area[1]));
        $pagedata['data'] = app::get('topc')->rpcCall('logistics.ziti.list',array('area_id'=>$area));
        $pagedata['ziti_id'] = $postData['ziti_id'];
        return  view::make('topc/checkout/dialog/take_goods.html', $pagedata)->render();
    }


public function buycard()
{     $postData = input::get();
	 $params['user_id'] = userAuth::id();

       if(!$this->check_identity($postData['buycard']))
        {
       return 1;
        }

	  /*获取购买人身份证号码*/
         $objMdlSysuser = app::get('sysuser')->model('user');
           $data = array(
            'usercard' => $postData['buycard'],
         );
		   $resurt=$objMdlSysuser->update($data,$params);
		   if($resurt){
			return 2; 
		   }else{
		   return 3;
		   }
}



    public function shoppingnotes()
    {
        return  view::make('topc/checkout/dialog/shoppingnotes.html')->render();
    }


}


