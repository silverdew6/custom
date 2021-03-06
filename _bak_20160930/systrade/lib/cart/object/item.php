<?php

class systrade_cart_object_item implements systrade_interface_cart_object{

    //返回加入购物车的商品类型
    public function getObjType()
    {
        return 'item';//普通商品类型
    }

    //获取检查是否可以加入购物车排序，由小到大排序处理
    public function getCheckSort()
    {
        return 100;
    }

    public function basicFilter($params)
    {
        $obj_ident = $this->__genObjIdent($params);
        return array('obj_type'=>$params['obj_type'],'obj_ident'=>$obj_ident);
    }

    /**
     * @brief 检查购买的商品是否可以购买
     *
     * @param array $checkParams 加入购物车参数
     * @param array $itemData 加入购物车的基本商品数据
     * @param array $skuData 加入购物车的基本SKU数据
     *
     * @return bool
     */
    public function check($checkParams, $itemData, $skuData)
    {
        if( $checkParams['obj_type'] != 'item' ) return true;

        // 如果是更新购物车则获取sku_id
        if($checkParams['cart_id'] && !$checkParams['sku_id'])
        {
            $checkParams['sku_id'] = $basicCartData['0']['sku_id'];
        }
        //检查加入购物的商品是否有效
        if( empty($checkParams['sku_id']) )
        {
            throw new \LogicException(app::get('systrade')->_("缺少sku信息，无法加入购物车!"));
        }

        $this->objLibItemInfo = kernel::single('sysitem_item_info');
        $skuData = $this->objLibItemInfo->getSkuInfo($checkParams['sku_id']);

        if($checkParams['totalQuantity'] <=0)
        {
            throw new \LogicException(app::get('systrade')->_("库存不能为零，最小库存为1"));
        }
        //有效库存（可售库存）
        $validQuantity = $skuData['store'] - $skuData['freez'];
        if( $validQuantity < $checkParams['totalQuantity'] )
        {
            throw new \LogicException(app::get('systrade')->_("库存不足, 最大库存为".$validQuantity));
        }
        return true;
    }

    /**
     * 校验加入购物车数据是否符合要求-各种类型的数据的特殊性校验
     * @param array 加入购物车数据
     * @param string message 引用值
     * @return boolean true or false
     */
    public function checkObject($params, $basicCartData)
    {
        // 如果是更新购物车则获取sku_id
        if($params['cart_id'])
        {
            $params['sku_id'] = $basicCartData['0']['sku_id'];
            $params['quantity'] = $params['totalQuantity'];
        }

        //检查加入购物的商品是否有效
        if( empty($params['sku_id']) )
        {
            throw new \LogicException(app::get('systrade')->_("缺少sku信息，无法加入购物车!"));
        }

        if( !$params['quantity'] )
        {
            throw new \LogicException(app::get('systrade')->_("最少购买1件"));
        }
        $this->objLibItemInfo = kernel::single('sysitem_item_info');
        $skuData = $this->objLibItemInfo->getSkuInfo($params['sku_id']);

        $itemData = $this->objLibItemInfo->getItemInfo(array('item_id'=>$skuData['item_id']));
        //检测库存

        $validQuantity = $skuData['store'] - $skuData['freez'];
        if($params['totalQuantity'] > $validQuantity)
        {
            throw new \LogicException(app::get('systrade')->_("库存不足, 最大库存为".$validQuantity));
            return false;
        }

        //检查加入购物的商品是否有效
        if( !$this->__checkItemValid($itemData, $skuData) )
        {
            throw new \LogicException(app::get('systrade')->_("无效商品，加入购物车失败"));
        }

        return true;
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
     * @brief 加入购物车数据处理
     *
     * @param array $params 加入购物车基本（合并已有购物车）数据
     *
     * @return array
     */
    public function __preAddCartData($mergeParams, $userId, $basicCartData)
    {
        kernel::single('base_session')->start();
        $this->objMdlCart = app::get('systrade')->model('cart');
		$objMdlCart_tax = app::get('systrade')->model('cart_tax');//判断父项
        $userIdent = $this->objMdlCart->getUserIdentMd5($userId);

        if( $mergeParams['cart_id'] )
        {
            $data['cart_id'] = $mergeParams['cart_id'];
            $mergeParams['sku_id'] = $basicCartData['0']['sku_id'];
        }
        else
        {
            $data['created_time'] = time();
        }

        $this->objLibItemInfo = kernel::single('sysitem_item_info');
        $skuData = $this->objLibItemInfo->getSkuInfo($mergeParams['sku_id']);
        $itemData = $this->objLibItemInfo->getItemInfo(array('item_id'=>$skuData['item_id']));

        // 是否购物车选中了
        $data['is_checked'] = $mergeParams['is_checked'];


        // 保存购物车选中的促销信息状态
        if(isset($mergeParams['selected_promotion']))
        {
            $data['selected_promotion'] = intval($mergeParams['selected_promotion']);
        }
      	else
        {
            $data['selected_promotion'] = '0';  // 2016/3/9 LCD保存购物车选中的促销信息状态默认为0，海关业务最简单的二次开发。务必修 改为0,只影响排序，不影响功能，前面的版本也是默认为0
        }
        $data['user_id'] = $mergeParams['user_id'];
        $data['user_id'] = $data['user_id'] ? $data['user_id'] : '-1';
        $data['user_ident'] = $userIdent;
		$data['user_ident'] = $userIdent;
        $data['shop_id'] = $itemData['shop_id'];
        $data['obj_type'] = $mergeParams['obj_type'] ? $mergeParams['obj_type'] : 'item';
        $data['obj_ident'] = $this->__genObjIdent($mergeParams);
        $data['item_id'] = $itemData['item_id'];
        $data['sku_id'] = $mergeParams['sku_id'];
        $data['title'] = $skuData['title'];
        $data['image_default_id'] = $itemData['image_default_id'];
        $data['quantity'] = $mergeParams['totalQuantity'];
		//2016/3/4  lcd 购物车增加业务模式和区域。
		$data['tax'] = $itemData['tax'];
		$data['sea_region'] = $itemData['sea_region'];
		//2016/3/4  lcd 用于购物车拆单，相同店铺和的业务模式和区域才能放一起
		$data['tax_sea_region'] =$itemData['tax'].$itemData['sea_region'];
       //拆单屏蔽项目
	   if($mergeParams['mode']=='cart'){  //当商品是添加到购物车的时候才执行
       if($data['tax']==1){//当业务模式为完税的时候
		     $ifter['user_id']=$data['user_id'];
		     $ifter['tax']=1;
             $cart_id = $this->objMdlCart->getRow('cart_id,select_tax',$ifter);
			 if($cart_id){
			 if($cart_id['select_tax']==1){
			$data['select_tax']=1;
            $dataes['user_id']=$data['user_id'];
		    $dataes['tax_id']=$data['shop_id'].$data['tax_sea_region'];
           $cart_tax = $objMdlCart_tax->getRow('cart_tax_id',$dataes);
		   if(!$cart_tax){
			 $dataes['select_id']=0; 
			$dataes['disabled_id']=1; 
		      $objMdlCart_tax->save($dataes);
		   }
			  }else{
		    $data['select_tax']=0;
			$dataes['user_id']=$data['user_id'];
		    $dataes['tax_id']=$data['shop_id'].$data['tax_sea_region'];
            $cart_tax = $objMdlCart_tax->getRow('cart_tax_id',$dataes);
		    if(!$cart_tax){
			$dataes['select_id']=0; 
			$dataes['disabled_id']=0; 
		    $objMdlCart_tax->save($dataes);
		     }
			  }
               }else{//如果是第一完税的加入购物车，那么先检查保税和直邮是否是被屏蔽
				       $d['user_id']=$data['user_id'];
				       $d['tax'] =2;//由于没有商派的开发文档没有讲or查询语句，只有分开写了。
				       $cartpid = $this->objMdlCart->getRow('cart_id,select_tax,is_checked',$d);
                        $d['tax'] =3;
					   $cartfid = $this->objMdlCart->getRow('cart_id,select_tax,is_checked',$d);
						if($cartpid['is_checked']||$cartfid['is_checked']){
                        $dataes['select_id']=0;   //保税和直邮的没有勾选，那么就不屏蔽
						$dataes['disabled_id']=1; 
						}else{
						$dataes['select_id']=0; 
						$dataes['disabled_id']=0; 
						}
						$dataes['user_id']=$data['user_id'];
						$dataes['tax_id']=$data['shop_id'].$data['tax_sea_region'];
						$objMdlCart_tax->save($dataes);
						   }
       }
       if($data['tax']==2||$data['tax']==3){//是保税和直邮的情况
		     $ifter['user_id']=$data['user_id'];
			 $ifter['shop_id']=$data['shop_id'];
		     $ifter['tax_sea_region']=$data['tax_sea_region'];
             $cart_id = $this->objMdlCart->getRow('cart_id,select_tax',$ifter);
			 if($cart_id){//如果能查询到保税的情况
				 if($cart_id['select_tax']==1){//如果屏蔽
		        $data['select_tax']=1;
				 }else{
				 $data['select_tax']=0;  //没有屏蔽
				 }
			 }else{//如果没有查询到结果，说明不存在
			 	$t['user_id']=$data['user_id'];
			   $checked = $this->objMdlCart->getList('is_checked',$t);//判断购物车是否有勾选，如果有就屏蔽
               $num=0;
               foreach ($checked as $addr) {
               $num=$num+$addr['is_checked'];
               }
			   if($num){
			   $data['select_tax']=1;
			   $dataes['select_id']=0; 
				$dataes['disabled_id']=1; 
			   }else{
			   $data['select_tax']=0;
			   	$dataes['select_id']=0; 
				$dataes['disabled_id']=0; 
			   }
			   	$dataes['user_id']=$data['user_id'];  //写入父项目表
				$dataes['tax_id']=$data['shop_id'].$data['tax_sea_region'];
				$objMdlCart_tax->save($dataes);
			 }
        }
	   }
        // 活动，剩余购买数量
        $restActivityNum = $this->restBuyNum($itemData['item_id'], $data['user_id']);
        if( $restActivityNum['ifactivity'] )
        {
            if($mergeParams['totalQuantity'] >= $restActivityNum['restActivityNum'])
            {
                $data['quantity'] = $restActivityNum['restActivityNum']>0 ? $restActivityNum['restActivityNum'] : 0;
            }
        }

        $data['modified_time'] = time();
        if(isset($data["tax_sea_region"])){
        	$data["tax_sea_region"] = intval($data["tax_sea_region"]); //tax_sea_region 值 为数字； by xch 
        }else{
        	unset($data["tax_sea_region"]);
        }
        return $data;
    }

    // 活动剩余购买数量,购物车结构改造，临时存放这里，此方法目前和systrade_data_cart里面的方法一致
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

    // 处理一条购物车object信息
    public function processCartObject($row,$itemsData,$skusData)
    {
        $itemId = $row['item_id'];
        $skuId = $row['sku_id'];
        $userId = $row['user_id'];
        $objectData['cart_id'] = $row['cart_id'];
        $objectData['obj_type'] = $row['obj_type'];
		$objectData['sea_region'] = $row['sea_region'];//业务区域 2016/4/27 雷成德
		$objectData['tax'] = $row['tax'];//业务区域2016/4/27  雷成德
        $objectData['item_id'] = $itemId;
        $objectData['sku_id'] = $skuId;
        $objectData['user_id'] = $userId;
		$objectData['select_tax'] = $row['select_tax'];  //判断保税和直邮是否选中2016/4/27 雷成德
		$objectData['tax_sea_region'] = $row['tax_sea_region'];  //插入拆单 2016/4/27 雷成德
        $objectData['selected_promotion'] = $row['selected_promotion'];
        $objectData['cat_id'] = $itemsData[$itemId]['cat_id'];
		$objectData['tax_rate'] = $itemsData[$itemId]['tax_rate'];  //2016/3/10   消费税率 雷成德
		$objectData['reg_rate'] = $itemsData[$itemId]['reg_rate'];  //2016/3/10   增值税率 雷成德
        $objectData['sub_stock'] = $itemsData[$itemId]['sub_stock'];
        $objectData['spec_info'] = $skusData[$skuId]['spec_info'];
        $objectData['bn'] = $skusData[$skuId]['bn'];
        //可售库存
        $objectData['store'] = $skusData[$skuId]['realStore'];
        $objectData['status'] = $itemsData[$itemId]['approve_status'];
        // 初始状态下折扣金额从0开始
        $objectData['price']['discount_price'] = 0;
        // 活动，剩余购买数量,如果剩余
        $restActivityNum = $this->restBuyNum($itemId, $userId);
        if( $restActivityNum['ifactivity'] )
        {
            if($row['quantity'] >= $restActivityNum['restActivityNum'])
            {
                $row['quantity'] = $restActivityNum['restActivityNum']>0 ? $restActivityNum['restActivityNum'] : 0;
            }
        }
        $objectData['quantity'] = $row['quantity'];//购买数量
        $objectData['title'] = $itemsData[$itemId]['title'] ? $itemsData[$itemId]['title'] : $row['title'];
        $objectData['image_default_id'] = $itemsData[$itemId]['image_default_id'] ? $itemsData[$itemId]['image_default_id'] : $row['image_default_id'];
        $objectData['weight'] = ecmath::number_multiple(array($skusData[$skuId]['weight'],$row['quantity']));
        $activityDetail = $this->getItemActivityInfo($itemId, $platform);
        if($activityDetail['activity_price']>0)
        {
          $objectData['price']['price'] = $activityDetail['activity_price']; //购买促销后单价tax_rate
			if($objectData['tax']>1){

   			$objectData['price']['tax_rate_price'] = 
     round(($objectData['price']['price']*$row['quantity']/(1-$itemsData[$itemId]['reg_rate']))*$itemsData[$itemId]['reg_rate']*0.7,2);//消费税 2016/4/27 雷成德reg_rate
				
			$objectData['price']['reg_rate_price'] = round(($objectData['price']['price']*$row['quantity']+$objectData['price']['tax_rate_price'])*$itemsData[$itemId]['tax_rate']*0.7,2);//增值税 2016/4/27 雷成德

            $objectData['price']['total_price'] = ecmath::number_multiple(array($activityDetail['activity_price'],$row['quantity'])); //购买此SKU总价格
            $oldTotalPrice = ecmath::number_multiple(array($skusData[$skuId]['price'],$row['quantity']))+$objectData['price']['tax_rate_price']+$objectData['price']['reg_rate_price']; //购买此SKU总价格
            // 平台活动不能在这里计算折扣金额，会导致计算子订单优惠分摊出错
            // $objectData['price']['discount_price'] = ecmath::number_minus(array($oldTotalPrice, $objectData['price']['total_price']));
            $objectData['activityDetail'] = $activityDetail;
            $objectData['promotion_type'] = 'activity'; //活动类型（针对单品），
			}else{
			$objectData['price']['tax_rate_price'] = 0;
	        $objectData['price']['reg_rate_price'] = 0;
            $objectData['price']['total_price'] = ecmath::number_multiple(array($activityDetail['activity_price'],$row['quantity'])); //购买此SKU总价格
            $oldTotalPrice = ecmath::number_multiple(array($skusData[$skuId]['price'],$row['quantity'])); //购买此SKU总价格
            // 平台活动不能在这里计算折扣金额，会导致计算子订单优惠分摊出错
            // $objectData['price']['discount_price'] = ecmath::number_minus(array($oldTotalPrice, $objectData['price']['total_price']));
            $objectData['activityDetail'] = $activityDetail;
            $objectData['promotion_type'] = 'activity'; //活动类型（针对单品），
			}
        }
        else
        {
            $objectData['price']['price'] = $skusData[$skuId]['price']; //购买促销前单价
		if($objectData['tax']>1){ //保税和直邮
   			$objectData['price']['tax_rate_price'] = 
     round(($skusData[$skuId]['price']*$row['quantity']/(1-$itemsData[$itemId]['reg_rate']))*$itemsData[$itemId]['reg_rate']*0.7,2);//消费税 2016/4/27 雷成德
			$objectData['price']['reg_rate_price'] = round(($skusData[$skuId]['price']*$row['quantity']+$objectData['price']['tax_rate_price'])*$itemsData[$itemId]['tax_rate']*0.7,2);//增值税 2016/4/27 雷成德
            $objectData['price']['total_price'] = ecmath::number_multiple(array($skusData[$skuId]['price'],$row['quantity'])); //购买此SKU总价格
		}else{ //完税情况
         $objectData['price']['tax_rate_price'] = 0;
	        $objectData['price']['reg_rate_price'] = 0;
            $objectData['price']['total_price'] = ecmath::number_multiple(array($skusData[$skuId]['price'],$row['quantity'])); //购买此SKU总价格	
		}

        }
        if($row['obj_type']!='package')
        $objectData['valid'] = $this->__checkItemValid($itemsData[$itemId], $skusData[$skuId] );//是否为有效数据
        // 如果可购买数量小于等于0（一般是活动限购会导致此情况），则商品失效
        if($objectData['quantity']<=0)
        {
            $objectData['valid'] = false;
        }
        if($objectData['valid'])
        {
            $objectData['is_checked'] = $row['is_checked'];
        }
        else
        {
            $objectData['is_checked'] = 0;
        }
        return $objectData;
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

    // 购物车的唯一信息进行判别是添加还是更新购物车，
    private function __genObjIdent(&$aData) {
        return $this->getObjType().'_'.$aData['sku_id'];
    }

    // 保存购物车主表cart_objects的时候，把对应的sku数量信息保存到cart_item表,方便库存判断
    public function __afterSaveCart($fullCartData)
    {
        $data['cart_id'] = $fullCartData['cart_id'];
        $data['sku_id'] = $fullCartData['sku_id'];
        $data['quantity'] = $fullCartData['quantity'];
        return app::get('systrade')->model('cart_item')->save($data);
    }

}

