<?php

class systrade_data_trade_create {

    /**
     * 生成的主订单号集合,用于新家订单后返回
     */
    private $tids = [];

    /**
     *  返回当前时间，用于新建订单，保证存储的时间一致
     */
    private $nowtime = '';

    /**
     * 新建订单的所有cart_id 集合，用于新建订单后删除购物车数据
     */
    protected $cartIds = [];

    /**
     * 当前订单使用的店铺优惠券 用于记录使用店铺优惠券日志
     */
    protected $cartUseCoupon = [];

    /**
     * 当前订单优惠的信息 用于记录优惠日志 事件中执行
     */
    protected $cartPromotion = [];


   /**
     * 构造方法
     * @param object app
     */
    public function __construct($app)
    {
        $this->objMdlTrade = app::get('systrade')->model('trade');
        $this->objLibCatServiceRate = kernel::single('sysshop_data_cat');
        $this->objLibTradeTotal = kernel::single('systrade_data_trade_total');
        $this->objMdlCart = app::get('systrade')->model('cart');
		$this->objMdlCart_tax = app::get('systrade')->model('cart_tax');

        $this->userIdent = $this->objMdlCart->getUserIdentMd5();
    }

    /**
     * 订单标准数据生成
     * @params mixed - 订单数据
     * @param array cart object array
     * 		说明：订单新添加了按业务类型和仓库地址来拆单功能，所在订单中部分字段有变；
     * 		1、子订单物流发货快递，根据店铺的分，
     * 		2、子订单邮费，每个订单都加一个运费。
     * 		3、备注信息采用店铺（多个仓库标识的备注信息 ），
     * @return boolean - 成功与否， 返回多个订单号数组(mixed 订单数据) 
     * 
     */
    public function generate($tradeParams, $aCart=array() )
    {
        $db = app::get('systrade')->database();
        $db->beginTransaction();
		$login_user_Id = userAuth::id();
		$address_idnum =  intval($aCart["addr_id"]);
        try
        {
            //格式化订单数据，不包含订单优惠,———— 9月22日，子订单新加了相关金额数据，运费计算的数据 。 by XCH
            $tradeData = $this->_chgdata($tradeParams, $aCart);
             
             //debug throw new \LogicException(print_r($tradeData,true)); 
             
            //计算订单总价格，总运费 //返回结果中新增了order_number 和all_post_fee字段
            $priceTotalData  = $this->getTradeTotal($tradeParams, $aCart);
            
            /*
             *存储订单数据完整结构
            foreach( $tradeData as $shopId=>$row )
            {
                $tradeData[$shopId] = array_merge($priceTotalData[$shopId], $row);
            }
           
            //处理积分抵扣后的订单数据
            if($tradeParams['use_points'] )
            {
                $tradeData = $this->__pointDeductionMoney($tradeData,$tradeParams);
            }
            */
            
      		/*
      		 * 查询出来购物车里面的勾选的商品，
			$filter['user_id']=userAuth::id();
			$filter['is_checked']=1;
		    $cartdata =	$this->objMdlCart->getList("tax_sea_region,shop_id,user_id",$filter);*/
		    
		    $save_shopTax = array();            
            //保存订单数据            
            foreach($tradeData as $shopId=>$firstShopOrder )
            {  /**
            	 * 分配子订单；一个店铺一个订单格式；
            	 * 		店铺下面再循环几个仓库的子订单出来；
            	 * 		Array( 
            	 * 		1=> array( 11=>array(订单列表) ， 12=>array(订单列表)  ),
            	 * 		2=> array()
            	 * )
            	 */
            	//子订单入库
            	$staxList = array();
            	if($firstShopOrder){
            		foreach($firstShopOrder as $region_idd => $childlist){ //子订单
            			$region_idd and	$staxList[] = $region_idd; //保存店铺的业务类型+仓库ID
            			$result = $this->objMdlTrade->save($childlist , null ,true);
            		}
            		$save_shopTax[$shopId] = $staxList ? implode(",",$staxList) : ""; //把tax_ssea_region值给返回后面来处理；
            	}
            	
            	//Debug print_r($save_shopTax); $db->commit();exit;
                if(!$result ){
                    throw new \LogicException(app::get('systrade')->_('订单生成失败'));
                }else{
                	//清空购物车信息
                	$this -> __clearSucessCart($login_user_Id , $shopId ,$staxList);
					/*foreach($cartdata as $k=>$v){
						if($shopId==$v['shop_id']){
					           $data['shop_id']=$v['shop_id'];
							   $data['tax_sea_region']=$v['tax_sea_region'];
							   $data['user_id']=$v['user_id'];	
					           $cartcount=	$this->objMdlCart->count($data);//当前店铺，当前业务模式数量
					           $data['is_checked']=1;
							   $checkedcart = $this->objMdlCart->count($data);//当前店铺，当前业务模式数量，勾选数量
								if($cartcount==$checkedcart){ //全选的情况
									$tax['user_id'] =$v['user_id'];	
									$tax['tax_id'] =$v['shop_id'].$v['tax_sea_region'];	
									$obj =	$this->objMdlCart_tax->delete($tax); //删除购物车拆单的父项是否应该勾选；
								}
								$this->objMdlCart_tax->update(array('disabled_id'=>0),array('user_id'=>$v['user_id']));//取消父项目的屏蔽
							 	$this->objMdlCart->update(array('select_tax'=>0),array('user_id'=>$v['user_id']));//取消购物车的屏蔽
						}
					}*/
				}
		     } //###一个店铺订单结束
             //将已使用的优惠券更新为已使用
             $this->_couponUse($tradeData);
             $db->commit();
        }
        catch (Exception $e)
        {
            $db->rollback();
            throw $e;
        }
        $this->__unsetCartUseCoupon(); //使用优惠券
		//交易数据落订单表；trade_order 表中
        $this->createTradeEventFire($tradeData, $tradeParams);
        return $this->getTids();
    }
    
    /**
     * 清空用户下单后，购物车的信息列表；
     */
    function __clearSucessCart($user_id , $shopId , $del_searegion = false){
    	if(!$user_id || !$shopId )return false;
    	//查询出来购物车里面的勾选的商品，
    	$filter =array("user_id"=>$user_id ,"is_checked"=> 1 ) ;	//选中的产品
	    $cartdata =	$this->objMdlCart->getList("tax_sea_region,shop_id,user_id",$filter);
	    if($cartdata){ 
	    	foreach($cartdata as $k=>$v){
				if($shopId==$v['shop_id']){
					$filter_arr = array("user_id"=>$user_id ,"shop_id"=> $v['shop_id'] ,"tax_sea_region"=> $v['tax_sea_region']);
			        $cart_all_count =	$this->objMdlCart->count($filter_arr);//当前店铺，当前业务模式数量
			        $filter_arr['is_checked']=1;
					$cart_checked_count = $this->objMdlCart->count($filter_arr);//当前店铺，当前业务模式数量，勾选数量
					if($cart_checked_count >0 && $cart_all_count==$cart_checked_count){ //全选的情况
						//业务类型，+ 仓库ID，
						$tax_region = isset($v['tax_sea_region']) ? trim($v['tax_sea_region']): "";
						$del_filter = array("user_id"=>$user_id ,"tax_id"=> $v['shop_id'].$tax_region);
						$tax_region and $obj =	$this->objMdlCart_tax->delete($del_filter); 			//删除购物车拆单的父项是否应该勾选；
					}
					if($del_searegion && !empty($del_searegion)){
						$del_cart = array("user_id"=> $user_id ,"shop_id"=> $shopId, "tax_sea_region|in"=> $del_searegion);
						$this->objMdlCart->delete($del_cart); 	 //删除已经下过的订单；
					}
					$this->objMdlCart_tax->update(array('disabled_id'=>0),array('user_id'=>$user_id));	//取消父项目的屏蔽
				 	$this->objMdlCart->update(array('select_tax'=>0),array('user_id'=>$user_id));		//取消购物车的屏蔽
				}
			}
	    }
	    return false;
    }

    //触发订单创建后的事件
    public function createTradeEventFire($tradeData, $tradeParams)
    {
        foreach( $tradeData as $shopId=>$shopTradeData )
        {
            $trade[$shopId]['tid'] = $shopTradeData['tid'];
            $trade[$shopId]['shop_id'] = $shopId;
            $trade[$shopId]['payment'] = $shopTradeData['payment'];
            foreach( $shopTradeData['order'] as $k => $shopOrderData )
            {
                $trade[$shopId]['order'][$k]['shop_id'] = $shopOrderData['shop_id'];
                $trade[$shopId]['order'][$k]['tid'] = $shopOrderData['tid'];
                $trade[$shopId]['order'][$k]['oid'] = $shopOrderData['oid'];
                $trade[$shopId]['order'][$k]['user_id'] = $shopOrderData['user_id'];
                $trade[$shopId]['order'][$k]['item_id'] = $shopOrderData['item_id'];
                $trade[$shopId]['order'][$k]['sku_id'] = $shopOrderData['sku_id'];
                $trade[$shopId]['order'][$k]['num'] = $shopOrderData['num'];
                $trade[$shopId]['order'][$k]['selected_promotion'] = $shopOrderData['selected_promotion'];
                $trade[$shopId]['order'][$k]['activityDetail'] = $shopOrderData['activityDetail'];
            }
        }

        $data = [
            'user_id' => $tradeParams['user_id'],
            'user_name' => $tradeParams['user_name'],
            'trade' => $trade
        ];

        event::fire('trade.create', [$data, ['cartIds'=>$this->cartIds,'mode'=>$tradeParams['mode'],'cartPromotion'=>$this->cartPromotion] ]);
    }

    /**
     * 创建订单ID
     *
     * @param int $userId 消费者ID
     * @param bool $isTid 是否为主订单ID
     * @return $tid
     */
    protected function genId($userId, $isTid=true )
    {
        if( ! $this->genidData )
        {
            $data['tradeBaseTime'] = date('ymdHi');
            $data['tradeBaseRandNum'] = rand(0,49);//str_pad($tradeBaseRandNum,2,'0',STR_PAD_LEFT);
            $data['tradeModUserId'] = str_pad($userId%10000,4,'0',STR_PAD_LEFT);

            $this->genidData = $data;
        }

        $id = $this->genidData['tradeBaseTime'].str_pad(++$this->genidData['tradeBaseRandNum'],2,'0',STR_PAD_LEFT).$this->genidData['tradeModUserId'];
        if( $isTid ) $this->tids[] = $id;

        return $id;
    }

    /**
     * 返回创建的主订单号集合
     */
    public function getTids()
    {
        return $this->tids;
    }

    /**
     * 返回当前时间
     */
    public function getTime()
    {
        return $this->nowTime ? $this->nowTime : time();
    }

    /**
     * 返回订单公用的数据结构
     */
    private function __commonTradeData($tradeParams)
    {
        return [
            'user_id'           => $tradeParams['user_id'],
            'user_name'         => $tradeParams['user_name'],
            'created_time'      => $this->getTime(),
            'modified_time'     => $this->getTime(),
            'ip'                => request::server('REMOTE_ADDR'),
            'title'             => app::get('systrade')->_('订单明细介绍'),
            'pay_type'          => $tradeParams['payment_type'] ? $tradeParams['payment_type'] : 'online',
            'need_invoice'      => ($tradeParams['invoice']['need_invoice'] ? 1 : 0),
            'trade_from'        => $tradeParams['source_from'],
            'invoice_name'      => $tradeParams['invoice']['invoice_title'],
            'invoice_main'      => strip_tags($tradeParams['invoice']['invoice_content']),
            'invoice_type'      => $tradeParams['invoice']['invoice_type'],
            'receiver_name'     => $tradeParams['delivery']['receiver_name'],
            'receiver_address'  => $tradeParams['delivery']['receiver_address'],
            'receiver_zip'      => $tradeParams['delivery']['receiver_zip'],
            'receiver_tel'      => $tradeParams['delivery']['receiver_tel'],
            'receiver_mobile'   => $tradeParams['delivery']['receiver_mobile'],
            'receiver_state'    => $tradeParams['delivery']['receiver_state'],
            'receiver_city'     => $tradeParams['delivery']['receiver_city'],
            'receiver_district' => $tradeParams['delivery']['receiver_district'],
            'buyer_area'        => $tradeParams['delivery']['buyer_area'],
			'card_id'              => $tradeParams['delivery']['card_id'],  //2016/3/24  lcd身份证号码
        ];
    }

    /**
     * 返回子订单结构
     *
     * @param array $tradeParams
     * @param array $cartItem
     * @param array $shopId
     */
    private function __orderItemData($tradeParams, $cartItem, $shopId)
    {
        $subStock = ($tradeParams['payment_type'] == 'online') ? $cartItem['sub_stock'] : '1';
        $oid = $this->genId($tradeParams['user_id'], false);

        $orderData = [
            'oid'              => $oid,
            'tid'              => end($this->getTids()),
            'shop_id'          => $shopId,
            'user_id'          => $tradeParams['user_id'],
            'item_id'          => $cartItem['item_id'],
            'sku_id'           => $cartItem['sku_id'],
            'cat_id'           => $cartItem['cat_id'],
            'bn'               => $cartItem['bn'],
			'ordtax_rate_price'      => $cartItem['price']['tax_rate_price'],  //消费税2016/3/27
		    'ordreg_rate_price'      => $cartItem['price']['reg_rate_price'],  //增值税2016/3/27
            'price'            => $cartItem['price']['price'],
            'num'              => $cartItem['quantity'],
            'payment'          => ecmath::number_minus(array($cartItem['price']['total_price'],$cartItem['price']['discount_price'])),
            'total_fee'        => $cartItem['price']['total_price'],
            'part_mjz_discount'=> $cartItem['price']['discount_price'],
            'total_weight'     => $cartItem['weight'],
            'pic_path'         => $cartItem['image_default_id'],
            'sub_stock'        => $subStock,
            'cat_service_rate' => $this->objLibCatServiceRate->getCatServiceRate(array('shop_id'=>$shopId, 'cat_id'=>$cartItem['cat_id'])),
            'sendnum'          => 0,
            'created_time'     => $this->getTime(),
            'modified_time'    => $this->getTime(),
            'status'           => $this->__tradeStatus($tradeParams['payment_type']),
            'title'            => $cartItem['title'],
            'spec_nature_info' => $cartItem['spec_info'],
            'order_from'       => $tradeParams['source_from'],
            'selected_promotion' => $cartItem['selected_promotion'],
        ];

        if( $cartItem['promotion_type'] == 'activity' )
        {
            $orderData['promotion_type'] = $cartItem['promotion_type'];
            $orderData['activityDetail'] = $cartItem['activityDetail'];
        }
        return $orderData;
    }

    private function __orderPackageData($tradeParams, $cartItem, $shopId, $opacval)
    {
        $subStock = ($tradeParams['payment_type'] == 'online') ? $opacval['sub_stock'] : '1';
        $oid = $this->genId($tradeParams['user_id'], false);

        $orderInfo = array(
            'oid'              => $oid,
            'tid'              => end($this->getTids()),
            'shop_id'          => $shopId,
            'user_id'          => $tradeParams['user_id'],
            'item_id'          => $opacval['item_id'],
            'sku_id'           => $opacval['sku_id'],
            'cat_id'           => $opacval['cat_id'],
            'bn'               => $opacval['bn'],
            'price'            => $opacval['price']['price'],
            'num'              => $cartItem['quantity'],
            'payment'          => ecmath::number_multiple( array( $opacval['price']['price'], $cartItem['quantity'] ) ),
            'total_fee'        => ecmath::number_multiple( array( $opacval['price']['price'], $cartItem['quantity'] ) ),
            'part_mjz_discount'=> ecmath::number_multiple( array( $opacval['price']['discount_price'], $cartItem['quantity'] ) ),
            'total_weight'     => ecmath::number_multiple( array( $opacval['weight'], $cartItem['quantity'] ) ),
            'pic_path'         => $opacval['image_default_id'],
            'sub_stock'        => $subStock,
            'cat_service_rate' => $this->objLibCatServiceRate->getCatServiceRate(array('shop_id'=>$shopId, 'cat_id'=>$opacval['cat_id'])),
            'sendnum'          => 0,
            'created_time'     => $this->getTime(),
            'modified_time'    => $this->getTime(),
            'status'           => $this->__tradeStatus($tradeParams['payment_type']),
            'title'            => $opacval['title'],
            'spec_nature_info' => $opacval['spec_info'],
            'order_from'       => $tradeParams['source_from'],
            'selected_promotion' => $cartItem['selected_promotion'],
        );

        return $orderInfo;
    }

    /**
     * 定义创建订单的默认的订单状态
     *
     * @param string $paymentType offline 线下支付 | online 在线支付
     * @return string
     */
    private function __tradeStatus( $paymentType )
    {
        //如果订单的支付方式为线下支付，订单状态默认为等待发货，否则为等待支付
        return ($paymentType == "offline") ?  "WAIT_SELLER_SEND_GOODS" : "WAIT_BUYER_PAY";
    }

    /**
     * 计算订单价格，运费 基本的优惠已经在获取的购物车数据中计算好了
     *
     * @param array $tradeParams 创建订单参数
     * @param array $aCart 需要创建订单获取的购物车数据
     * 
     * 	说明：订单拆单；把数据给组装好，（订单总额＝ 多个订单金额，+ 多个订单的邮费）
     *  返回的应付款，一定是计算好的订单金额；
     */
    public function getTradeTotal($tradeParams, $aCart)
    {
        foreach ($aCart['resultCartData'] as $shopId => $shopCartData )
        {
            //计算订单总金额
            $totalParams = array(
				'discount_fee' => $shopCartData['cartCount']['total_discount'],
                'total_fee' => $shopCartData['cartCount']['total_fee'],
                'total_weight' => $shopCartData['cartCount']['total_weight'],
                'shop_id' => $shopCartData['shop_id'],
                'template_id' => $tradeParams['shipping'][$shopId]['template_id'],
                'region_id' => str_replace('/', ',', $tradeParams['region_id']),
                'usedCartPromotionWeight' => $shopCartData['usedCartPromotionWeight'],
            );
            //ADD 统计子订单数目
            $all_order_number = 0;
            if(isset($shopCartData["object2"]) && !empty($shopCartData["object2"])){
            	foreach ($shopCartData["object2"] as $k => $vald){
            		if(isset($vald) && isset($vald["sea_region"])){ //子订单有效；
            			$all_order_number++ ;
            		}
            	}
            }
            //多加一个参数，当前店铺的订单数量统计；
            $totalParams["order_number"] = $all_order_number>0 ? $all_order_number :1 ;	//默认为一个订单；
            
			//payment 是商品实际要付的金额，不包邮费
            $totalInfo = $this->objLibTradeTotal->trade_total_method($totalParams);

			//添加税率，计算当前单个店铺的税率，如果存在多个店铺付款，那么就是完税的情况，不需要算税，那么可以不考虑。2016/3/24雷成德
			if($shopCartData['cartCount']['tax']==1){
				$shopCartData['cartCount']['tax_rate_price']=0; //如果是完税的情况，消费税费为0
				$shopCartData['cartCount']['reg_rate_price']=0; //如果是完税的情况，增值税费为0
				$priceTotalData[$shopId]['payment'] = $totalInfo['payment'];
			}else{
			    if($shopCartData['cartCount']['total_fee']>2000)
                {
                    $msg = app::get('topc')->_("温馨提示：海关要求商品价格不能大于2000元");  
                    return $this->splash('error', '', $msg, true);
                }
				$tax_rate_price=0;
				$reg_rate_price=0;
				foreach ( $shopCartData['object'] as $kk=>$vv)
				{
					//计算消费税
					$tax=(($vv['price']['price']*$vv['quantity'])+($vv['price']['price']*$vv['quantity']/$shopCartData['cartCount']['total_fee']*$totalInfo['post_fee']))/(1-$vv['tax_rate'])*$vv['tax_rate']*0.7;
					$tax_rate_price= $tax_rate_price+$tax;
					//计算增值税
					$reg=(($vv['price']['price']*$vv['quantity'])+$vv['price']['price']*$vv['quantity']/$shopCartData['cartCount']['total_fee']*$totalInfo['post_fee']+$tax)*$vv['reg_rate']*0.7;
					$reg_rate_price= $reg_rate_price+$reg;
				}
				//增加 +消费税+增值税
		    	$priceTotalData[$shopId]['payment'] = $totalInfo['payment']+$tax_rate_price+$reg_rate_price;
			}
			$priceTotalData[$shopId]['tax_rate_price'] = $shopCartData['cartCount']['tax_rate_price'];//当前店铺的消费税，只能是一家店铺
			$priceTotalData[$shopId]['reg_rate_price'] = $shopCartData['cartCount']['reg_rate_price'];//当前店铺的增值税
			$priceTotalData[$shopId]['tax'] = $shopCartData['cartCount']['tax'];//当前店铺的业务模式
			$priceTotalData[$shopId]['sea_region'] = $shopCartData['cartCount']['sea_region'];//保税和直邮的区域，完税的不准，完税不要使用暂时不考虑2016/3/25雷成德
          	//$priceTotalData[$shopId]['payment'] = $totalInfo['payment'];
            $priceTotalData[$shopId]['total_fee'] = $totalInfo['total_fee'];
            $priceTotalData[$shopId]['discount_fee'] = $totalInfo['discount_fee'];
            $priceTotalData[$shopId]['obtain_point_fee'] = $totalInfo['obtain_point_fee'];
            $priceTotalData[$shopId]['post_fee'] = $totalInfo['post_fee'];
            $priceTotalData[$shopId]["order_number"] = $all_order_number>0 ? $all_order_number :1 ;	//默认为一个订单；
            $priceTotalData[$shopId]['all_post_fee'] = $totalInfo['all_post_fee'];
        }
        return $priceTotalData;
    }

    /**
     * 返回订单保存基本数据
     *
     * @params array $tradeParams
     * @params array $aCart
     * @return array $orderData
     */
    private function _chgdata( $tradeParams, $aCart=array() )
    {
        //创建店铺主订单   公用的数据，如收货人信息,创建时间，IP地址，发票信息；
        $commonTradeData = $this->__commonTradeData($tradeParams);
        $address_id  = isset($tradeParams["delivery"]["addr_id"]) ? intval($tradeParams["delivery"]["addr_id"]): 0;
        //组装分订单数据
        foreach ($aCart['resultCartData'] as $shopId => $shopCartData )
        {
            $this->shopIds[] = $shopId;
            $markArr = isset($tradeParams['trade_memo'])? $tradeParams['trade_memo'][$shopId] : false;//处理订单的备注信息(传入是数组；)
            //获取多个订单号子订单号，用于支付 订单 ；
            if(isset($shopCartData["object2"]) && !empty($shopCartData["object2"])){
            	foreach($shopCartData["object2"] as $shop_region => $order){
            		//循环下下面所有数据 如果是数组， 并有cart_id 值是是一个有效商品
            		$youxiao_o_num = 0; //当前订单商品数量
            		$youxiao_o_weight = 0; //当前订单商品数量
            		$youxiao_o_totalfee = 0; //当前单总额
            		$chailds = array();
            		//新订单号生成
	            	$tid = $this->genId($tradeParams['user_id']);
            		if($shop_region){ //店铺ID + 仓库ID， 组合键
            			if(isset($markArr) && !empty($markArr) && is_string($markArr)){
			            	$order_mark = trim($markArr); 
			            }else{
			            	$order_mark  = isset($markArr["{$shop_region}"] ) ? $markArr["{$shop_region}"]  : ""; //单个订单备注信息	
			            }     
            			//子商品列表
            			foreach($order as $k => $oo ){
	            			if(isset($oo) && is_array($oo) && isset($oo["item_id"]) &&  intval($oo["item_id"]) >=0){ //算作有效订单一个
	            				$youxiao_o_num ++;
	            				isset($oo["weight"]) and $youxiao_o_weight += floatval($oo["weight"]) ;//统计总重量；
	            				//price值
	            				$priceObj = $oo["price"];
	            				if(!$priceObj){  //价格异常
	            					throw new \LogicException(app::get('systrade')->_('获取订单信息异常'));
	            				}else{			//统计总价
	            					$discount_price =  isset($priceObj["discount_price"]) ? floatval($priceObj["discount_price"]) : 0; //折扣
	            					//单价 ,有折扣再另说
	            					$current_price = ($discount_price>0 && $discount_price<=1) ? $discount_price *  floatval($priceObj["price"]) : floatval($priceObj["price"]); 
	            					//总价
		            				intval($oo["quantity"])>0 and $youxiao_o_totalfee += intval($oo["quantity"]) * $current_price;
	            				}
	            				//获取子订单的详细信息
                    			$orderDetail = $this->__orderItemData($tradeParams, $oo ,$shopId); 
                    			//写入子订单信息
	            				$chailds[] = $orderDetail;
	            				//下单减库存
                    			$this->__minusStore($orderDetail); // 传入订单的详细信息
	            			}
	            		}
	            		//组合订单基础数据(订单号，物流模板,商品数量)
			            $shopTradeData = array('shop_id'=> $shopId,'tid'=> $tid,'itemnum'=> $youxiao_o_num , "area_id" => $address_id,
			            	'sea_region' => isset($order["sea_region"]) ? intval($order["sea_region"]) : 0, 
							'tax'		 => isset($order["tax"]) ? $order["tax"] : 1,
			                'status'     => $this->__tradeStatus($commonTradeData['pay_type']),
			                'trade_memo' => isset($order_mark) ? strip_tags($order_mark): "",	//备注信息，分子订单，不放公共数据；
			                'dlytmpl_id' => $tradeParams['shipping'][$shopId]['template_id'],  //物流模板 共用店铺的
			                'ziti_addr'  => $tradeParams['ziti'][$shopId]['ziti_addr'], //自提地址 共用店铺的
			                'total_weight'=>$youxiao_o_weight,
			                'order'		 =>$chailds 		//订单商品信息；
			            );			            
			            //计算拆单之后 要实际付款===计算订单总金额
			            $totalParams = array(
							'discount_fee' => 0,
			                'total_fee' => $youxiao_o_totalfee  , // 当前订单总价$shopCartData['cartCount']['total_fee'],
			                'total_weight' => $youxiao_o_weight,
			                'shop_id' => $shopId,
			                'template_id' => isset($shopTradeData['dlytmpl_id']) ? $shopTradeData['dlytmpl_id'] : 0,	//公用的运费模板
			                'region_id' => str_replace('/', ',', $tradeParams['region_id']),
			                'usedCartPromotionWeight' => $shopCartData['usedCartPromotionWeight'],
			            );
						//payment == 是商品实际要付的金额，不包邮费
			            $totalInfo = $this->objLibTradeTotal->trade_total_method($totalParams);
			             
			            $shopTrade_AllData = $shopTradeData ;//默认只有详细信息； 
			            //把订单费用添加进去；
			            if($totalInfo){
			            	$shopTrade_AllData = array_merge($totalInfo, $shopTradeData);
			            }
			            //合并基础数据 ； 主订单基本数据 不包含订单金额数据			            
           				$orderData[$shopId][$shop_region] = array_merge($commonTradeData, $shopTrade_AllData);  //存储方式，按仓库来存值
            		}
            	}
            }
            
            //当前订单使用的店铺优惠券 用于记录使用店铺优惠券日志（ 有问题，后续再修改改）
            if( $shopCartData['cartCount']['total_coupon_discount'] > 0 && $_SESSION['cart_use_coupon'][$this->userIdent][$shopId] )
            {
                $this->cartUseCoupon[$shopId] = $_SESSION['cart_use_coupon'][$this->userIdent][$shopId];
            }
            /**
             * //主订单基本数据 不包含订单金额数据
            $orderData[$shopId] = array_merge($commonTradeData, $shopTradeData);
            //购物车优惠信息
            $this->__setPromotionParams($shopId, $shopCartData);

            // 子订单
            foreach($shopCartData['object'] as $k =>$cartItem)
            {
                if( $cartItem['obj_type'] == 'item' )
                {
                	//获取子订单的详细信息
                    $shopOrderData = $this->__orderItemData($tradeParams, $cartItem,$shopId);
                    
                    //子订单备注信息补充；
                    if(isset($cartItem["tax_sea_region"]) && $tax_sregid = trim($cartItem["tax_sea_region"])) {
                    	$shopOrderData["trade_memo"] = isset($markArr[$tax_sregid]) ? trim($markArr[$tax_sregid])  : ""; //把关联订单备注信息给填到订单上去；
                    }
                    //下单减库存
                    $this->__minusStore($shopOrderData);
                    $orderData[$shopId]['order'][] = $shopOrderData;
                }

                if( $cartItem['obj_type'] == 'package' )
                {
                    foreach($cartItem['skuList'] as $opacval)
                    {
                        $shopOrderData = $this->__orderPackageData($tradeParams, $cartItem, $shopId, $opacval);
                        $this->__minusStore($shopOrderData);
                        $orderData[$shopId]['order'][] = $shopOrderData;
                    }
                }
                $this->cartIds[] = $cartItem['cart_id'];
            } */
        }
        return $orderData;
    }

    private function __setPromotionParams($shopId, $shopCartData)
    {
        // 用于生成促销日志表
        $this->cartPromotion[$shopId]['basicPromotionListInfo'] = $shopCartData['basicPromotionListInfo'];
        // 本次购物使用的促销id
        $this->cartPromotion[$shopId]['usedCartPromotion'] = $shopCartData['usedCartPromotion'];

        return true;
    }

    //删除使用的优惠券
    private function __unsetCartUseCoupon()
    {
        foreach( $this->shopIds as $shopId )
        {
            unset($_SESSION['cart_use_coupon'][$this->userIdent][$shopId]);
        }

        return true;
    }

    /**
     * 订单创建成功后将已使用的优惠券更新为已使用
     *
     * @param array $tradeData
     */
    protected function _couponUse($tradeData)
    {
        foreach( (array)$this->cartUseCoupon as $shopId => $couponCode  )
        {
            $data = array(
                'tid' => $tradeData[$shopId]['tid'],
                'coupon_code' => $couponCode,
            );

            if( !app::get('systrade')->rpcCall('user.coupon.useLog', $data) )
            {
                throw new \LogicException(app::get('systrade')->_('优惠券使用失败'));
            }
        }

        return true;
    }

    /**
     * 下单减库存; 支付减库存,下单冻结库存
     *
     * @param $orderData 商品的子订单数据
     */
    private function __minusStore($orderData)
    {
        // 处理sku订单冻结
        $params = array(
            'item_id' => $orderData['item_id'],
            'sku_id' => $orderData['sku_id'],
            'quantity' => intval($orderData['num']) >0 ? intval($orderData["num"]):intval($orderData["quantity"]),
            'sub_stock' => $orderData['sub_stock'],
            'status' => 'on',
        );
        $isMinus = app::get('systrade')->rpcCall('item.store.minus',$params);
        if( ! $isMinus )
        {
            throw new \LogicException(app::get('systrade')->_('冻结库存失败'));
        }

        return true;
    }

    /**
     * @brief 下单时使用积分抵钱
     *
     * @param $tradeData
     * @param $postdata
     *
     * @return
     */
    private function __pointDeductionMoney($tradeData,$postdata)
    {
        //积分抵扣不计算运费
        $usePoints = $postdata['use_points'];
        $payment = array_column($tradeData,'payment');
        $totalPayment = array_sum($payment);
        $postFee = array_column($tradeData,'post_fee');
        $totalPostFee = array_sum($postFee);
        foreach($tradeData as $key=>$value)
        {
            $trade_money = $value['payment']-$value['post_fee'];
            $params = array(
                'user_id' => $value['user_id'],
                'use_point' => $usePoints,
                'total_money' => $totalPayment-$totalPostFee,
                'trade_money' => $trade_money,
            );
            $result = app::get('systrade')->rpcCall('point.deduction.num',$params);
            if(!$result) continue;

            foreach($value['order'] as $k=>$val)
            {
                $paramsOrder = array(
                    'user_id' => $value['user_id'],
                    'use_point' => $result['point'],
                    'total_money' => $trade_money,
                    'trade_money' => $val['payment'],
                );
                $resultOrder = app::get('systrade')->rpcCall('point.deduction.num',$paramsOrder);
                $tradeData[$key]['order'][$k]['consume_point_fee'] = $resultOrder['point'];
                $tradeData[$key]['order'][$k]['points_fee'] = $resultOrder['money'];
            }

            $tradeData[$key]['consume_point_fee'] = $result['point'];
            $tradeData[$key]['points_fee'] = $result['money'];
            $tradeData[$key]['payment'] = ecmath::number_minus(array($value['payment'],$result['money']));
        }
        $this->__consumePoint($tradeData);
        return $tradeData;
    }

    private function __consumePoint($tradeData)
    {

        foreach($tradeData as $key=>$value)
        {
            if(count($value['order']) > 1)
            {
                $behavior = "多个商品：".array_shift($value['order'])['title']."等...; 订单号：".$value['tid'];
            }
            else
            {
                $behavior = array_shift($value['order'])['title']."; 订单号：".$value['tid'];
            }

            // 积分抵扣下单扣减积分
            $updateParams = array(
                'user_id' => $value['user_id'],
                'type' => 'consume',
                'num' => $value['consume_point_fee'],
                'behavior' => $behavior,
                'remark' => app::get('systrade')->_('交易扣减'),
            );
            $result = app::get('systrade')->rpcCall('user.updateUserPoint',$updateParams);
        }
    }

}

