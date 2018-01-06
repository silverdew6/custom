<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class systrade_data_trade_total {

    /**
     * 生成订单总计详细
     * @params object 控制器
     * @params object cart objects
     * @params array sdf array
     */
    public function trade_total_method($params)
    {
        $total_fee = $params['total_fee'];
        $items_weight = $params['total_weight'];
        $dlyTmplId = $params['template_id']; //运费模板ID，
        $shopId = $params['shop_id'];
        $region_id = $params['region_id'];
        $usedCartPromotionWeight = $params['usedCartPromotionWeight'];
        $discount_fee =isset( $params['discount_fee']) ? floatval( $params['discount_fee']) :0 ; // 被优惠的金额 
        //默认为1－，当前店铺下需要拆分的订单个数
        $order_number = intval($params['order_number']) > 0 ? intval($params['order_number']) : 1;

        //	检查传入的配送方式是否属于当前店铺 $dlyTmplId = 10000 为包邮，不计运费
        if($dlyTmplId && $dlyTmplId!='-1' && intval($dlyTmplId) != 10000)
        {
            $tmpParams = array(
                'shop_id' => $params['shop_id'],
                'status' => 'on',
                'fields' => 'template_id',
            );
            $dtytmpls = app::get('systrade')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
            $validTemplateIds = array_column($dtytmpls['data'], 'template_id');
            if(!in_array($dlyTmplId, $validTemplateIds))
            {
                throw new \LogicException(app::get('systrade')->_('配送方式选择有误，商家没有此配送方式！'));
            }
        }

        if($dlyTmplId && $region_id && intval($dlyTmplId) != 10000 ) {
            $params = array(
                'template_id' => $dlyTmplId,
                'weight' => $items_weight,
                'areaIds' => $region_id,
            );
            // 免运费促销 优惠前的运费
            $beforePromotion_post_fee = app::get('systrade')->rpcCall('logistics.fare.count',$params); //邮费列表
            if($usedCartPromotionWeight>0)
            {
                // 免运费促销 优惠后的运费
                $minusWeight = ecmath::number_minus(array($items_weight, $usedCartPromotionWeight));
                if($minusWeight>0)
                {
                    $params['weight'] = $minusWeight;
                    $post_fee = app::get('systrade')->rpcCall('logistics.fare.count',$params)[$dlyTmplId];
                } else {
                    $post_fee = 0;
                }
            }
            else
            {
                // 没有免运费的运费
                $post_fee = $beforePromotion_post_fee[$dlyTmplId];
            }
            if($post_fee<0)
            {
                $post_fee = 0;
            }
        }
        if(isset($dlyTmplId) &&  intval($dlyTmplId) === 10000 ){
        	$post_fee = 0.00; 			//直邮固定20块包邮，不计运费 by xch 20160924
        }
        // $objMath = kernel::single('ectools_math');
        $allpost_fee = $order_number >= 1 ? $order_number * $post_fee : $post_fee ;//计算当前店总邮
        //var_dump($allpost_fee);
        //优惠金额只能用在订单金额上。
        $lastOrderFee = ecmath::number_minus(array($total_fee, $discount_fee));  //订单最后的金额； 减去优惠部分
        //订单需要邮费；
        $lastPayment = $lastOrderFee;//(最后要付的钱是这个数)
        if($allpost_fee > 0 ){
        	$lastPayment = ecmath::number_plus(array($lastOrderFee , $allpost_fee));  //单个邮费，计算为多个邮费；by xch 2016/09/21 
        }
        
        if($lastPayment < 0){
            $lastPayment = 0.01; //不可以有0元订单，最小值为0.01；后续改造
        }
        /*$payment = ecmath::number_minus(array($payment, $discount_fee));
        var_dump($payment);
        if($payment < 0)
        {
            $payment = 0.01; //不可以有0元订单，最小值为0.01；后续改造
        }*/
        //计算商品总额所获积分
        $subtotal_obtain_point = app::get('systrade')->rpcCall('user.pointcount',array('money'=>$lastOrderFee));//计算积分不考虑（多个）邮费。
        $payment_detail = array(
            'total_fee'=>$total_fee,
            'all_post_fee'=>$allpost_fee,
            'post_fee'=>$post_fee,
            'payment'=>$lastPayment,
            'discount_fee' => $discount_fee,
            'obtain_point_fee' => $subtotal_obtain_point,
        );
        return $payment_detail;
    }
    /**
     * 重新计算单个子订单邮费；
     * 适用于拆单之后重构子订单来计算邮费；
     */
    function total_childOrder_method($params,$shopId,$region_id = false ){
    	if(!$shopId)return false;
    	if(!$region_id) return false;
    	$items_weight = $params['total_weight'];
        $dlyTmplId = $params['template_id']; //运费模板ID，
        $usedCartPromotionWeight = $params['usedCartPromotionWeight'];
        $all_post_fee = 0 ;	//计算最后的运费
        //	检查传入的配送方式是否属于当前店铺 $dlyTmplId = 10000 为包邮，不计运费
        if($dlyTmplId && $dlyTmplId!='-1' && intval($dlyTmplId) != 10000)
        {
            $tmpParams = array('shop_id' => $shopId,'status' => 'on','fields' => 'template_id');
            $dtytmpls = app::get('systrade')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
            $validTemplateIds = array_column($dtytmpls['data'], 'template_id');
            if(!in_array($dlyTmplId, $validTemplateIds)){
                throw new \LogicException(app::get('systrade')->_('配送方式选择有误，商家没有此配送方式！'));
            }
            //查找相关的运费
            $params = array('template_id' => $dlyTmplId, 'weight' => $items_weight, 'areaIds' => $region_id);
            //免运费促销 优惠前的运费
            $beforePromotion_post_fee = app::get('systrade')->rpcCall('logistics.fare.count',$params); //邮费列表
            if($usedCartPromotionWeight>0){
                //免运费促销 优惠后的运费
                $minusWeight = ecmath::number_minus(array($items_weight,$usedCartPromotionWeight));
                if($minusWeight>0){
                    $params['weight'] = $minusWeight;
                    $post_fee = app::get('systrade')->rpcCall('logistics.fare.count',$params);
                    $post_fee and $all_post_fee = isset($post_fee[$dlyTmplId]) ? floatval($post_fee[$dlyTmplId]) : 0;
                } else {
                    $all_post_fee = 0;
                }
            }
            else
            {
                $all_post_fee = isset($beforePromotion_post_fee[$dlyTmplId]) ? floatval($beforePromotion_post_fee[$dlyTmplId]) : 0.0; // 没有免运费的运费
            }
        }
    	return $all_post_fee ?  $all_post_fee : 0.0  ; 
    }
    
    
}


