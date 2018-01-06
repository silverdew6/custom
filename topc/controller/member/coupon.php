<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topc_ctl_member_coupon extends topc_ctl_member {

	var $offenModel;
	public function __construct(){
		parent::__construct();
		$this->offenModel = app::get('sysuser')->model('cpoffen');
	}
	
    public function couponList()
    {
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = 10;
        $params = array(
            'page_no' => intval($filter['pages']),
            'page_size' => intval($pageSize),
            'fields' =>'*',
            'user_id'=>userAuth::id(),
        );
        $couponListData = false;
        if(isset($filter["offen"]) && trim($filter["offen"]) == "on"){ 
        	//线下优惠券  
        	$fiterparams = array("status"=> 1 ,'user_id'=>userAuth::id() ); //lthan 小于，than大于， "offen_status"=>0, 
			//查询PC端没有拉取的数据
			$offset= isset($filter['pages']) && $filter['pages']>0 ? ($filter['pages']-1)*$pageSize: 0;
			$orderBy = "";
			$countNum = $this->offenModel->count($fiterparams); //总数
			$couponList= $this->offenModel->getList("*",$fiterparams,$offset,$pageSize,$orderBy) ;
        	$couponListData = array("count"=>$countNum , "coupons"=>$couponList );
        	$pagedata['coupon_offentype'] = true; //标识为线下券类别
        }else{
        	//取线上的券
        	$couponListData = app::get('topc')->rpcCall('user.coupon.list', $params, 'buyer');
        }
        $count = $couponListData['count'];
        $couponList = $couponListData['coupons'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topc_ctl_member_coupon@couponList',$filter),
            'current'=>$current,
            'total'=>$total,
            'token'=>$filter['pages'],
        );
        $pagedata['couponList']= $couponList;
        $pagedata['count'] = $count;
        $pagedata['action'] = 'topc_ctl_member_coupon@couponList';
        $this->action_view = "coupon/list.html";
        return $this->output($pagedata);
    }

}
