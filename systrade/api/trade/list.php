<?php
class systrade_api_trade_list{
    public $apiDescription = "获取订单列表";
    public function getParams()
    {
        $return['params'] = array(
            'user_id' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'订单所属用户id'],
            'shop_id' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'订单所属店铺id'],
            'dlytmpl_id' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'店铺模板id'],
			'tax_price' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'进口税'],
			'price' => ['type'=>'money', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'价格'],
			'sea_region' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'业务区域'],
			'tax' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'业务模式'],
            'status' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'订单状态'],
            'buyer_rate' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'订单评价状态'],
            'tid' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'订单编号,多个用逗号隔开'],
            'create_time_start' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'查询指定时间内的交易创建时间开始yyyy-MM-dd'],
            'create_time_end' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'查询指定时间内的交易创建时间结束yyyy-MM-dd'],
            'receiver_mobile' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'收货人手机'],
            'receiver_phone' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'收货人电话'],
            'receiver_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'收货人姓名'],
            'user_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'会员用户名/手机号/邮箱'],
            'is_aftersale' => ['type'=>'bool', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'是否显示售后状态'],
            'pay_type' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'支付方式【offline、online】'],
            'dlytmpl_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'配送方式(0代表自提，大于0正常快递)'],

            'page_no' => ['type'=>'int','valid'=>'int','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'int','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'int','valid'=>'','description'=>'排序方式','example'=>'','default'=>'created_time desc'],
            'fields' => ['type'=>'field_list','valid'=>'','description'=>'获取的交易字段集','example'=>'','default'=>''],
        );
        $return['extendsFields'] = ['order','activity'];
        return $return;
    }
    /**
     * 获取订单列表信息
     * 
     */
    public function tradeList($params)
    {
	
        if($params['oauth']['account_id'] && $params['oauth']['auth_type'] == "member" )
        {
            $params['user_id'] = $params['oauth']['account_id'];
        }
        elseif($params['oauth']['account_id'] && $params['oauth']['auth_type'] == "shop")
        {
            $sellerId = $params['oauth']['account_id'];
            $params['shop_id'] = app::get('systrade')->rpcCall('shop.get.loginId',array('seller_id'=>$sellerId),'seller');
        }

        $tradeRow = $params['fields']['rows']; //查询所有字段内容
        
        $orderRow = $params['fields']['extends']['order'];
        $activityRow = $params['fields']['extends']['activity'];

        //分页使用
        $pageSize = $params['page_size'] ? $params['page_size'] : 40;
        $pageNo = $params['page_no'] ? $params['page_no'] : 1;
        $max = 1000000;
        if($pageSize >= 1 && $pageSize < 500 && $pageNo >=1 && $pageNo < 200 && $pageSize*$pageNo < $max)
        {
            $limit = $pageSize;
            $page = ($pageNo-1)*$limit;
        }

        $orderBy = $params['orderBy'];
        if(!$params['orderBy'])
        {
            $orderBy = "created_time desc";
        }
        unset($params['fields'],$params['page_no'],$params['page_size'],$params['order_by'],$params['oauth']);

        foreach($params as $k=>$val)
        {
            if(is_null($val))
            {
                unset($params[$k]);
                continue;
            }
            if($k == "status" || $k == "tid")
            {
                $params[$k] = explode(',',$val);
            }
        }

        if( $params['create_time_start'] )
        {
            $params['created_time|bthan'] = $params['create_time_start'];
            unset($params['create_time_start']);
        }

        if( $params['create_time_end'] )
        {
            $params['created_time|lthan'] = $params['create_time_end'];
            unset($params['create_time_end']);
        }
		//2016/5/17  lcd 下线ERP专用
        if( $params['erp_api'] )
        {
			$params['modified_time|bthan'] = $params['modified_time_start'];
            $params['modified_time|lthan'] = $params['modified_time_end'];
            unset($params['modified_time_start']);
            unset($params['modified_time_end']);
        }

        if( $params['user_name'] )
        {
            $userIds = app::get('systrade')->rpcCall('user.get.account.id',['user_name'=>$params['user_name']]);
            unset($params['user_name']);
            $params['user_id'] = $userIds;
        }

        $objMdlTrade = app::get('systrade')->model('trade');
        $objMdlRegion = app::get('sysshop')->model('ware_region'); //区域  	//修改成ware_region表中；
	    $objMdlTax = app::get('sysshop')->model('tax');//业务模式
        $count = $objMdlTrade->count($params);
        $tradeLists = $objMdlTrade->getList($tradeRow,$params,$page,$limit,$orderBy);
        $tradeLists = array_bind_key($tradeLists,'tid');
        
		//修改业务模式和区域的名字
        foreach($tradeLists as $k=>&$v)
       {
       		if(isset($v) && isset($v["tax"]) && intval($v["tax"]) > 0 ){
       			$name=$objMdlTax->getRow('name',array("id"=>intval($v["tax"]) ));
       			$tradeLists[$k]['tax_name'] = $name['name'];
       		}
       		if(isset($v) && isset($v["sea_region"]) && intval($v["sea_region"]) > 0 ){
       			$regionname=$objMdlRegion->getRow('name',array("id"=>intval($v["sea_region"])));
       			$tradeLists[$k]['sea_region'] = $regionname['name'];
       		}
         }
        if($orderRow && $tradeLists)
        {
            $orderRow = str_append($orderRow,'tid');
            $objMdlOrder = app::get('systrade')->model('order');
            $mdlProDetal = app::get('systrade')->model('promotion_detail');
            $tids = array_column($tradeLists,'tid');
            $orderLists = $objMdlOrder->getList($orderRow,array('tid'=>$tids));
            //是否需要显示标签促销tag
            if( $activityRow && $orderLists )
            {
                $oids = array_column($orderLists,'oid');
                $promotionActivityData = $mdlProDetal->getList('promotion_tag,oid',['promotion_type'=>'activity','oid'=>$oids]);
                //一个子订单只可参加一次标签促销活动
                $promotionActivityData = array_bind_key($promotionActivityData,'oid');
            }

            foreach($orderLists as $key=>$value) {
                $ooid = isset($value['oid'])? trim($value['oid']) :false ;
            	$ttid = isset($value['tid'])? trim($value['tid']) :false ;
            	$item_id = isset($value['item_id'])? trim($value['item_id']) :0 ;
                if($ooid &&  $promotionActivityData[$ooid]['promotion_tag'] ){
                    $value['promotion_tag'] = $promotionActivityData[$ooid]['promotion_tag'];
                }
                if($ttid){
                	//支持所有活动tag －－－－－10.20号新加满减也是促销； by xch   
                	$fiterparams = array("tid"=> $ttid ,"user_id|than"=> 0,"promotion_id|than"=>0,"item_id"=>$item_id); //lthan 小于，than大于，
	                $promData = $mdlProDetal->getList('promotion_id,promotion_tag,oid,promotion_type,promotion_name,item_id,sku_id',$fiterparams);
	                if($promData && !empty($promData)){
	                	$promData = array_bind_key($promData,'promotion_id');//把ID作为key
	                }
	                $value["userpromotions"] = isset($promData)&& !empty($promData)?$promData  :false;
	                unset($promData);
                }
                $tradeLists[$ttid]['order'][] = $value;
            }

            //获取售后状态
            if($params['is_aftersale'])
            {
                $afterParams = array();
                $afterParams['fields'] = 'tid,progress,aftersales_bn';
                $afterParams['tid'] = $tids;
                $afterParams['shop_id'] = $params['shop_id'];
                $afterList = app::get('sysaftersales')->rpcCall('aftersales.list.get',$afterParams);
                $afterList = $afterList['list'];
                if($afterList)
                {
                    foreach ($afterList as $afterVal)
                    {
                        $tradeLists[$afterVal['tid']]['aftersale'] = $afterVal;
                    }
                }
            }
        }

        $trade['list'] = $tradeLists;
        $trade['count'] = $count;
        return $trade;
    }
}



