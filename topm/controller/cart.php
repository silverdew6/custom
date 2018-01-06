<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topm_ctl_cart extends topm_controller{

    public $payType = array(
        'online' => '线上支付',
        'offline' => '货到付款',
    );
    public function __construct(&$app)
    {
        parent::__construct();
        // 检测是否登录
        if( !userAuth::check() )
        {
            redirect::action('topm_ctl_passport@signin')->send();exit;
        }
        $this->setLayoutFlag('cart');
    }

    public function index()
    {
        header("cache-control: no-store, no-cache, must-revalidate");
        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');
        $cartData = app::get('topm')->rpcCall('trade.cart.getCartInfo', array('platform'=>'wap', 'user_id'=>userAuth::id()), 'buyer');
        $pagedata['aCart'] = $cartData['resultCartData'];
        
        //print_r($cartData['resultCartData']);
		$pagedata['title']="我的购物车";
        $pagedata['totalCart'] = $cartData['totalCart'];
        
		// 店铺可领取优惠券    //展示可以领取的优惠券；
        foreach ($pagedata['aCart'] as &$v) {
            $params = array(
                'page_no' => 0,
                'page_size' => 1,
                'fields' => '*',
                'shop_id' => $v['shop_id'],
                'platform' => 'wap',
                'is_cansend' => 1,
            );
            $couponListData = app::get('topm')->rpcCall('promotion.coupon.list', $params, 'buyer');
            if($couponListData['count']>0){
                $v['hasCoupon'] = 1;
            }
        }
        
        //结算页面－〉店铺优惠券，当订单满足券的条件就显示优惠信息出来
        return $this->page('topm/cart/index.html', $pagedata);
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
        
        //return $this->splash('error',"", "EEEE",true);

        $params['obj_type'] = $obj_type ? $obj_type : 'item';
        $params['mode'] = $mode ? $mode : 'cart';
        $params['user_id'] = userAuth::id();
        if( $params['obj_type']=='package' )
        {
            $package_id = input::get('package_id');
            $params['package_id'] = intval($package_id);
            $skuids = input::get('package_item');
            $tmpskuids = array_column($skuids, 'sku_id');
            $params['package_sku_ids'] = implode(',', $tmpskuids);
            $params['quantity'] = input::get('package-item.quantity',1);
        }
        if( $params['obj_type']=='item')
        {
            $quantity = input::get('item.quantity');
            $params['quantity'] = $quantity ? $quantity : 1; //购买数量，如果已有购买则累加
            $params['sku_id'] = intval(input::get('item.sku_id'));
        }

        try
        {
            $data = app::get('topm')->rpcCall('trade.cart.add', $params, 'buyer');
            if( $data === false )
            {
                $msg = app::get('topm')->_('加入购物车失败!');
                return $this->splash('error',null,$msg,true);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        $msg = app::get('topm')->_('成功加入购物车');
        if( $params['mode'] == 'fastbuy' )
        {
            $url = url::action('topm_ctl_cart@checkout',array('mode'=>'fastbuy') );
            $msg = "";
        }
        return $this->splash('success',$url,$msg,true);
    }

    public function updateCart()
    {
        $mode = input::get('mode');//
        $obj_type = input::get('obj_type','item');
        $postCartId = input::get('cart_id');
        $postCartNum = input::get('cart_num');
        $postPromotionId = input::get('promotionid');

		$f_cart_shop = input::get('f_cart_shop');  //2016/3/31 跨境勾选 雷成德
		$p_cart_shop = input::get('p_cart_shop');//2016/3/31 屏蔽        雷成德
		$objMdlCart_tax = app::get('systrade')->model('cart_tax');

        $userid  =	userAuth::id();
        $params = array();
        foreach ($postCartId as $cartId => $v)
        {
            $data['mode'] = $mode;
            $data['obj_type'] = $obj_type;
            $data['cart_id'] = intval($cartId);
            $data['totalQuantity'] = intval($postCartNum[$cartId]);
            $data['selected_promotion'] = intval($postPromotionId[$cartId]);
            $data['user_id'] = $userid;
            if($v=='1'){
                $data['is_checked'] = '1';
            }
            if($v=='0'){
                $data['is_checked'] = '0';
            }
            $params[] = $data;
        }
        try
        {
        	//更新购物车信息 
            foreach($params as $updateParams)
            {
                $data = app::get('topm')->rpcCall('trade.cart.update',$updateParams);
                if($data === false){
                    $msg = app::get('topm')->_('更新失败');
                    return $this->splash('error',null,$msg,true);
                }
            }
        }catch(Exception $e){
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

	    //检查勾选保税或者直邮2016/3/13 lcd
        $key = array_search('1', $postCartId); 
		$objMdlCart = app::get('systrade')->model('cart');
        if($key){	//如果勾选了复选框
			$filter['cart_id']= $key;
			$carts =	$objMdlCart->getRow('user_id,tax_sea_region,tax,shop_id',$filter); 
		    $finish['user_id']= $carts['user_id'];
		    $cartlists =	$objMdlCart->getList('tax,cart_id,tax_sea_region,shop_id',$finish);
		   	if($carts['tax']==1){//完税
				foreach ($cartlists  as $cart)
				{
					if($cart['tax']==1){
			           $tax_ifter['cart_id']=$cart['cart_id'];
					   $tax_data['select_tax']=0;
					   $objMdlCart->update($tax_data,$tax_ifter);
					}else{
					   $tax_ifter['cart_id']=$cart['cart_id'];
					   $tax_data['select_tax']=1;
					   $objMdlCart->update($tax_data,$tax_ifter);
					}
				}
			}else{ //保税和直邮
				foreach ($cartlists  as $cart){
					//同一w；
					if(($cart['shop_id']==$carts['shop_id'])&&($cart['tax_sea_region']==$carts['tax_sea_region']))
					{
			           $tax_ifter['cart_id']=$cart['cart_id'];
					   $tax_datas['select_tax']=0;
					   $objMdlCart->update($tax_datas,$tax_ifter);
					}else{
						$tax_ifter['cart_id']=$cart['cart_id'];
					    $tax_datas['select_tax']=1;
					    $objMdlCart->update($tax_datas,$tax_ifter);
					}
				}
			}
        }else{	//什么也不勾选的情况2016/3/16lcd
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
	 	//业务模式和区域屏蔽项。
     	foreach ($p_cart_shop as $shop_taxId => $p)
        {
            $dataes_p['tax_id'] = intval($shop_taxId);
            $dataes_p['user_id'] = $userid;
            $d['disabled_id'] = $p;
		    $result = $objMdlCart_tax->update($d,$dataes_p);
		}
        $cartData = app::get('topm')->rpcCall('trade.cart.getCartInfo', array('platform'=>'wap', 'user_id'=>userAuth::id()), 'buyer');
        $pagedata['aCart'] = $cartData['resultCartData'];
        //print_r($cartData); exit; 
        // 临时统计购物车页总价数量等信息
        $totalWeight = 0;
        $totalNumber = 0;
        $totalPrice  = 0;
        $totalDiscount = 0;
        $totalTax_rate_price  = 0; //2016/3/10 lcd 添加消费税
		$totalTeg_rate_price  = 0; //2016/3/10 lcd 添加增值税率
		$zonghe_rateprice = 0;
		//print_r($cartData['resultCartData']);
        foreach($cartData['resultCartData'] as $v){
            $totalWeight += $v['cartCount']['total_weight'];
            $totalNumber += $v['cartCount']['itemnum'];
			$totalTax_rate_price   += $v['cartCount']['tax_rate_price']; //2016/3/10 lcd 添加税费
			$totalTeg_rate_price   += $v['cartCount']['reg_rate_price']; //2016/3/10 lcd 添加税费
			$zonghe_rateprice      =  $zonghe_rateprice + floatval($v['cartCount']['zonghe_rateprice']); 	// 综合税费额；
            $totalPrice 		   += $v['cartCount']['total_fee'];
            $totalDiscount         += $v['cartCount']['total_discount'];
			if($v['cartCount']['tax']){
				$tax 			   = $v['cartCount']['tax'];
			}
        }
		// 总金额+税费超过50计算运费，2016/3/10 lcd
		//是否包含有需要计算税的判断方法，tax_ids 是否含有2的数字；   /// 不再使用$tax 来判断  by xch  2016/12/ 22
		$tax_ids = isset($v["tax_ids"])?trim($v["tax_ids"]) : "";
		if($tax_ids && (strpos($tax_ids,"2" )>=0 || strpos($tax_ids,"3" )>=0)){ //有
			$totalAfterDiscount=ecmath::number_minus(array($totalPrice, $totalDiscount)) + $zonghe_rateprice;
		}else{
			$totalTax_rate_price =0;
			$totalTeg_rate_price =0;
	        $totalAfterDiscount=ecmath::number_minus(array($totalPrice, $totalDiscount));
		}
		//var_dump($totalAfterDiscount);
        $totalCart['tax'] = $tax;
        $totalCart['totalWeight'] = $totalWeight;
        $totalCart['number'] = $totalNumber;
        $totalCart['totalPrice'] = $totalPrice;
		$totalCart['totalTax_rate_price'] = $zonghe_rateprice ; //2016/3/10 lcd 添加综合税费
		$totalCart['totalTeg_rate_price'] = $totalTeg_rate_price; //2016/3/10 lcd 添加增值税费
        $totalCart['totalAfterDiscount'] = $totalAfterDiscount; //2016/3/10 lcd 总价和税费
        $totalCart['totalDiscount'] = $totalDiscount;
		$totalCart['totalFirstDiscount'] = ecmath::number_minus(array($totalPrice, $totalDiscount));  //不计算税费的总价,总价减去优惠的价格
        $totalCart['zonghe_total_ratePrice'] = $zonghe_rateprice; //总的综全税之各
        $pagedata['totalCart'] = $totalCart;
        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');
        foreach(input::get('cart_shop') as $shopId => $cartShopChecked){
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
            $couponListData = app::get('topm')->rpcCall('promotion.coupon.list', $params, 'buyer');
            if($couponListData['count']>0)
            {
                $v['hasCoupon'] = 1;
            }
        }
		//print_r($pagedata);
        $msg = view::make('topm/cart/cart_main.html', $pagedata)->render();

        return $this->splash('success',null,$msg,true);
    }

    public function ajaxBasicCart()
    {
        $cartData = app::get('topm')->rpcCall('trade.cart.getCartInfo', array('platform'=>'wap', 'user_id'=>userAuth::id()), 'buyer');

        $pagedata['aCart'] = $cartData['resultCartData'];

        $pagedata['totalCart'] = $cartData['totalCart'];

        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');

        foreach(input::get('cart_shop') as $shopId => $cartShopChecked)
        {
            $pagedata['selectShop'][$shopId] = $cartShopChecked=='on' ? true : false;
        }
        $pagedata['selectAll'] = input::get('cart_all')=='on' ? true : false;

        $msg = view::make('topm/cart/cart_main.html', $pagedata)->render();

        return $this->splash('success',null,$msg,true);
    }

    /**
     * @brief 删除购物车中数据
     *
     * @return
     */
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
            $res = app::get('topm')->rpcCall('trade.cart.delete',$params);
            if( $res === false )
            {
                throw new Exception(app::get('topm')->_('删除失败'));
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
        return $this->splash('success',null,'删除成功',true);
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
        $addr = app::get('topm')->rpcCall('user.address.info',$params,'buyer');
        list($regions,$region_id) = explode(':', $addr['area']);

        $cartFilter['needInvalid'] = $postData['checkout'] ? false : true;
        $cartFilter['platform'] = 'pc';
        $cartFilter['user_id'] = userAuth::id();
        $cartFilter['mode'] = $postData['mode'] ? $postData['mode'] :'cart';
        $cartInfo = app::get('topm')->rpcCall('trade.cart.getCartInfo', $cartFilter, 'buyer');
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
            //店铺统计数据 （支付金额已经除去营销优惠，但没有除去优惠券抵扣金额 ；也没有加上税的计算，请在这里加上   by XCH ）
        	$shopTotal = isset($shop_orderinfo["shop_total"]) ? $shop_orderinfo["shop_total"] : false ; 
        	
        	/** 当前店铺订单总支付金额 ：
        	 * 	公式  ＝＝＝〉〉   总支付金额  ＝ （  店铺订单金额   － 总优惠费   － 使用优惠券金额 ） + 总运费 + 综合税
        	 *  注意  ： 优惠券不能抵运费，也不能抵综合税；
        	 *  即： （  店铺订单金额   － 总优惠费   － 使用优惠券金额 ） >= 0 始终成立
        	 */
        	$shop_paymoney =  isset($shopTotal["all_order_payment"]) ? floatval($shopTotal["all_order_payment"]) : 0 ;
        	$shop_postmoney =  isset($shopTotal["all_order_totalpostfee"]) ? floatval($shopTotal["all_order_totalpostfee"]) : 0 ;
            $shop_zonghe_rprice = isset($shopTotal["all_rate_price"]) ? floatval($shopTotal["all_rate_price"]) : 0 ;
            //优惠券减掉的金额
            $shop_coupon_money = isset($shopTotal["coupon_discount_money"]) ? floatval($shopTotal["coupon_discount_money"]) : 0 ;
           	$currentShopTotalPay = ($shop_paymoney - $shop_coupon_money)  +  $shop_zonghe_rprice;
           	if($shop_paymoney - $shop_coupon_money <= 0 ){ //订单金额用优惠券全抵完，则需要支付运费和税费；
           		$currentShopTotalPay = $objMath->number_plus(array($shop_postmoney , $shop_zonghe_rprice));
           	}
           	//支付金额    ＝＝＝＝多个店铺相加
            $trade_data['allPayment'] = $objMath->number_plus(array($trade_data['allPayment'], $currentShopTotalPay));
            $trade_data['allPostfee'] = $objMath->number_plus(array($trade_data['allPostfee'], $shop_postmoney));//总邮费；
            $trade_data['allDisMoney'] = $objMath->number_plus(array($trade_data['allDisMoney'], $shopTotal['all_order_totaldisamount']));//总优惠额；
            $trade_data['allCouponMoney'] = $objMath->number_plus(array($trade_data['allCouponMoney'],$shop_coupon_money));//总优惠券抵扣；
            if($current_shop_id && $shop_id != $current_shop_id){
                continue;
            }
            //店铺统计： 拆单，拆成多少个单，就需要有几个快递费用；
			$trade_data['shop'][$shop_id]['shop_id'] = $shop_id;	 			//2016/3/23 店铺id雷成德
            $trade_data['shop'][$shop_id]['tax'] = $tval['cartCount']['tax'];	//2016/3/23 业务模式雷成德
            $trade_data['shop'][$shop_id]['child_post_num'] = intval($shopTotal['all_order_nums'])>0 ? intval($shopTotal['all_order_nums']) :1; //订单数量
            $trade_data['shop'][$shop_id]['tax_rate_price'] = $tval['cartCount']['tax_rate_price'];//2016/3/23 税雷成德
			$trade_data['shop'][$shop_id]['reg_rate_price'] = $tval['cartCount']['reg_rate_price'];//2016/3/23 税雷成德
			$trade_data['shop'][$shop_id]['zonghe_rateprice'] = $tval['cartCount']['zonghe_rateprice'];// 综合税 by xch
			$trade_data['shop'][$shop_id]['payment'] 		= $currentShopTotalPay ; //  $objMath->number_plus(array($shop_paymoney, $tval['cartCount']['zonghe_rateprice']));//总邮费； ;
            $trade_data['shop'][$shop_id]['total_fee'] 		= $shopTotal['all_order_totalamount'];
            $trade_data['shop'][$shop_id]['discount_fee'] 	= $shopTotal['all_order_totaldisamount'];
            $trade_data['shop'][$shop_id]['obtain_point_fee'] = 0 ;
            $trade_data['shop'][$shop_id]['post_fee'] 		= $shopTotal['all_order_totalpostfee'];
            $trade_data['shop'][$shop_id]['totalWeight']  	=  $shopTotal['all_order_totalweight'];
            $trade_data['shop'][$shop_id]['coupon_discount_money'] = isset($shop_coupon_money) ? $shop_coupon_money :0 ;
            //新加一个对像是子订单统计数据
            $trade_data['shop'][$shop_id]['clist']	 		=  $sea_reginlist;
        }
        //print_r($trade_data);exit;
        return response::json($trade_data);exit;
    }
    
    /**
     * Ajax 获取 购物车中已有产品的数量；
     * 并返回给当前登录用户
     * (/countcart-number.html)
     */
    function countcartNumber(){
    	$user_id  = userAuth::id();
    	$returnda = array("success" => 0);
    	$pc = input::get('platform');
    	if($user_id && $user_id){
    		$cartFilter = array('user_id'=>$user_id ,'platform' => $pc ?$pc :'pc','needInvalid'=>true);
    		$cartInfo = app::get('topm')->rpcCall('trade.cart.getCartInfo', $cartFilter, 'buyer');
    		//print_r($cartInfo);
    		$totalcartnumber = 0;
    		if(isset($cartInfo) && isset($cartInfo["totalCart"])){
    			foreach($cartInfo["resultCartData"] as $k=> $Hong){
    				foreach($Hong["object"] as $kv=> $hong2){
    					$totalcartnumber += intval($hong2["quantity"]); //numbe统计 商品数量
    				}
    			}
    		}
    		if($totalcartnumber && intval($totalcartnumber) >0){
    			$returnda = array("success" => 1,"total"=> $totalcartnumber);
    		}
    	}else{
    		$returnda["total"] = 0;
    	}
    	//$returnda["total2"]= md5('ov7o9wRHRGCq84vEOLU6_0WJc7iI');
    	return response::json($returnda);exit;
    }

    /**
     * @brief 计算购物车金额
     *
     * @return
     */
    public function total_old()
    {
        $postData = input::get();
        if($postData['current_shop_id'])
        {
            $current_shop_id = $postData['current_shop_id'];
            unset($postData['current_shop_id']);
        }

        if($addrId = $postData['addr_id'])
        {
            $params['user_id'] = userAuth::id();
            $params['addr_id'] = $addrId;
            $params['fields'] = 'area';
            $addr = app::get('topm')->rpcCall('user.address.info',$params,'buyer');
            list($regions,$region_id) = explode(':', $addr['area']);
        }

        $cartFilter['mode'] = $postData['mode'] ? $postData['mode'] :'cart';
        $cartFilter['needInvalid'] = $postData['checkout'] ? false : true;
        $cartFilter['platform'] = 'wap';
        $cartFilter['user_id'] = userAuth::id();
        $cartInfo = app::get('topm')->rpcCall('trade.cart.getCartInfo', $cartFilter,'buyer');

        $allPayment = 0;
        $objMath = kernel::single('ectools_math');

        foreach ($cartInfo['resultCartData'] as $shop_id => $tval) {
        	$shipping_sid = $postData['shipping'][$tval['shop_id']]['template_id'];
        	//是否直邮，1件20，两件包邮流程by xch 
        	$is_tax3 = (isset($tval["cartCount"]) && isset($tval["cartCount"]["tax"]) && intval($tval["cartCount"]["tax"]) >1) ? true : false;
        	//统计订单件数
        	$goods_nums = (isset($tval["cartCount"]) && isset($tval["cartCount"]["itemnum"]) && intval($tval["cartCount"]["itemnum"]) >1) ?intval($tval["cartCount"]["itemnum"])  : 1;
        	if($is_tax3){
        		$shipping_sid = 10000; //使用固定的邮费
        	}
            $totalParams = array(
                'discount_fee' => $tval['cartCount']['total_discount'],
                'total_fee' => $tval['cartCount']['total_fee'],
                'total_weight' => $tval['cartCount']['total_weight'],
                'shop_id' => $tval['shop_id'],
                'template_id' =>$shipping_sid,
                'region_id' => $region_id ? str_replace('/', ',', $region_id) : '0',
                'usedCartPromotionWeight' => $tval['usedCartPromotionWeight'],
            );
            //统计当前店铺里按仓库来拆成多少个订单；
            $shop_o_numbs = isset($tval["object2"]) && is_array($tval["object2"]) ? count($tval["object2"]) : 1;
            if(isset($shop_o_numbs) && intval($shop_o_numbs) >=1){
            	$totalParams['order_number']= $shop_o_numbs;	 //统计当前店铺里按仓库来拆成多少个订单；
            }
            $totalInfo = app::get('topm')->rpcCall('trade.price.total',$totalParams,'buyer');
            if($current_shop_id && $shop_id != $current_shop_id){
                continue;
            }
            //print_r($totalInfo);
			if($tval['cartCount']['tax']>1){
				if($totalInfo['post_fee']){
						//保税或者直邮有运费的时候
						$tax_rate_price=0;
						$reg_rate_price=0;
						$trade_data['shop'][$shop_id]['payment'] = $totalInfo['payment'];
						foreach ( $tval['object'] as $kk=>$vv)
						{
							//计算消费税
							$tax=(($vv['price']['price']*$vv['quantity'])+($vv['price']['price']*$vv['quantity']/$totalInfo['total_fee']*$totalInfo['post_fee']))/(1-$vv['reg_rate'])*$vv['reg_rate']*0.7;
							$tax_rate_price= $tax_rate_price+$tax;
								   //计算增值税
							$reg=(($vv['price']['price']*$vv['quantity'])+$vv['price']['price']*$vv['quantity']/$totalInfo['total_fee']*$totalInfo['post_fee']+$tax)*$vv['tax_rate']*0.7;
							$reg_rate_price= $reg_rate_price+$reg;
						}
						//增加 +消费税+增值税
					    $trade_data['shop'][$shop_id]['payment'] = $totalInfo['payment']+$tax_rate_price+$reg_rate_price;
			            $trade_data['shop'][$shop_id]['tax_rate_price'] = $tax_rate_price;//2016/3/23 税雷成德
						$trade_data['shop'][$shop_id]['reg_rate_price'] =$reg_rate_price ;//2016/3/23 税雷成德
					}else{
			             //保税或者直邮没有运费的时候
						 //保税或者直邮有运费的时候
						$tax_rate_price=0;
						$reg_rate_price=0;
						$trade_data['shop'][$shop_id]['payment'] = $totalInfo['payment'];
						foreach ( $tval['object'] as $kk=>$vv)
						{
							//计算消费税
							$tax=($vv['price']['price']*$vv['quantity'])/(1-$vv['reg_rate'])*$vv['reg_rate']*0.7;
							$tax_rate_price= $tax_rate_price+$tax;
								   //计算增值税
							$reg=(($vv['price']['price']*$vv['quantity'])+$tax)*$vv['tax_rate']*0.7;
							$reg_rate_price= $reg_rate_price+$reg;
						}
						//增加 +消费税+增值税
					    $trade_data['shop'][$shop_id]['payment'] = $totalInfo['payment']+$tax_rate_price+$reg_rate_price;
			            $trade_data['shop'][$shop_id]['tax_rate_price'] = $tax_rate_price;//2016/3/23 税雷成德
						$trade_data['shop'][$shop_id]['reg_rate_price'] =$reg_rate_price ;//2016/3/23 税雷成德
					}
					//计算所有支付总额和总运费
		            $trade_data['allPayment'] = $objMath->number_plus(array($trade_data['allPayment'] ,$totalInfo['payment']))+$tax_rate_price+$reg_rate_price;
		            $trade_data['allPostfee'] = $objMath->number_plus(array($trade_data['allPostfee'] ,$totalInfo['post_fee']));
			}else{
				//店铺子订单邮费
				$allPFee_shop = isset($totalInfo['all_post_fee']) ?  $totalInfo['all_post_fee'] : $totalInfo['post_fee'] ; 
	            $trade_data['allPayment'] = $objMath->number_plus(array($trade_data['allPayment'] ,$totalInfo['payment']));
	            $trade_data['allPostfee'] = $objMath->number_plus(array($trade_data['allPostfee'] ,$allPFee_shop));
				$tval['cartCount']['tax_rate_price']=0;
				$tval['cartCount']['reg_rate_price']=0;
	            $trade_data['shop'][$shop_id]['payment'] = $totalInfo['payment'];
	            $trade_data['shop'][$shop_id]['tax_rate_price'] = $tval['cartCount']['tax_rate_price'];//2016/3/23 税雷成德
				$trade_data['shop'][$shop_id]['reg_rate_price'] = $tval['cartCount']['reg_rate_price'];//2016/3/23 税雷成德
			}
            $trade_data['shop'][$shop_id]['tax'] = $tval['cartCount']['tax'];//2016/3/23 业务模式雷成德
            $trade_data['shop'][$shop_id]['shop_order_number'] = $shop_o_numbs ? intval($shop_o_numbs) : 1 ;  //新增统计店铺子订单数量
       		//  $trade_data['shop'][$shop_id]['payment'] = $totalInfo['payment'];
            $trade_data['shop'][$shop_id]['total_fee'] = $totalInfo['total_fee'];
            $trade_data['shop'][$shop_id]['discount_fee'] = $totalInfo['discount_fee'];
            $trade_data['shop'][$shop_id]['obtain_point_fee'] = $totalInfo['obtain_point_fee'];
            $trade_data['shop'][$shop_id]['post_fee'] = $totalInfo['post_fee'];
            $trade_data['shop'][$shop_id]['totalWeight'] += $tval['cartCount']['total_weight'];
        }
        return response::json($trade_data);exit;
    }

    public function checkout()
    {

        $this->setLayoutFlag('order_index');
        header("cache-control: no-store, no-cache, must-revalidate");
        $postData =utils::_filter_input(input::get());
        $cartFilter['mode'] = $postData['mode'] ? $postData['mode'] :'cart';
        $pagedata['mode'] = $postData['mode'];

        try
        {
            /*获取收货地址 start*/
            if(isset($postData['addr_id']) && $postData['addr_id'])
            {
                $params['user_id'] = userAuth::id();
                $params['addr_id'] = $postData['addr_id'];
                $userDefAddr = app::get('topm')->rpcCall('user.address.info',$params);

                //身份证实名认证 start
                $userDefAddr['cd_pd']=1;
                //身份证查询
                $iscard = app::get('topm')->rpcCall('user.card.index', $userDefAddr);
                if($iscard){
                    $newaddr = substr($iscard,0,3). "****".substr($iscard,-4);
                    $userDefAddr['card_pd']=$newaddr;
                    $userDefAddr['cd_pd']=0;
                }else{     //前期需求新增判断
                    if(trim($userDefAddr['card_id'])!=''){
                        $newaddr = substr($userDefAddr['card_id'],0,3). "****".substr($userDefAddr['card_id'],-4);
                        $userDefAddr['card_pd']=$newaddr;
                        $userDefAddr['cd_pd']=2;
                    }
                }
                //身份证实名认证 end
            }
            else
            {
                // 获取默认地址
                $params['user_id'] = userAuth::id();
                $params['def_addr'] = 1;
                $userDefAddr = app::get('topm')->rpcCall('user.address.list',$params);
                $userDefAddr = $userDefAddr['list']['0'];
                if(!$userDefAddr['list'])//是否有默认地址
                {
                    $userAddr= app::get('topm')->rpcCall('user.address.count',array('user_id'=>$params['user_id']));
                    $pagedata['nowcount'] = $userAddr['nowcount'];
                }

                //身份证实名认证 start 17/09/01
                $userDefAddr['cd_pd']=1;
                //身份证查询
                if(!empty($userDefAddr['name'])) {
                    $iscard = app::get('topc')->rpcCall('user.card.index', $userDefAddr);
                    if ($iscard) {
                        $newaddr = substr($iscard, 0, 3) . "****" . substr($iscard, -4);
                        $userDefAddr['card_pd'] = $newaddr;
                        $userDefAddr['cd_pd'] = 0;
                    } else {     //前期需求新增判断
                        if (trim($userDefAddr['card_id']) != '') {
                            $newaddr = substr($userDefAddr['card_id'], 0, 3) . "****" . substr($userDefAddr['card_id'], -4);
                            $userDefAddr['card_pd'] = $newaddr;
                            $userDefAddr['cd_pd'] = 2;
                        }
                    }
                }
                //身份证实名认证 end
            }
            $pagedata['def_addr'] = $userDefAddr;
            /*	获取收货地址 end*/
			/* 20160924  跟小本确认，收货人信息中有身份证号就可以走直邮
			 * 用户下单时购买人的身份证号码不需要一定有
			 *
			 *  $card['user_id']=userAuth::id();
		        $objMdlSysuser = app::get('sysuser')->model('user');
		        $sysuserUser = $objMdlSysuser->getRow('usercard',$card);
		        $pagedata['buycard'] = $sysuserUser['usercard'];
			 *
			 */
			//默认选择线上支付
            if(isset($postData['pay_type']))
            {
                $pagedata['payType'] = array('pay_type'=>$postData['pay_type'],'name'=>$this->payType[$postData['pay_type']]);
            }else{
            	//默认取一个线上支付的方式；
            	$pagedata['payType'] = array('pay_type'=>"online",'name'=>$this->payType["online"]."");
            }
            //print_r($pagedata); exit;
            // 商品信息
            $cartFilter['needInvalid'] = false;
            $cartFilter['platform'] = 'wap';
            $cartFilter['user_id'] = userAuth::id();
            $cartInfo = app::get('topm')->rpcCall('trade.cart.getCartInfo', $cartFilter,'buyer');
            if(!$cartInfo)
            {
                $resetUrl = url::action('topm_ctl_default@index');
                return $this->splash('error', $resetUrl);
            }
            $cart_taxFlag = 0 ; //默认是完税类型的商品//不需要验证身份证号
            $isSelfShop = true;  //自营类型
            $pagedata['ifOpenOffline'] = app::get('ectools')->getConf('ectools.payment.offline.open');
            $pagedata['ifOpenZiti'] =app::get('syslogistics')->getConf('syslogistics.ziti.open');

			/*优化一下数据结构用来展示页面的数据；*/
            foreach($cartInfo['resultCartData'] as $key=>$val) {
            	$shop_tax_ids = isset($val['tax_ids'])? trim($val['tax_ids']):"";
            	$shop_tax_ids = $shop_tax_ids? explode("-",$shop_tax_ids):false ;
            	if($shop_tax_ids && (in_array(2,$shop_tax_ids) || in_array(3,$shop_tax_ids))){
            		$cart_taxFlag = 1 ; 			//不是完税，要验证身份证号
            	}
                if($val['shop_type'] != "self") {
                    $isSelfShop = false;
                } else {
                    $isSelfShopArr[] = $val['shop_id'];
                }
            }
            $pagedata['isTaxFirst'] = $cart_taxFlag ? $cart_taxFlag : 0 ;//默认是完税类型
            $pagedata['isSelfShop'] = $isSelfShop;
            //echo "<pre>"; print_r($cartInfo);print_r($pagedata); exit;

            $pagedata['cartInfo'] = $cartInfo;		//购物车信息；
            //用户验证购物车数据是否发生变化
            $md5CartFilter = array('user_id'=>userAuth::id(), 'platform'=>'wap', 'mode'=>$cartFilter['mode'], 'checked'=>1);
            $md5CartInfo = md5(serialize(utils::array_ksort_recursive(app::get('topm')->rpcCall('trade.cart.getBasicCartInfo', $md5CartFilter, 'buyer'), SORT_STRING)));
            $pagedata['md5_cart_info'] = $md5CartInfo;

            $ifOpenZiti = app::get('syslogistics')->getConf('syslogistics.ziti.open');
            if($isSelfShop && $ifOpenZiti == 'true' && $pagedata['def_addr'])
            {
                $area = explode(':',$pagedata['def_addr']['area']);
                $area = implode(',',explode('/',$area[1]));
                $zitiData = app::get('topm')->rpcCall('logistics.ziti.list',array('area_id'=>$area));
                $pagedata['zitiDataList'] = $zitiData;
            }

            $shop_ids = array_keys($pagedata['cartInfo']['resultCartData']);
			$shop_cartCount = array_column($pagedata['cartInfo']['resultCartData'], 'cartCount');  //2016/4/5 lcd 提取店铺的业务模式
            if( $isSelfShop )
            {
                $pagedata['dtyList'] = $this->__getDtyList($shop_ids,$isSelfShopArr,$zitiData,$shop_cartCount);//传值
            }
            else
            {
                $pagedata['dtyList'] = $this->__getDtyList($shop_ids,$isSelfShop,null,$shop_cartCount);//传值
            }
			//print_r($pagedata['cartInfo']['resultCartData']);
            //优惠券列表
            foreach ($pagedata['cartInfo']['resultCartData'] as &$v)
            {
                $nocoupon = array('0'=>array('coupon_name'=>'不使用优惠券', 'coupon_code'=>'-1'));
                $validcoupon = $this->getCoupons($v['shop_id']);
                $v['couponList'] = array_merge($nocoupon, $validcoupon);
            }
            //print_r($pagedata); exit;

            // 刷新结算页则失效前面选则的优惠券
            foreach($shop_ids as $sid)
            {
                $apiParams = array(
                    'coupon_code' => '-1',
                    'shop_id' => $sid,
                );
                app::get('topm')->rpcCall('trade.cart.cartCouponCancel', $apiParams, 'buyer');
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg);
        }
		$pagedata['title']="订单结算";
        $pagedata['if_open_point_deduction'] = app::get('topm')->rpcCall('point.setting.get',['field'=>'open.point.deduction']);
        return $this->page('topm/cart/checkout/index.html', $pagedata);
    }

    /**
     * @brief 获取收货地址列表
     *
     * @return  html
     */
    public function getAddrList()
    {
        $selectAddrId = input::get('selected');
        $ifedit = input::get('ifedit',false);
        $userId = userAuth::id();
        $userAddrList = app::get('topm')->rpcCall('user.address.list',array('user_id'=>$userId));
        $count = $userAddrList['count'];
        $userAddrList = $userAddrList['list'];
        foreach ($userAddrList as &$addr) {
            list($regions,$region_id) = explode(':', $addr['area']);
            $addr['region_id'] = str_replace('/', ',', $region_id);
            if($addr['def_addr'])
            {
                $userDefAddr = $addr;
            }
        }
        if(!$userAddrList)
        {
            return $this->editAddr();
        }
        $pagedata['userAddrList'] = $userAddrList;
        $pagedata['userDefAddr'] = $userDefAddr;
        $pagedata['selectedAddr'] = $selectAddrId;
        if($ifedit)
        {
            return $this->page('topm/cart/checkout/addredit.html', $pagedata);
        }
        else{
            return $this->page('topm/cart/checkout/addrlist.html', $pagedata);
        }
    }

    /**
     * @brief 修改收货地址
     *
     * @return
     */
    public function editAddr()
    {
        $selectAddrId = input::get('addr_id');
        $is_require_cardid = input::get('idcard');  //新增是否需要身份证必填
        if($selectAddrId)
        {
            $userId = userAuth::id();
            $addrInfo = app::get('topm')->rpcCall('user.address.info',array('addr_id'=>$selectAddrId,'user_id'=>$userId));
            list($regions,$region_id) = explode(':', $addrInfo['area']);
            $addrInfo['area'] = $regions;
            $addrInfo['region_id'] = str_replace('/', ',', $region_id);

            $pagedata['addrInfo'] = $addrInfo;
            $pagedata['addrdetail'] = $addrInfo['area'].'/'.$addrInfo['addr'];
        }
        /**
         * 根据订单是否是直邮来判断是否需要身份证必填
         */
        if($is_require_cardid && intval($is_require_cardid) == 1){
        	 $pagedata['isRequireId'] = true ;
        }

        return $this->page('topm/cart/checkout/edit.html', $pagedata);
    }

    /**
     * @brief 购物车结算页
     *
     * @return
     */
    public function saveAddress()
    {
        $userId = userAuth::id();
        $postData = input::get();

        $postData['area'] = rtrim(input::get()['area'][0],',');

        $postData['user_id'] = $userId;
        $area = app::get('topm')->rpcCall('logistics.area',array('area'=>$postData['area']));

        if($area)
        {
            $areaId =  str_replace(",","/", $postData['area']);
            $postData['area'] = $area . ':' . $areaId;
        }
        else
        {
            $msg = app::get('topm')->_('地区不存在!');
            return $this->splash('error',null,$msg);
        }

        $postData['card_id']=trim($postData['card_id']);
        $postData['name']=trim($postData['name']);

        //身份证实名认证 start $postData  17/01/13
        if(!empty($postData['card_id'])){
            if($postData['card_id'] && !$this->check_identity($postData['card_id']))
            {
                $msg = app::get('topm')->_('身份证格式不正确!');
                return $this->splash('error',null,$msg);
            }

            //判断数据库中是否有身份证信息
            $iscard=app::get('topm')->rpcCall('user.card.index',$postData);//返回turn false
            if(!$iscard){
                $str_r='';
                //认证
                $code=$this->real_name_auth($postData['name'],$postData['card_id']);
                if($code!=1){
                    if($code==2 || $code==3){
                        $str_r='姓名与身份证号不一致，请重新输入！';
                        $msg = app::get('topm')->_($str_r);
                        return $this->splash('error',null,$msg);
                    }elseif($code==11 || $code==12 || $code==13 || $code==14){
                        $str_r="身份证查询失败!错误码：$code";
                        $msg = app::get('topm')->_($str_r);
                        return $this->splash('error',null,$msg);
                    }
                }
                //认证通过则入库
                $codec=app::get('topm')->rpcCall('user.card.create',$postData);
                if(!$codec){
                    $str_r='身份证添加失败!';
                    $msg = app::get('topm')->_($str_r);
                    return $this->splash('error',null,$msg);
                }
            }elseif($iscard==2){
                $str_r='姓名与身份证号不一致，请重新输入！';
                $msg = app::get('topm')->_($str_r);
                return $this->splash('error',null,$msg);

            }
        }
       // var_dump($postData);
        //身份实名认证 end 17/07/13
        try
        {
            $postData['zip'] = intval($postData['zip']);
            app::get('topm')->rpcCall('user.address.add',$postData);//地址添加入库
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();

            return $this->splash('error',null,$msg);
        }
        $url = url::action('topm_ctl_cart@getAddrList');//就是一个url
        return $this->splash('success',$url,$msg);
    }

    public function delAddr()
    {
        $postData = array(
            'addr_id' =>input::get('addr_id'),
            'user_id' => userAuth::id(),
        );

        try
        {
            app::get('topm')->rpcCall('user.address.del',$postData);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();

            return $this->splash('error',null,$msg);
        }
        $url = url::action('topm_ctl_cart@getAddrList', array('ifedit'=>true));
        $msg = app::get('topm')->_('删除成功');
        return $this->splash('success',$url,$msg);

    }

    public function getCoupons($shop_id)
    {
        // 默认取100个优惠券，用作一页显示，一般达不到这个数量一个店铺
        $params = array(
            'page_no' => 0,
            'page_size' => 100,
            'fields' => '*',
            'user_id' => userAuth::id(),
            'shop_id' => intval($shop_id),
            'is_valid' => 1,
            'platform' => 'wap',
        );
        $couponListData = app::get('topm')->rpcCall('user.coupon.list', $params, 'buyer');
        $couponList = $couponListData['coupons'];

        return $couponList;
    }

	/**
	 * 在订单提交页面里选择当前的优惠券信息，
	 * 并把优惠券信息添加到Session中，在订单结算时，及时把优惠金额给抵扣
	 */
    public function useCoupon()
    {
        try
        {
            $mode = input::get('mode');
            $buyMode = $mode ? $mode :'cart';
            $apiParams = array(
                'coupon_code' => input::get('coupon_code'),
                'mode' => $buyMode,
                'platform' => 'wap',
            );
            if( app::get('topm')->rpcCall('promotion.coupon.use', $apiParams,'buyer') )
            {
                $msg = app::get('topm')->_('使用优惠券成功！');
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
            if( app::get('topm')->rpcCall('trade.cart.cartCouponCancel', $apiParams,'buyer') )
            {
                $msg = app::get('topm')->_('取消优惠券成功！');
                return $this->splash('success', null, $msg, true);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
    }

    public function getPayTypeList()
    {
        $data = input::get('selected');
        $pagedata['payType'] = array(
            'pay_type' => $data,
            'name' => $this->payType[$data],
        );
        $pagedata['addr_id'] = input::get('addr_id');
        $pagedata['isSelfShop'] = input::get('s');
        $pagedata['mode'] = input::get('mode');
        $pagedata['ifOpenOffline'] = app::get('ectools')->getConf('ectools.payment.offline.open');
        return $this->page('topm/cart/checkout/paylist.html', $pagedata);
    }

    public  function __getDtyList($shop_ids,$isSelfShop=null,$zitiData,$shop_cartCount)
    {
    	$shop_cartCount[0]['tax']=1;//默认取出所有的快递方式展示
        $tmpParams = array(
	        'shop_cart' =>$shop_cartCount,//lcd 2016/4/5  传值区分手机的业务模式和区域
		    'port' => 'wap', //lcd 2016/4/5  传值区分手机的业务模式和区域
            'shop_id' => implode(',',$shop_ids),
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $dtytmpls = app::get('topm')->rpcCall('logistics.dlytmpl.get.list',$tmpParams,'buyer');
        $dtytmplsBykey = array();
        foreach ($dtytmpls['data'] as $k => $tdy) {
            $dtytmplsBykey[$tdy['shop_id']][] = $tdy;
        }

        $ifOpenZiti = app::get('syslogistics')->getConf('syslogistics.ziti.open');
        if( $isSelfShop )
        {
            foreach($isSelfShop as $shopid)
            {
                if(!$dtytmplsBykey[$shopid])
                {
                    $dtytmplsBykey[$shopid][] = array(
                        'template_id' => -1,
                        'name' => '--选择配送方式--',
                    );
                }

                if( $zitiData && $ifOpenZiti == 'true' )
                {
                    $dtytmplsBykey[$shopid][] = array(
                        'template_id' => 0,
                        'name' => '上门自提',
                    );
                }
            }
        }
        return $dtytmplsBykey;
    }

public function buycard()
{     $postData = input::get();
	 $params['user_id'] = userAuth::id();

       if(!$this->check_identity($postData['buycard']))
        {
       return 1;
        }

         $objMdlSysuser = app::get('sysuser')->model('user');

    //身份证实名认证 17/07/13
    //获取字体人姓名
    $gname=$objMdlSysuser->getList('username',$params);
    if($gname[0]['username']){
        $arr=[];
        $arr['user_id']=$params['user_id'];
        $arr['name']=$gname[0]['username'];
        $arr['card_id']=$postData['buycard'];
            //判断数据库中是否有身份证信息
            $iscard=app::get('topm')->rpcCall('user.card.index',$arr);//返回turn false
            if(!$iscard){
                $str_r='';
                //认证
                $code=$this->real_name_auth($arr['name'],$arr['card_id']);
                if($code!=1){
                    return 1;
                }
                //认证通过则入库
                $codec=app::get('topm')->rpcCall('user.card.create',$arr);
                if(!$codec){
                   return 1;
                }
            }elseif($iscard==2){
                return 1;
            }
    }else{
        return 1;
    }
    //身份实名认证

    /*获取购买人身份证号码*/
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

//身份实名认证
    function real_name_auth($name,$card){
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



}

