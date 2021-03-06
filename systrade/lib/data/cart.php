<?php

class systrade_data_cart {

    public $objects = array();

	/**
	 * user Id
	 *
	 * @var int
	 */
    protected $userId = null;

    public function __construct($userId)
    {
        if (!$userId) throw \InvalidArgumentException('user id cannot null.');
        $this->userId = $userId;
        $this->objMdlCart = app::get('systrade')->model('cart');
        $this->objLibItemInfo = kernel::single('sysitem_item_info');
		$this->objTax =	$objTax = app::get('sysshop')->model('tax');
		 $this->objRegion =$objRegion= app::get('ectools')->model('region');
		 $this->objCarttax =$objRegion= app::get('systrade')->model('cart_tax');
        $this->__instance();
    }

    //初始化加入购物车的处理类
    private function __instance()
    {
        if( !$this->objects )
        {
            $objs = kernel::servicelist('cart_object_apps');
            foreach( $objs as $obj )
            {
                //购物车类型===
                $type = $obj->getObjType();
                //排序
                $index = $obj->getCheckSort();
                if( isset($tmp[$index]) ) $index++;

                $tmpIndex[$index] = $type;
                $tmpObjects[$type] = $obj;
            }
            ksort($tmpIndex);
            foreach( $tmpIndex as $type )
            {
                $this->objects[$type] = $tmpObjects[$type];
            }
        }
        return $this->objects;
    }

    /**
     * @brief 检查是否可以购买
     *
     * @param array $params 加入购物车参数
     * @param array $itemData 加入购物车的基本商品数据
     * @param array $skuData 加入购物车的基本SKU数据
     *
     * @return bool
     */
    private function __check($checkParams, $itemData, $skuData)
    {
        foreach( $this->objects as $obj )
        {
            $obj->check($checkParams, $itemData, $skuData);
        }
    }

    /**
     * 检查加入购物车的商品是否有效
     *
     * @param array $itemsData 加入购物车的基本商品数据集合
     * @param array $skuData 加入购物车的基本SKU数据集合
     *
     * @return bool
     */
    private function __checkItemValid($itemsData, $skuData)
    {
        if( empty($itemsData) || empty($skuData) ) return false;

        //违规商品
        if( $itemsData['violation'] ) return false;

        //未启商品
        if( $itemsData['disabled'] ) return false;

        //未上架商品
        if($itemsData['approve_status'] == 'instock' ) return false;

        //已删除SKU
        if( $skuData['status'] == 'delete' )
        {
            return false;
        }

        if( $skuData['store'] <= 0 )
        {
            return false;
        }

        return true;
    }

    /**
     * 如果加入购物车的商品在购物车中已存在，则进行合并
     *
     * @param $cartBasicData 根据加入购物车的的参数，获取到的基本的购物车数据
     * @param $params  加入购物车的的参数
     *
     * @return array
     */
    private function __mergeAddCartData($cartBasicData, $params)
    {
        //购买方式分为加入购物车模式，和立即购买模式
        //加入购物车模式需要判断购物车是否已经有该次购买的商品
        if( $cartBasicData && (empty($params['mode']) || $params['mode'] == 'cart') )
        {
            //总购买数量
            $params['totalQuantity'] = $params['quantity'];
            if( $params['obj_type'] && $cartBasicData['obj_type'] == $cartBasicData['obj_type'] )
            {
                $params['totalQuantity'] += intval($cartBasicData['quantity']);
                $params['cart_id'] = $cartBasicData['cart_id'];
            }
        }
        else
        {
            $params['totalQuantity'] = intval($params['quantity']);
        }

        return $params;
    }

    /**
     * @brief 加入购物车
     *
     * @param array $params 加入购物车参数
     *
     * @return bool
     */
    public function addCart($params)
    {
        if (!$this->objects[$params['obj_type']]){
            $msg = app::get('systrade')->_('商品类型错误！');
            throw new \LogicException($msg);
        }

        // 不同的cart  objects查询参数各自定义
        $filter = $this->objects[$params['obj_type']]->basicFilter($params);
        $cartBasicData = $this->getBasicCart($filter);
        $selectp = 0;
        /* 查询商品的促销优惠信息；*/
        if(isset($params["sku_id"]) && intval($params["sku_id"]) >0){
	        $obj_item_promotion = kernel::single("sysitem_data_item");
	        $acts = $obj_item_promotion->itemPromotionFirst($params ,$selectp);
        }
        // 合并购物车，再判断
        $mergeParams = $this->__mergeAddCartData($cartBasicData[0], $params);
        //用户第一次添加购物车时，需要对商品进行搜索活动，默认添加一个初始的活动ID＝promotionid
        $selectp >0 and $mergeParams["selected_promotion"] = $selectp ; //默认可选的促销活动；
        
        //print_r($cartBasicData);exit;

        // 进行各自的特殊校验
        $this->objects[$params['obj_type']]->checkObject($mergeParams, $cartBasicData);
        // 判断合并后的购物车信息
        $this->__check($mergeParams);
        
        // 格式化加入购物车数据
        $data = $this->objects[$params['obj_type']]->__preAddCartData($mergeParams, $this->userId, $cartBasicData);

        if( $params['mode'] == 'fastbuy' )
        {
            return $this->fastBuyStore($data);
        }
        $db = app::get('systrade')->database();
        $db->beginTransaction();
        try
        {
            $result = $this->objMdlCart->save($data);
            // $this->objects[$params['obj_type']]->__afterSaveCart($data);
            $db->commit();
        }
        catch(\Exception $e)
        {
            $db->rollback();
            throw $e;
        }

        return $result ? $data : false;
    }

    public function countCart()
    {
        $filter['user_ident'] = $this->objMdlCart->getUserIdentMd5($this->userId);
        $cartNumber['variety'] = $this->objMdlCart->count($filter);
        $number = 0;
        $data = $this->objMdlCart->getList('quantity',$filter);
        foreach($data as $val)
        {
            $number += $val['quantity'];
        }
        $cartNumber['number'] = $number;
        return $cartNumber;
    }

    public function setCartCookieNum($cartNumber)
    {
        $cookie_path = kernel::base_url().'/';
        $expire = time()+315360000;
        setcookie('CARTNUMBER',$cartNumber,$expire,$cookie_path);
        return true;
    }

    /**
     * @brief 立即购买流程存储购物数据
     *
     * @param array $params
     *
     * @return bool
     */
    public function fastBuyStore($params)
    {
        kernel::single('base_session')->start();
        $userIdent = $this->objMdlCart->getUserIdentMd5($this->userId);
        $params['is_checked'] = '1';
        $_SESSION['cart_objects_fastbuy'][$userIdent] = $params;
        kernel::single('base_session')->close();
        return true;
    }

    public function fastBuyFetch()
    {
        $userIdent = $this->objMdlCart->getUserIdentMd5($this->userId);
        return $_SESSION['cart_objects_fastbuy'][$userIdent];
    }


    // 活动剩余购买数量
    public function restBuyNum($itemId, $userId)
    {
        // 活动，剩余购买数量
        $promotionDetail = app::get('systrade')->rpcCall('promotion.activity.item.info',array('item_id'=>$itemId, 'valid'=>1));
        if($promotionDetail['item_id'])
        {
            $objMdlPromDetail = app::get('systrade')->model('promotion_detail');
            $filter = array('promotion_id'=>$promotionDetail['activity_id'], 'promotion_type'=>'activity', 'user_id'=>$userId, 'item_id'=>$itemId);
            $oids = $objMdlPromDetail->getList('oid,item_id', $filter);
            $objMdlOrder = app::get('systrade')->model('order');
            $activityNum = 0;
            foreach($oids as $v)
            {
                $orderInfo = $objMdlOrder->getRow('status,num',array('oid'=>$v['oid']));
                if( !in_array( $orderInfo['status'], array('TRADE_CLOSED_BY_SYSTEM', 'TRADE_CLOSED') ) )
                {
                    $activityNum += $orderInfo['num'];
                }
            }
            $restActivityNum = $promotionDetail['activity_info']['buy_limit']-$activityNum;
            return array('ifactivity'=>$promotionDetail['item_id']?true:false,'restActivityNum'=>$restActivityNum);
        }
    }

    /**
     * @brief 更新购物车信息
     *
     * @param array $params
     *
     * @return bool
     */
    public function updateCart($params)
    {
        if( $params['mode'] == 'fastbuy' ) return false;

        if( !$params['cart_id'] )
        {
            throw new \LogicException(app::get('systrade')->_("无效参数"));
        }

        // 进行各自的特殊校验
        $filter['cart_id'] = $params['cart_id'];
        $basicCartData = $this->getBasicCart($filter);
        $params['obj_type'] = $basicCartData['0']['obj_type'];
        if( !$basicCartData )
        {
            throw new \LogicException(app::get('systrade')->_("无效参数"));
        }
		//print_r($basicCartData);
        $this->objects[$params['obj_type']]->checkObject($params, $basicCartData);
        //检查商品是否能加入购物车(添加一个字段：selected_promotion 参加什么活动)
        $data = $this->objects[$params['obj_type']]->__preAddCartData($params, $this->userId, $basicCartData);
        //print_r($data);
        $db = app::get('systrade')->database();
        $db->beginTransaction();
        try
        {
            $result = $this->objMdlCart->save($data);
            // $this->objects[$params['obj_type']]->__afterSaveCart($data);
            $db->commit();
        }
        catch(\Exception $e)
        {
            $db->rollback();
            throw $e;
        }

        return $result;
    }

    /**
     * @brief 根据条件删除购物车信息，如果条件为空，则清空购物车
     *
     * @param array $params
     *
     * @return bool
     */
    public function removeCart($params, $mode='')
    {
        if($mode=='fastbuy')
        {
            kernel::single('base_session')->start();
            $userIdent = $this->objMdlCart->getUserIdentMd5($this->userId);
            unset( $_SESSION['cart_objects_fastbuy'][$userIdent] );
            kernel::single('base_session')->close();
            return true;
        }
        if( $params )
        {
            $filter['cart_id'] = $params['cart_id'];
        }
        $filter['user_ident'] = $this->objMdlCart->getUserIdentMd5($this->userId);
 $cartdata= $this->objMdlCart->getRow('shop_id,tax_sea_region',$filter); //2016/3/18  lcd  删除购物车的父项勾选表
      
	  $cartdata['user_id']=$this->userId;
     $cart= $this->objMdlCart->getList('shop_id,tax_sea_region',$cartdata); 
	 $n=count($cart);
     if($n==1){
		 //2016/3/18 lcd如果父项组只有一个，购物车删除了，那么就删除掉
		 $objMdlCart_tax = app::get('systrade')->model('cart_tax');//判断父项
         $dataes['user_id']=$this->userId;
		 $dataes['tax_id']=$cartdata['shop_id'].$cartdata['tax_sea_region'];
        $cart_tax = $objMdlCart_tax->delete($dataes);
       }
        $result = $this->objMdlCart->delete($filter);



     $cart= $this->objMdlCart->getList('shop_id,tax_sea_region,is_checked',$cartdata); //删除剩下一个如果勾选就勾选上
	           $num=0;
               foreach ($cart as $addr) {
               $num=$num+$addr['is_checked'];
               }
	 $n=count($cart);
     if($n==$num){
		 //2016/3/18 lcd如果父项组只有一个，购物车删除了，那么这个父项下面，勾选的数量和当前数量相同的时候，就是全选
		 $objMdlCart_tax = app::get('systrade')->model('cart_tax');//判断父项
         $dataes['user_id']=$this->userId;
		 $dataes['tax_id']=$cartdata['shop_id'].$cartdata['tax_sea_region'];
		 $data_b['select_id']=1;
         $objMdlCart_tax->update($data_b,$dataes);
       }

        // app::get('systrade')->model('cart_item')->delete(array('cart_id'=>$params['cart_id']));

        $this->countCart();

        return $result;
    }

    /**
     * @brief 根据条件获取购物车信息
     *
     * @param array $filter
     *
     * @return array
     */
    public function getCartInfo($filter=array(), $needInvalid=true, $platform='pc')
    {
        $aCart = array();
        $cartData = $this->getBasicCart($filter);
        if( empty($cartData) ) return array();
        $result = $this->__preCartInfo($cartData, $needInvalid, $platform,$filter);
        //print_r($result);exit;
        $aCart['resultCartData'] = $result['resultCartData'];
        $aCart['totalCart'] = $result['totalCart'];
        if( empty($aCart['resultCartData']) ) $aCart = array();
        return $aCart;
    }
	 /*
	 *重新整合新数组，分保税，完税，直邮。
	 */
	private function __taxShopCartInfo($shopObjectData,$shopId,&$tax_ids = false ,&$second_shopData = array(),&$IcanuseCouponId = array())
	{
        //2016/3/9lcd  开始拆单
        $newshopData = array();
        //判断所有订单商品是完税还是真邮，如两者都有，则需要显示两种配送方式。
        $tax_index_ids = array(); //object2
        $activePromats  = array();//满减之类的活动列表；
        foreach( $shopObjectData as $row)
        {
        	$tax_sea_region = isset($row['tax_sea_region']) ? trim($row['tax_sea_region']) : ""; //组合Tax AREA
        	//var_dump($tax_sea_region);
        	
        	//edit by xch ----10.08  //无效的数据不考虑
        	if($tax_sea_region && strlen($tax_sea_region)>1){
        		$newshopData[$tax_sea_region][] = $row;
        		//获取业务模式和区域;
	            $tax = substr($tax_sea_region,0,1);
	            $tax = $tax ? intval($tax): 0;
				$region=substr($tax_sea_region, 1);
	            $filters['tax_id']=$shopId.$tax_sea_region;
				$filters['user_id']=userAuth::id();
			
				$select_id =     $this->objCarttax->getRow('select_id,disabled_id',$filters);
				if($select_id['select_id']){
					$newshopData[$tax_sea_region]['select_id'] = $select_id['select_id'];
				}else{
					$newshopData[$tax_sea_region]['select_id'] = 0;
				}
				if($select_id['disabled_id']){
					$newshopData[$tax_sea_region]['disabled_id'] = $select_id['disabled_id'];
				}else{
					$newshopData[$tax_sea_region]['disabled_id'] = 0;
				}
	            $newshopData[$tax_sea_region]['tax'] = $tax;//业务模式
			    $newshopData[$tax_sea_region]['sea_region'] = $region;//业务区域
			    //收集tax列表
        		if($tax > 0 && !in_array($tax ,$tax_index_ids)){
        			$tax_index_ids[] = intval($tax);
        		}
        		//活动列表；
        		if(isset($row["promotions"]) && !empty($row["promotions"]) && is_array($row["promotions"])){
        			foreach($row["promotions"] as $k1=> $acted ){
        				if($k1 && !array_key_exists($k1,$activePromats )){
        					$activePromats [$k1] = $acted;
        				}
        			}
        		}
        	}
        }
        sort($tax_index_ids);//id 升序排列   //print_r($activePromats);
        $tax_ids = $tax_index_ids ? implode("-",$tax_index_ids) : false; //显示参数有1，3 或1－3 ,返回当前店铺订单中所有tax列表
		ksort($newshopData);
		
		//优惠券以店铺为基准，与其它营销活动没有冲突；（只要是店铺内的所有仓库单都满足就能使用优惠券     by xch）
		empty($IcanuseCouponId) and $IcanuseCouponId = array(); //可用优惠券的ID及产品ID；
		//可用优惠券列表：  用户：user.coupon.list      店铺：promotion.coupon.list   //$params = array('page_no' => 0, 'page_size' => 1, 'fields' => '*', 'shop_id' => $shopId, 'platform' => 'wap', 'is_cansend' => 1 );
        $params = array('fields' => '*', 'shop_id' => $shopId, 'platform' => 'wap', 'user_id' => $this->userId ,'is_valid'=>1 ); //is_valid
        $couponListData = app::get('topm')->rpcCall('user.coupon.list', $params, 'buyer');
        //把用户的可用优惠券列出来，   promotion.coupon.list   promotion.couponitem.list   promotion.coupon.get
        if($couponListData['count']>0 && isset($couponListData['coupons'])){
        	foreach($couponListData['coupons'] as $cop => $copon){
        		$coupid = isset($copon["coupon_id"]) ? intval($copon["coupon_id"]) : 0;
        		if($coupid >0 &&  intval($copon["is_valid"]) ==1 ){ //可用优惠券的ID才算
        			$coupitem  = app::get('topm')->rpcCall('promotion.couponitem.list', array("coupon_id"=>$coupid,'fields' => 'item_id,price'), 'buyer');
        			$IcanuseCouponId[$coupid] = $copon; //子券的信息 
        			//参加当前优惠券的所有商品ID
        			$IcanuseCouponId[$coupid]["orderMoney"]=0;
        			$IcanuseCouponId[$coupid]["items"]= $coupitem["total_found"] >0 ? array_column($coupitem["list"],'item_id') : false;
        		}
        	}
        }
		//整理数据
		if($newshopData){
			foreach($newshopData as $rg_e => $reg_info ){ 
				//print_r($reg_info);
				$childgoodDetail = array("tax_region"=> $rg_e,"tax"=>$reg_info["tax"],"sea_region"=>$reg_info["sea_region"]);
				$childgoodDetail["select_id"]= $reg_info["sea_region"];
				$childgoodDetail["disabled_id"]= 0;//$reg_info["disabled_id"]
				$childgoodDetail["selected_flag"]= 1;//默认单个仓库选 中
				$childgoodDetail["zonghe_ratemoney"]= 0;//默认当前子订单的税费是0
				$already_pors = array(); //已参加的活动信息；
				foreach($reg_info as $k => $r_info ){
					//一个有效果的购物车商品数据；
					if($r_info && is_array($r_info) && isset($r_info["obj_type"]) && trim($r_info["obj_type"])=="item"){
						$goods_id = isset($r_info["item_id"])?intval($r_info["item_id"]) :0; //商品ID；
						
					    //参加活动列表信息；
						$select_ppp_index = (isset($r_info["selected_promotion"]) && intval($r_info["selected_promotion"])>0 )? intval($r_info["selected_promotion"]) :false;
			    		//活动类型
			    		$select_prp_type = isset($row["promotion_type"])? trim($row["promotion_type"]): false ;
					 	//收积商品的总件数quantity ；和总价格  totalprice   (只有活动产品的价格)   is_checked = 1已选择的商品
					    if(intval($r_info["is_checked"])===1){		//产品有效选择
					    	$childgoodDetail['total_order_discount_money']=0;
					    	$r_info["disabled_id"] = 0 ;		// 单个商品不能禁用；
					    	//已参加的活动
					    	if( $select_ppp_index && !in_array($select_ppp_index,$already_pors)){
					    		$already_pors[] = $select_ppp_index; 
					    	}
					    	$current_zonghe_rate = isset($r_info["price"]["zonghe_rate_total"])? floatval($r_info["price"]["zonghe_rate_total"]):0;
					    	if(isset($r_info["price"]["total_price"])){
					    		//订单总额
					    		$childgoodDetail['total_order_money'] = ecmath::number_plus( array($childgoodDetail['total_order_money'],$r_info["price"]["total_price"]));
					    		//统计总税数量
					    		$childgoodDetail["zonghe_ratemoney"]= ecmath::number_plus( array( $childgoodDetail["zonghe_ratemoney"],$r_info["price"]["zonghe_rate_total"]));
					    		//活动订单总额；
					    		$select_ppp_index > 0 and $childgoodDetail['total_disorder_money'][$select_ppp_index] = ecmath::number_plus(array($childgoodDetail['total_disorder_money'][$select_ppp_index],$r_info["price"]["total_price"]));
					    	}
					    	if( isset($r_info["quantity"]) &&  $select_ppp_index > 0 ){
					    		$childgoodDetail['all_prom_quantity'][$select_ppp_index] +=  intval($r_info["quantity"]); //活动订单件数；
					    	}
					    	if( isset($r_info["price"]["discount_price"]) &&  $select_ppp_index > 0 ){
					    		$childgoodDetail['all_discount_money'][$select_ppp_index] = ecmath::number_plus( array($childgoodDetail['all_discount_money'][$select_ppp_index],$r_info["price"]["discount_price"])); //活动优惠数；
					    	}
					    	
					    	//优惠券金额统计，是否有满足的情况； 说明优惠券与其它营销活动不冲突；  税不能使用优惠券
					    	//print_r($IcanuseCouponId) ;// 'orderBy'=> 'deduct_money DESC'
					    	if($IcanuseCouponId && !empty($IcanuseCouponId) && count($IcanuseCouponId) >0 ){
					    		//按对象的数组里的字段时行 排序
					    		$IcanuseCouponId = $this-> _sortArrByField($IcanuseCouponId, "deduct_money" , true ,'coupon_id'); //排倒序
					    		foreach($IcanuseCouponId as $copid => $coponitems){
									if($goods_id >0 &&isset($coponitems["items"]) && !empty($coponitems["items"]) && in_array($goods_id,$coponitems["items"])){
										$r_info["has_coupon"] = 1; //商品标识可以优惠券
										$copid = $coponitems["coupon_id"];
										$IcanuseCouponId[$copid]["useCouponGids"][]=$goods_id;//统计满足条件的商品ID；
										$IcanuseCouponId[$copid]["orderMoney"] +=  $r_info["price"]["total_price"];//统计满足条件的总金额；
										$childgoodDetail["can_usecoupon"]= 1;    //标识当如果达到优惠券，表示当前子订单可挂优惠券使用
									}
								}
					    	}
					    }else{
					    	$childgoodDetail["selected_flag"]= 0;//单个仓库不选中
					    }//##end if 
					    $childgoodDetail["orderlist"][$goods_id]=$r_info ;			//有效的一个子商品数据；
				 	}//##end if 
				}
				
				//如果当前订单 是保税类型的，那么要判断价格是否超 2000
				if(isset($childgoodDetail['total_order_money']) && floatval($childgoodDetail['total_order_money'])>2000 && intval($reg_info["tax"])===2){
					 //throw new \LogicException(app::get('systrade')->_('您已超过海关限额￥2000.00，请选择部分商品进行结算'));
					 $childgoodDetail["faile_valid"]= 1;//当前订单无效；不能结算；
					 foreach($childgoodDetail["orderlist"] as $kv=>$vv){
					 	$childgoodDetail["orderlist"][$kv]["faile_valid"] = 1;
					 }
					 $childgoodDetail["faile_valid_msg"]= '您已超过海关限额￥2000.00，请选择部分商品进行结算' ;//当前订单无效；不能结算；
				}
				
				//活动列表(保存的活动列表)
				if( !empty($already_pors)){
					$childgoodDetail["selected_promotion"] = $already_pors; 
					foreach($already_pors as $promat_id){
						if($promat_id && intval($promat_id) >0 ){
							$prp_type = (isset($activePromats[$promat_id]) && isset($activePromats[$promat_id]["promotion_type"]))? trim($activePromats[$promat_id]["promotion_type"]):"none";
							switch ( $prp_type ) {
								case "fullminus":	//满减 取即时活动满减数额；
									$applyData = array('promotion_id' => $promat_id,'fullminus_id' => $activePromats[$promat_id]['rel_promotion_id'],'forPromotionTotalPrice' => $childgoodDetail['total_disorder_money'][$promat_id]);
        							$discount_price = app::get('systrade')->rpcCall('promotion.fullminus.apply', $applyData, 'buyer');
									$discount_price and $childgoodDetail['all_discount_money'][$promat_id]= $discount_price;
									break;
								default:			//其它满折，xy 折都使用discount_price
									$childgoodDetail['all_discount_money'][$promat_id] = isset($childgoodDetail['all_discount_money'][$promat_id])? floatval($childgoodDetail['all_discount_money'][$promat_id]):0 ; //默认为0
									break;
							}
							$childgoodDetail['total_order_discount_money'] = ecmath::number_plus( array($childgoodDetail['total_order_discount_money'] , $childgoodDetail['all_discount_money'][$promat_id]));  //计算总优惠额；
						}
					}
				}
				//cart_list子元素
				$childgoodDetail and $second_shopData[$rg_e] = $childgoodDetail;
			}
			//计算当前优惠券信息是否可以使用；
			foreach($IcanuseCouponId as $copid => $coponitem){
				//满足优惠条件；
				if($copid >0 && isset($coponitem["orderMoney"]) && $coponitem["is_valid"]==1 && $coponitem["orderMoney"] >= $coponitem["limit_money"]){
					$IcanuseCouponId[$copid]["use_show"]="succ";//统计满足条件
				}
			}
			//print_r($IcanuseCouponId);
		 //print_r($second_shopData);
		}
		return $newshopData;
	}
	/**
	 * 根据数组元素中的字段进行反序， 　$desc　＝ true 　为倒序 | false 正序;
	 * 
	 * 并取出key 值作为新的  == key 
	 * 
	 */
	function _sortArrByField($array, $field, $desc = false, $fild_key = ''){
		  $fieldArr = array();
		  $newTarget_data = array();
		  foreach ($array as $k => $v) {  $fieldArr[$k] = $v[$field]; }
		  $sort = $desc == false ? SORT_ASC : SORT_DESC;
		  array_multisort($fieldArr, $sort, $array); 		//反序
		  if($fild_key){
			  	foreach($array as $ky => $kvls) {
			  		$keystr =  isset( $kvls ["{$fild_key}"]) ?  trim( $kvls ["{$fild_key}"]) : '';
			  		if( isset($keystr) && intval($keystr) > 0 ){
			  			$newTarget_data [$keystr] = $kvls ;
			  		}else{
			  			$newTarget_data [] = $kvls ;
			  		}
			  	}
			  	return $newTarget_data ? $newTarget_data : false ;	
		  }else{
		  		return  $array;
		  }
	}

    /**
     * @brief 加载购物车显示数据结构
     *
     * @param array $data  加入购物车数据参数
     * @param array $shopNameArr 加入购物车商品的店铺名称集合
     * @param array $itemsData 加入购物车的基本商品数据集合
     * @param array $skusData 加入购物车的基本SKU数据集合
     *
     * @return array
     */
    private function __preCartInfo($cartData, $needInvalid, $platform)
    {
        $newCartData = [];
        $shopIds = [];
        foreach( $cartData as $row)
        {
            $shopIds[] = $row['shop_id'];
            $newCartData[$row['shop_id']][] = $row;
        }
       
        $shopIds = implode(',', $shopIds);
        $shopNameArr = app::get('systrade')->rpcCall('shop.get.list',array('shop_id'=>$shopIds,'fields'=>'shop_id,shop_type,shop_name'));
        $shopNameArr = array_bind_key($shopNameArr,'shop_id');
        foreach( $newCartData as $shopId=>$shopCartData )
        {
            //如果不存在则表示该店铺，已关闭，那么就不必要再查下该店铺的已加入购物车商品信息
            if( !$shopNameArr[$shopId] ) continue;
            //print_r($shopCartData);
            $shopObjectData = $this->__preShopCartInfo($shopCartData, $needInvalid, $platform);
            //print_r($shopObjectData);
            if( $shopObjectData ){
                $resultCartData[$shopId]['shop_id'] = $shopId;
                $resultCartData[$shopId]['shop_name'] = $shopNameArr[$shopId]['shopname'];
                $resultCartData[$shopId]['shop_type'] = $shopNameArr[$shopId]['shop_type'];
                // 统计购物车的总数量，总价格，促销价格等综合信息
                $cartTotalInfo = $this->__cartTotal($shopObjectData, $shopId, $needInvalid, $platform);
				//print_r($cartTotalInfo); //exit;
                $resultCartData[$shopId]['cartCount'] = $cartTotalInfo['cartCount'];
                $resultCartData[$shopId]['tax_ids']= isset($cartTotalInfo['cartCount']["tax"])?trim($cartTotalInfo['cartCount']["tax"]):"";
                $resultCartData[$shopId]['basicPromotionListInfo'] = $cartTotalInfo['basicPromotionListInfo'];
                $resultCartData[$shopId]['usedCartPromotion'] = $cartTotalInfo['usedCartPromotion'];
                $resultCartData[$shopId]['usedCartPromotionWeight'] = $cartTotalInfo['usedCartPromotionWeight'];
                $resultCartData[$shopId]['cartByPromotion'] = $cartTotalInfo['cartByPromotion'];
                $resultCartData[$shopId]['canuse_copons']=array();
                $resultCartData[$shopId]['cart_list']=array();
                $resultCartData[$shopId]['object'] = $shopObjectData;//原来数据
                $tax_ids = false;
                $second_shopData = false ;
                $second_usecoupons = false ;
				$resultCartData[$shopId]['object2'] = $this->__taxShopCartInfo($shopObjectData,$shopId,$tax_ids,$second_shopData,$second_usecoupons);//开始拆单
				$second_shopData and $resultCartData[$shopId]['cart_list'] = $second_shopData;//重新整理的新数据结构
				$second_usecoupons and $resultCartData[$shopId]['canuse_copons'] = $second_usecoupons;//店铺可以使用的优惠券；
				$tax_ids and $resultCartData[$shopId]['tax_ids'] = $tax_ids ? $tax_ids : ""; //返回组合的Tax列表数据
				
				//print_r($resultCartData);exit;
				//$resultCartData[$shopId]['obj'][$tax_sea_region]['seaname'] = $regionname['name'].' '.$taxname['name'];
            }
        }
        //print_r($resultCartData);
        $return['resultCartData'] = $resultCartData;
        // 统计购物车所有勾选商品的总重量，总数量，总价格，总促销价格
        $totalWeight   = 0;
        $totalNumber   = 0;
        $totalPrice    = 0;
        $totalDiscount = 0;  //
		$totalTax_rate_price = 0;
		$totalTeg_rate_price = 0;
		$zonghe_rateprice_total = 0;
		$totalAfterDiscount_money = 0;//最后商品的要支付的价格；不含运费
        foreach($resultCartData as $v)
        {
            $totalWeight   += $v['cartCount']['total_weight'];
            $totalNumber   += $v['cartCount']['itemnum'];
			$totalTax_rate_price  += $v['cartCount']['tax_rate_price']; //2016/3/11  lcd 添加消费税费
			$totalTeg_rate_price  += $v['cartCount']['reg_rate_price']; //2016/3/11  lcd 添加增值税费
            $totalPrice    += $v['cartCount']['total_fee'];
            $totalDiscount += $v['cartCount']['total_discount'];
            $zonghe_rateprice_total += $v['cartCount']['zonghe_rateprice'];
			if($v['cartCount']['tax']){
				$tax = $v['cartCount']['tax'];  //在购物车里面勾选的时候已经控制，除了完税可以在不同的店铺里面勾选，其他职能在一个店铺里面勾选，那么也就是同一业务模式
			}
			if($v['cartCount']['sea_region']){
				$sea_region = $v['cartCount']['sea_region'];  
			}
			//是否包含有需要计算税的判断方法，tax_ids 是否含有2的数字；   /// 不再使用$tax 来判断  by xch  2016/12/ 22
			$tax_ids = isset($v["tax_ids"])?trim($v["tax_ids"]) : "";
			$oneDiscount_money=0;
			if($tax_ids && (strpos($tax_ids,"2" )>=0 || strpos($tax_ids,"3" )>=0)){ //有
				$oneDiscount_money=ecmath::number_minus(array($v['cartCount']['total_fee'], $v['cartCount']['total_discount'])) + $v['cartCount']['zonghe_rateprice'];
			}else{
		        $oneDiscount_money=ecmath::number_minus(array($v['cartCount']['total_fee'], $v['cartCount']['total_discount']));
			}
			$totalAfterDiscount_money += $oneDiscount_money;
        }
    	/*if($tax==1){  //完税，就是所有税都是0
			$totalTax_rate_price=0;
			$totalTeg_rate_price=0;
			$zonghe_rateprice_total=0;
		} 完税的情况，税为0,如果其他店铺没有勾选，那么久是null，多个店铺也就是完税的情况。所以还是0
		*/
		//$totalAfterDiscount =ecmath::number_minus( array($totalPrice, $totalDiscount) )+ $zonghe_rateprice_total;
        $return['totalCart'] = array(
			'sea_region'=> $sea_region,//2016/3/11  lcd 区域保税和直邮的，完税的区域是不准的。
            'tax'=> $tax,//2016/3/11  lcd 业务模式这个业务模式也不准，保税和直邮拆单以后才能用
			'totalWeight'=> $totalWeight,
            'number' => $totalNumber,
			'totalTax_rate_price' => $totalTax_rate_price, //2016/3/11  lcd 添加累计消费税费
			'totalTeg_rate_price' => $totalTeg_rate_price, //2016/3/11  lcd 添加累计增值税费
			'zonghe_total_ratePrice' => $zonghe_rateprice_total, //2016/3/11  lcd 添加累计消费税费
            'totalPrice' => $totalPrice,
            'totalAfterDiscount'=>	$totalAfterDiscount_money,
			'totalFirstDiscount'=>ecmath::number_minus( array($totalPrice, $totalDiscount) ),
            'totalDiscount'=> $totalDiscount,
        );
        return $return;
    }

    /**
     * 但店铺的购物车的信息统计，及应用促销规则
     * 购物车的cartid根据促销进行分组，索引为0的为不应用促销规则的分组
     * @param  array &$shopObjectData 购物车元数据
     * @return array 应用促销规则后的购物车统计信息
     */
    private function __cartTotal(&$shopObjectData, $shop_id, $needInvalid, $platform)
    {
        // 统一购物车内所有商品关联的促销唯一标识，方便通过促销排列购物车商品
        $basicPromotionListInfo = array();
        //索引为0的代表是不应用促销规则的购物车id分组
        $cartByPromotion = array();   
        //print_r($shopObjectData);

        foreach ($shopObjectData as $basicCartInfo)
        {
            if( $basicCartInfo['promotions'] )
            {
                foreach($basicCartInfo['promotions'] as $k => $v)
                {
                    $basicPromotionListInfo[$k] = $v;
                }
            }
            // 根据促销分类购物车数据(包括所有勾选及未勾选的，只要选择了促销规则的商品都分组)
            if( $needInvalid || $basicCartInfo['valid'])
            {
                $cartByPromotion[$basicCartInfo['selected_promotion']]['cart_ids'][] = $basicCartInfo['cart_id'];
            }
        }
        // 应用促销规则，只包括购物车勾选了商品及有效的商品才应用
        $shop_discount_fee = 0; //店铺总的优惠金额
        $shop_promotion_totalWeight = 0; //用于免邮，符合促销的商品的总重量
        $freeweightArr = false;
        $usedCartPromotion = array(); //使用的促销集合ID
        foreach($cartByPromotion as $kPomotionId=>&$vInfo)
        {
            // 应用满减
            if($basicPromotionListInfo[$kPomotionId]['promotion_type'] == 'fullminus')
            {
            	//print_r($vInfo);
            	$fullminus_totalprice = 0;
                $fullminus_dicount_price = $this->applyFullminus($shopObjectData, $vInfo['cart_ids'],$basicPromotionListInfo[$kPomotionId],$fullminus_totalprice);
                //var_dump($fullminus_dicount_price . " -- " .$fullminus_dicount_price );
                $vInfo['fullminus_totalprice'] = $fullminus_totalprice; //订单额有多少了
                if(isset($fullminus_dicount_price) && $fullminus_dicount_price >0 ){
                	$usedCartPromotion[] = $kPomotionId ;
                	//$shop_discount_fee += $fullminus_dicount_price;
                	$shop_discount_fee = ecmath::number_plus( array($shop_discount_fee,$fullminus_dicount_price));
                	$vInfo['discount_price'] = $fullminus_dicount_price;  	//优惠金额
                	
                }
                /*$fullminus_dicount_price ? $usedCartPromotion[] = $kPomotionId : null;
                $fullminus_dicount_price ? $vInfo['discount_price'] = $fullminus_dicount_price : 0;*/
            }
            // 应用满折
            if($basicPromotionListInfo[$kPomotionId]['promotion_type'] == 'fulldiscount')
            {
            	$fullminus_totalprice =0 ;
                $fulldiscount_dicount_price = $this->applyFulldiscount($shopObjectData, $vInfo['cart_ids'], $basicPromotionListInfo[$kPomotionId],$fullminus_totalprice);
                /*$fulldiscount_dicount_price ? $usedCartPromotion[] = $kPomotionId : null;
                $fulldiscount_dicount_price ? $vInfo['discount_price'] = $fulldiscount_dicount_price : 0;*/
                $vInfo['fullminus_totalprice'] = $fullminus_totalprice; //订单额有多少了
                if(isset($fulldiscount_dicount_price) && $fulldiscount_dicount_price >0 ){
                	$usedCartPromotion[] = $kPomotionId;
                	//$shop_discount_fee += $fulldiscount_dicount_price;
                	$shop_discount_fee = ecmath::number_plus( array($shop_discount_fee,$fulldiscount_dicount_price));
                	$vInfo['discount_price'] = $fulldiscount_dicount_price;  	//优惠金额(满折减免订单金额)
                }
            }
            // 应用xy折扣
            if($basicPromotionListInfo[$kPomotionId]['promotion_type'] == 'xydiscount')
            {
                $xydiscount_dicount_price = $this->applyXydiscount($shopObjectData, $vInfo['cart_ids'], $basicPromotionListInfo[$kPomotionId]);
                $shop_discount_fee += $xydiscount_dicount_price;
                $xydiscount_dicount_price ? $usedCartPromotion[] = $kPomotionId : null;
                $xydiscount_dicount_price ? $vInfo['discount_price'] = $xydiscount_dicount_price : 0;
            }
            // 应用免邮
            if($basicPromotionListInfo[$kPomotionId]['promotion_type'] == 'freepostage')
            {
                $forPromotionTotalWeight = $this->applyFreepostage($shopObjectData, $vInfo['cart_ids'], $basicPromotionListInfo[$kPomotionId],$shop_id,$freeweightArr);
                $shop_promotion_totalWeight += $forPromotionTotalWeight; //(要减免的重量；)
            }
            //print_r($vInfo); 
        }
        
        // 应用优惠券(结算页，购物车页不应用) $couponCode 不为空再计算
        if(!$needInvalid && ($couponCode = $this->getCouponCode($shop_id)))
        {
            $coupon_dicount_price = $this->applyCoupon($shopObjectData, $couponCode);
            $shop_discount_fee += $coupon_dicount_price;
        }
        $cartCount = array();
        $total_fee = $itemnum = $total_weight =$tax_price = $tax_rate_price= $reg_rate_price= $zonghe_rateprice = 0;  //2016/3/11  lcd 添加税费
        foreach ($shopObjectData as $k1=>$v1)
        {
            //统计购物车价格，数量，价格等
            if( $v1['valid'] && $v1['is_checked']=='1' )
            {
				$tax_rate_price += $v1['price']['tax_rate_price'];  //2016/3/11  lcd 添加消费税费
				$reg_rate_price += $v1['price']['reg_rate_price'];  //2016/3/11  lcd 添加增值税费
				$zonghe_rateprice += isset($v1['price']['zonghe_rate_total']) ? floatval($v1['price']['zonghe_rate_total']) :0 ;   //综合税之和 by xch 
                $total_weight += $v1['weight'];
                $itemnum += $v1['quantity'];
                $total_fee += $v1['price']['total_price'];
				$sea_region = $v1['sea_region'];  //2016/3/25雷成德当完税的时候区域不处理，当保税和直邮的时候，因为只能勾选相同区域，所以直接赋值覆盖；拆单以后再使用不准
				$tax = $v1['tax']; //当从购物车执行到订单确认的页面时候，其他地方用不到，业务模式只能是一种，所以直接覆盖；不准
            }
        }
		//print_r($cartByPromotion);
        $return['basicPromotionListInfo'] = $basicPromotionListInfo;
        $return['usedCartPromotion'] = $usedCartPromotion;	//参加活动的ID；
        $return['usedCartPromotionWeight'] = isset($freeweightArr) && !empty($freeweightArr) &&count($freeweightArr)>0 ?$freeweightArr : $shop_promotion_totalWeight;//免邮重量；
        $return['cartByPromotion'] = $cartByPromotion;		//活动的详情
        $return['cartCount'] = array(
			'shop_id' => $shop_id,  //2016/3/11  lcd 添加店铺id，方便手机直接使用，不需要查询。
			'sea_region' => $sea_region,  //2016/3/11  lcd 添加税费，只能拆单以后使用，不准
			'tax_rate_price' => $tax_rate_price,  //2016/3/11  lcd 添加消费税费，只能在保税和直邮拆单完以后使用这个变量，不准
			'reg_rate_price' => $reg_rate_price,  //2016/3/11  lcd 添加增值税费，只能在保税和直邮拆单完以后使用这个变量，不准
			'zonghe_rateprice'=> $zonghe_rateprice, //各个子订单的综合税之和
		    'tax' => $tax,  //2016/3/11  lcd 订单确认页面要用到。业务模式只能是一种
            'total_weight' => $total_weight,
            'itemnum' => $itemnum,
            'total_fee' => $total_fee,
            'total_discount' => $shop_discount_fee,
            'total_coupon_discount' => $coupon_dicount_price,
        );
		//print_r($return);
        return $return;

    }
	

    /**
     * @brief 加载每个店铺的商品数据信息
     *
     * @param array $shopCartData 店铺中加入购物车数据的
     * @param array $itemsData 加入购物车的基本商品数据集合
     * @param array $skusData 加入购物车的基本SKU数据集合
     *
     * @return array
     */
    private function __preShopCartInfo($shopCartData, $needInvalid, $platform)
    {
        $itemIds = $skuIds = array();
        foreach( $shopCartData as $row) {
            if($row['obj_type']=='item'){
                $itemIds[] = $row['item_id'];
                $skuIds[]  = $row['sku_id'];
            }
        }
        //产品SKU；
        if($itemIds && $skuIds){
            $itemRows = 'item_id,cat_id,title,weight,image_default_id,sub_stock,violation,disabled,tax_rate,reg_rate,tax,sea_region';//添加税tax_rate
            $skuRows = 'sku_id,bn,item_id,spec_info,price,weight,status';
            $itemFields['status'] = 'approve_status';
            $itemFields['promotion'] = 'promotion';
            $itemsData = $this->objLibItemInfo->getItemList($itemIds, $itemRows, $itemFields);
            $skusData = $this->objLibItemInfo->getSkusList($skuIds,$skuRows);
        }
        //现在只有普通商品购买流程，因此临时将商品结构写到此
        //如果有其他商品购买类型，则到各类型中进行商品获取
        foreach( $shopCartData as $row )
        {
            $k = $row['cart_id'];
            $shopObjectData[$k] = $this->objects[$row['obj_type']]->processCartObject($row,$itemsData,$skusData);
            $itemId = $row['item_id'];
            //商品活动信息价
            // $shopObjectData[$k]['activityDetail'] = $this->getItemActivityInfo($itemId,$platform); // 活动价格有问题？？？？？？？？？？？？？？？？
            // 获取商品关联的促销信息
            if($row['obj_type']=='item'){
                $shopObjectData[$k]['promotions']= $this->getItemPromotionInfo($itemId, $platform);
                //根据促销的创建时间进行倒序排序，则最新的默认选为商品的促销规则
                if($shopObjectData[$k]['promotions']){
                    // 取商品对应的最后（最新）一个促销(已排序)，默认促销就用这个
                    $latestPromotion = end($shopObjectData[$k]['promotions']);
                    $priorityPromotionId = $latestPromotion['promotion_id'];
                    if($priorityPromotionId && !$row['selected_promotion'] && $row['selected_promotion']!=='0' )
                    {
                        $shopObjectData[$k]['selected_promotion'] = $priorityPromotionId;
                    }
                }
            }
            if( !$needInvalid && (!$shopObjectData[$k]['valid'] || !$row['is_checked']) )
            {
                unset($shopObjectData[$k]);
            }
        }
        return $shopObjectData;
    }

    /**
     * 根据商品返回其相关的促销信息
     * @param  int $itemId 商品id
     * @return array         促销信息数组
     */
    public function getItemPromotionInfo($itemId, $platform='pc')
    {
        $itemPromotionTagInfo = app::get('systrade')->rpcCall('item.promotion.get', array('item_id'=>$itemId),'buyer');
        if(!$itemPromotionTagInfo){
            return false;
        }
        $allPromotion = array();
        foreach($itemPromotionTagInfo as $v)
        {
            $basicPromotionInfo = app::get('systrade')->rpcCall('promotion.promotion.get', array('promotion_id'=>$v['promotion_id'], 'platform'=>$platform), 'buyer');
            if($basicPromotionInfo['valid']===true)
            {
                $allPromotion[$v['promotion_id']] = $basicPromotionInfo;
            }
        }
        // 倒序排序，购物车的默认促销规则选择最新添加的促销适用
        ksort($allPromotion);
        return $allPromotion;
    }

    /**
     * 根据商品返回其单品促销活动，如团购价
     * @param  int $itemId 商品id
     * @return array         活动信息数组
     */
    public function getItemActivityInfo($itemId, $platform='pc')
    {
        $promotionDetail = app::get('topc')->rpcCall('promotion.activity.item.info',array('item_id'=>$itemId, 'platform'=>$platform, 'valid'=>1), 'buyer');
        if(!$promotionDetail)
        {
            return false;
        }
        return $promotionDetail;
    }

    /**
     * 应用满减促销
     * @param  array &$shopObjectData 购物车商品数据
     * @param  array $vCartIds        本促销对应购物车id
     * @param  array $promotionDetail 本促销的详情
     * @return float                  返回折扣金额
     */
    private function applyFullminus(&$shopObjectData, $vCartIds,$promotionDetail,&$forPromotionTotalPrice=0)
    {
        //$forPromotionTotalPrice = 0; // 对应促销商品的总价
        foreach ($vCartIds as $cartId)
        {
            if($shopObjectData[$cartId]['valid'] && $shopObjectData[$cartId]['is_checked']=='1')
            {
                $forPromotionTotalPrice = ecmath::number_plus( array($forPromotionTotalPrice, $shopObjectData[$cartId]['price']['total_price']) );
            }
        }

        $applyData = array(
            'promotion_id' => $promotionDetail['promotion_id'],
            'fullminus_id' => $promotionDetail['rel_promotion_id'],
            'forPromotionTotalPrice' => $forPromotionTotalPrice,
        );
        $discount_price = app::get('systrade')->rpcCall('promotion.fullminus.apply', $applyData, 'buyer');
        if($discount_price>0)
        {
            // 优惠分摊
            foreach ($vCartIds as $cartId)
            {
                if($shopObjectData[$cartId]['valid'] && $shopObjectData[$cartId]['is_checked']=='1')
                {
                    $percent = ecmath::number_div(array($shopObjectData[$cartId]['price']['total_price'], $forPromotionTotalPrice) );
                    $divide_order_price = ecmath::number_multiple(array($discount_price, $percent) );
                    $shopObjectData[$cartId]['price']['discount_price'] = ecmath::number_plus( array( $shopObjectData[$cartId]['price']['discount_price'], $divide_order_price) );
                    $shopObjectData[$cartId]['promotion_type'] = $promotionDetail['promotion_type'];
                    $shopObjectData[$cartId]['promotion_id'] = $promotionDetail['promotion_id'];
                }
            }
        }
        return $discount_price;
    }

    /**
     * 应用满折促销
     * @param  array &$shopObjectData 购物车商品数据
     * @param  array $vCartIds        本促销对应购物车id
     * @param  array $promotionDetail 本促销的详情
     * @return float                  返回折扣金额
     */
    private function applyFulldiscount(&$shopObjectData, $vCartIds, $promotionDetail,&$forPromotionTotalPrice = 0)
    {
        //$forPromotionTotalPrice = 0; // 对应促销商品的总价
        foreach ($vCartIds as $cartId)
        {
            if($shopObjectData[$cartId]['valid'] && $shopObjectData[$cartId]['is_checked']=='1')
            {
                $forPromotionTotalPrice = ecmath::number_plus( array($forPromotionTotalPrice, $shopObjectData[$cartId]['price']['total_price']) );
            }
        }

        $applyData = array(
            'promotion_id' => $promotionDetail['promotion_id'],
            'fulldiscount_id' => $promotionDetail['rel_promotion_id'],
            'forPromotionTotalPrice' => $forPromotionTotalPrice,

        );
        $discount_price = app::get('systrade')->rpcCall('promotion.fulldiscount.apply', $applyData, 'buyer');
        /*if($promotionDetail['promotion_id']==8){
        	print_r($shopObjectData);
        }*/
        if($discount_price>0)
        {
        	//标记当前商品参加活动，显示活动价格；
            //优惠分摊
            foreach ($vCartIds as $cartId)
            {
                if($shopObjectData[$cartId]['valid'] && $shopObjectData[$cartId]['is_checked']=='1')
                {
                    $percent = ecmath::number_div(array($shopObjectData[$cartId]['price']['total_price'], $forPromotionTotalPrice) );
                    $divide_order_price = ecmath::number_multiple(array($discount_price, $percent) );
                    $shopObjectData[$cartId]['price']['discount_price'] = ecmath::number_plus( array( $shopObjectData[$cartId]['price']['discount_price'], $divide_order_price) );
                    $shopObjectData[$cartId]['promotion_type'] = $promotionDetail['promotion_type'];
                    $shopObjectData[$cartId]['promotion_id'] = $promotionDetail['promotion_id'];
                }
            }
        }
        return $discount_price;
    }

    /**
     * 应用XY折促销
     * @param  array &$shopObjectData 购物车商品数据
     * @param  array $vCartIds        本促销对应购物车id
     * @param  array $promotionDetail 本促销的详情
     * @return float                  返回折扣金额
     */
    private function applyXydiscount(&$shopObjectData, $vCartIds, $promotionDetail)
    {
        $forPromotionTotalPrice = 0; // 对应促销商品的总价
        $forPromotionTotalQuantity = 0; // 对应促销商品的总数量
        foreach ($vCartIds as $cartId)
        {
            if($shopObjectData[$cartId]['valid'] && $shopObjectData[$cartId]['is_checked']=='1')
            {
                $forPromotionTotalPrice = ecmath::number_plus( array($forPromotionTotalPrice, $shopObjectData[$cartId]['price']['total_price']) );
                $forPromotionTotalQuantity = ecmath::number_plus( array($forPromotionTotalQuantity, $shopObjectData[$cartId]['quantity']) );
            }
        }

        $applyData = array(
            'promotion_id' => $promotionDetail['promotion_id'],
            'xydiscount_id' => $promotionDetail['rel_promotion_id'],
            'forPromotionTotalPrice' => $forPromotionTotalPrice,
            'forPromotionTotalQuantity' => $forPromotionTotalQuantity,

        );
        $discount_price = app::get('systrade')->rpcCall('promotion.xydiscount.apply', $applyData, 'buyer');
        if($discount_price>0)
        {
            // 优惠分摊
            foreach ($vCartIds as $cartId)
            {
                if($shopObjectData[$cartId]['valid'] && $shopObjectData[$cartId]['is_checked']=='1')
                {
                    $percent = ecmath::number_div(array($shopObjectData[$cartId]['price']['total_price'], $forPromotionTotalPrice) );
                    $divide_order_price = ecmath::number_multiple(array($discount_price, $percent) );
                    $shopObjectData[$cartId]['price']['discount_price'] = ecmath::number_plus( array( $shopObjectData[$cartId]['price']['discount_price'], $divide_order_price) );
                    $shopObjectData[$cartId]['promotion_type'] = $promotionDetail['promotion_type'];
                    $shopObjectData[$cartId]['promotion_id'] = $promotionDetail['promotion_id'];
                }
            }
        }
        return $discount_price;
    }

    /**
     * 应用免邮促销
     * @param  array &$shopObjectData 购物车商品数据
     * @param  array $vCartIds        本促销对应购物车id
     * @param  array $promotionDetail 本促销的详情
     * @return float                  返回应用免邮的商品的重量的和
     */
    private function applyFreepostage(&$shopObjectData, $vCartIds, $promotionDetail,$shop_id=0,&$weightArr = false )
    {
        $forPromotionTotalPrice = 0; // 对应促销商品的总价
        $forPromotionTotalQuantity = 0; // 对应促销商品的总数量
        $forPromotionTotalWeight = 0; // 对应促销商品的总重量
        $freeWeightArr = array();
        foreach ($vCartIds as $cartId)
        {
        	if($cartinfo = $shopObjectData[$cartId]){
        		$sea_reg = isset($cartinfo["tax_sea_region"])&& trim($cartinfo["tax_sea_region"])!=""? trim($cartinfo["tax_sea_region"]): "{$shop_id}".trim($cartinfo["sea_region"]);
        		if(!isset($freeWeightArr[$sea_reg])) $freeWeightArr[$sea_reg] = 0 ;
        		if($shopObjectData[$cartId]['valid'] && $shopObjectData[$cartId]['is_checked']=='1')
	            {
	                $forPromotionTotalPrice = ecmath::number_plus( array($forPromotionTotalPrice, $shopObjectData[$cartId]['price']['total_price']) );
	                $forPromotionTotalQuantity = ecmath::number_plus( array($forPromotionTotalQuantity, $shopObjectData[$cartId]['quantity']) );
	                $forPromotionTotalWeight = ecmath::number_plus( array($forPromotionTotalWeight, $shopObjectData[$cartId]['weight']) );
	                $freeWeightArr[$sea_reg] = ecmath::number_plus( array($freeWeightArr[$sea_reg], $shopObjectData[$cartId]['weight']));
	            }
        	}
        }
        $applyData = array(
            'promotion_id' => $promotionDetail['promotion_id'],
            'freepostage_id' => $promotionDetail['rel_promotion_id'],
            'forPromotionTotalPrice' => $forPromotionTotalPrice,
            'forPromotionTotalQuantity' => $forPromotionTotalQuantity,

        );
        //是否参加免邮验证。
        $freePostageFlag = app::get('systrade')->rpcCall('promotion.freepostage.apply', $applyData, 'buyer');
        //var_dump($freeWeightArr);
        if($freePostageFlag){
        	$weightArr = $freeWeightArr ; //满足条件就把免邮标志带回去。
            return $forPromotionTotalWeight;
        }
        return 0;
    }

    /**
     * 应用免邮促销
     * @param  array $shopObjectData  购物车商品数据
     * @param  array $couponCode      优惠券码
     * @return float                  返回应用优惠券的金额
     */
    private function applyCoupon($shopObjectData, $couponCode)
    {
        $itemWithTotalPriceArr = array();
        foreach ($shopObjectData as $k1=>$v1)
        {
            //统计购物车价格，数量，价格等
            if( $v1['valid'] && $v1['is_checked']=='1' )
            {
                $itemWithTotalPriceArr[] = $v1['item_id'].'_'.$v1['price']['total_price'];
            }
        }

        // 应用优惠券
        $apiParams = array(
            'coupon_code' => $couponCode,
            'cartItemsInfo' => implode('|', $itemWithTotalPriceArr),
        );
        if( $coupon_discount_fee = app::get('systrade')->rpcCall('promotion.coupon.apply', $apiParams,'buyer') )
        {
            return $coupon_discount_fee;
        }

        return 0;
    }

    /**
     * @brief 根据条件查询到基本的购物车数据
     *
     * @param array $filter
     *
     * @return array
     */
    public function getBasicCart($filter)
    {

        if( empty($filter['mode']) || $filter['mode'] == 'cart' )
        {
            unset($filter['mode']);
            $params = $this->objMdlCart->getList('*', $filter);
        }
        else
        {
        	//立即购买
            $data = $this->fastBuyFetch();
            if( $data )
            {
                $params[0] = $data;
            }
        }
        return $params;
    }

    // 获取结算页使用的某店铺优惠券
    public function getCouponCode($shop_id)
    {
        $userIdent = $this->objMdlCart->getUserIdentMd5($this->userId);
        return $_SESSION['cart_use_coupon'][$userIdent][$shop_id];
    }

    /**
     * @brief 选择的优惠券放入购物车优惠券表
     *
     * @param array $data
     *
     * @return array
     */
    public function addCouponCart($coupon_code, $shop_id)
    {
        kernel::single('base_session')->start();
        $userIdent = $this->objMdlCart->getUserIdentMd5($this->userId);
        $_SESSION['cart_use_coupon'][$userIdent][$shop_id] = $coupon_code;
        kernel::single('base_session')->close();
        return true;
        // $objMdlCartCoupon = app::get('systrade')->model('cart_coupon');
        // $data = array(
        //     'coupon_id' => $coupon_id,
        //     'coupon_code' => $coupon_code,
        //     'user_id' => $user_id,
        //     'shop_id' => $shop_id,
        // );
        // return $objMdlCartCoupon->save($data);
    }

    /**
     * @brief 取消优惠券
     *
     * @param array $data
     *
     * @return array
     */
    public function cancelCouponCart($coupon_code, $shop_id)
    {
        if($coupon_code == '-1')
        {
            kernel::single('base_session')->start();
            $userIdent = $this->objMdlCart->getUserIdentMd5($this->userId);
            unset($_SESSION['cart_use_coupon'][$userIdent][$shop_id]);
            kernel::single('base_session')->close();
            return true;
            // $objMdlCartCoupon = app::get('systrade')->model('cart_coupon');
            // $data = array(
            //     'user_id' => "此处user_id需要重新传入",
            //     'shop_id' => $shop_id,
            // );
            // return $objMdlCartCoupon->delete($data);
        }
        else
        {
            return false;
        }

    }

}

