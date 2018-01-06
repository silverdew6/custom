<?php
/**
 * 接口作用说明
 * item.search
 */
class sysitem_api_promotion_useCouponinfo{

    public $apiDescription = '根据条件获取商品可用优惠券列表信息';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
            'item_id' => ['type'=>'string','valid'=>'required','description'=>'商品id，多个id用，隔开','example'=>'2,3,5,6','default'=>''],
            'shop_id' => ['type'=>'int','valid'=>'','description'=>'店铺id','example'=>'','default'=>''],
            'page_no' => ['type'=>'int','valid'=>'numeric','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'numeric','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'int','valid'=>'numeric','description'=>'排序方式','example'=>'','default'=>'modified_time desc,list_time desc'],
            'fields' => ['type'=>'field_list','valid'=>'','description'=>'要获取的商品字段集','example'=>'','default'=>''],
        );
        $return['extendsFields'] = ['promotion','store'];
        return $return;
    }

    public function useCouponinfo($params)
    {
        $objMdlItem = app::get('sysitem')->model('item');
        $itemId  = isset($params["item_id"]) ?  intval($params["item_id"]) :0 ;
        $shopId  = isset($params["shop_id"]) ?  intval($params["shop_id"]) :0 ;
        //获取商品的店铺ID；
        if($itemId >0 && isset($shopId) && intval($shopId) >0){
        	 $shopId  = intval($shopId);
        }else{
        	$info = $objMdlItem -> getRow("item_id,shop_id",array("item_id"=>$itemId)); //获取ID；
        	(isset($info) && intval($info["shop_id"]) > 0) and $shopId = intval($info["shop_id"]);
        }
        //去获取商品的优惠券列表信息
        return kernel::single('sysitem_data_item')->getItemCouponList($itemId,$shopId);
    }
}
