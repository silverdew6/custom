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
        $discount_fee = $params['discount_fee'];
        //默认为1 ，当前店铺下需要拆分的订单个数
        $order_number = intval($params['order_number']) > 0 ? intval($params['order_number']) : 1;

        // 检查传入的配送方式是否属于当前店铺 $dlyTmplId = 10000 为包邮，不计运费
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
        $payment = ecmath::number_plus(array($total_fee, $allpost_fee)); 			//单个邮费，计算为多个邮费；by xch 2016/09/21 
        $payment = ecmath::number_minus(array($payment, $discount_fee));
        
        if($payment < 0)
        {
            $payment = 0.01; //不可以有0元订单，最小值为0.01；后续改造
        }
        //计算商品总额所获积分
        $totalFee = $payment-$allpost_fee;   //计算积分不考虑（多个）邮费。
        $subtotal_obtain_point = app::get('systrade')->rpcCall('user.pointcount',array('money'=>$totalFee));
        $payment_detail = array(
            'total_fee'=>$total_fee,
            'all_post_fee'=>$allpost_fee,
            'post_fee'=>$post_fee,
            'payment'=>$payment,
            'discount_fee' => $discount_fee,
            'obtain_point_fee' => $subtotal_obtain_point,
        );
        return $payment_detail;
    }
}


