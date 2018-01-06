<?php
class topm_ctl_member_coupon extends topm_ctl_member{

    // 优惠券列表
    public function couponList()
    {
        $filter = input::get();
        if(!$filter['pages'])
        {
             $filter['pages'] = 1;
        } 
        $pagedata['title'] = "我的优惠券"; 
        //线下优惠券
        if(isset($filter['is_valid']) && !empty($filter['is_valid']) && trim($filter['is_valid']) == "offen" ){
        	$objMdlUserCoupon = app::get('sysuser')->model('cpoffen');
        	$filter = array("user_id"=> userAuth::id() , "shop_id"=>100000);
        	
        	$couponList = $objMdlUserCoupon->getList("*", $filter ); //所有券码；
        	$keyiList =  array();//有效的券
        	if($couponList){
        		foreach($couponList as $k => $mmv){
        			$is_valid=1; //有效
        			//优惠券的最终状态； is_valid ＝0 已使用，2已过期间
        			if(isset($mmv["offen_status"]) && intval($mmv["offen_status"])==1){
        				$is_valid = 0 ; //已使用
        				$couponList[$k]['offen_date'] = $mmv["offen_date"];
        			}
        			//是否在有效时间内；
        			$isfail = time() >intval($mmv["use_endtime"]) ? false : true ;  
        			if(!$isfail ){
        				$is_valid = 2 ; //已过期 作废        				
        			}
        			$couponList[$k]['is_valid']=$is_valid; //最终状态；
        			$couponList[$k]["qrc_path"] = "/upload/qrcpng/".$mmv["coupon_code"].".png";
        			$couponList[$k]["coupon_desc"]="精茂城指定门店使用，每单限用一张券，活动解释权归精茂所有。";
        			
        			//把有效的取出来
        			if(intval($is_valid) == 1){
			        	 $keyiList[] = $couponList[$k];
			        	 unset($couponList[$k]); 
        			}
        		}
        		if($keyiList && !empty($keyiList)){ //组合 ,把未使用的排在最前面        		
	        		$couponList = array_merge($keyiList ,$couponList);
	        	}
        		$pagedata['couponList']= $couponList; //最后的有效的券
        	}
        	$pagedata['count'] = $objMdlUserCoupon->count($filter );
        	return $this->page('topm/member/coupon/offen_index.html',$pagedata);
        }else{
        	//线下优惠券，不区分类，默认把所有券都取出来，分页 
        	//排序规则，先排 未使用的，再排已使用的，最后排过期的
        	switch ($filter['is_valid']) {
	            case 'no':
	                $filter['is_valid']=1;
	                break;
	            case 'al':
	                $filter['is_valid']=0;
	                break;
	            case 'ex':
	                $filter['is_valid']=2;
	                break;
	            default:
	                //$filter['is_valid']=1;
	                break;
	        }
        }
        $pageSize = 10;
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => $pageSize,
            'fields' =>'*',
            'user_id'=>userAuth::id(),
            'orderBy' => "is_valid ASC,obtain_time DESC" //排序规则
            
        );
        if(isset($filter['is_valid']) && trim($filter['is_valid']) !==""){ //添加状态
        	$params["is_valid"] =  intval($filter['is_valid']) ;
        }
        $couponListData = app::get('topm')->rpcCall('user.coupon.list', $params, 'buyer');
        $count = $couponListData['count'];
        $couponList = $couponListData['coupons'];
        $keyiList =  array();
       if($couponList){
        	foreach($couponList as $vk=> $cuop){
        		if(isset($cuop['is_valid']) && intval($cuop['is_valid']) == 1){
		        	 $keyiList[] = $cuop;
		        	 unset($couponList[$vk]); 
        		}
        	}
        	if($keyiList && !empty($keyiList)){ //组合 ,把未使用的排在最前面        		
        		$couponList = array_merge($keyiList ,$couponList);
        	}
        }

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topm_ctl_member_coupon@couponList',$filter),
            'current'=>$current,
            'total'=>$total,
            'token'=>$filter['pages'],
        );
        $pagedata['couponList']= $couponList;
        $pagedata['count'] = $count;
        $pagedata['action'] = 'topm_ctl_member_coupon@couponList';
        // return $this->page('topm/member/couponlist.html',$pagedata);
        return $this->page('topm/member/coupon/index.html',$pagedata);
    }

    public function ajaxCouponData()
    {
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = 10;
        $params = array(
            'page_no' => $pageSize*($filter['pages']-1),
            'page_size' => $pageSize,
            'fields' =>'*',
            'user_id'=>userAuth::id(),
        );
        $couponListData = app::get('topm')->rpcCall('user.coupon.list', $params, 'buyer');

        $count = $couponListData['count'];
        $couponList = $couponListData['coupons'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topm_ctl_member_coupon@couponList',$filter),
            'current'=>$current,
            'total'=>$total,
            'token'=>$filter['pages'],
        );
        $pagedata['couponList']= $couponList;
        $pagedata['count'] = $count;
        $pagedata['action'] = 'topm_ctl_member_coupon@couponList';


        if( input::get('json') )
        {
            $data['html'] = view::make('topm/member/coupon/list.html',$pagedata)->render();
            $data['pagers'] = $pagedata['pagers'];
            $data['success'] = true;
            return response::json($data);exit;
        }

        return view::make('topm/member/coupon/list.html',$pagedata);
    }

    public function couponDetail()
    {
        $coupon_id = input::get('coupon_id');
        $pagedata['couponInfo'] = app::get('topm')->rpcCall('promotion.coupon.get', array('coupon_id'=>$coupon_id));
        // 获取会员等级名称
        $validGrade = explode(',', $pagedata['couponInfo']['valid_grade']);
        $gradeList = app::get('topm')->rpcCall('user.grade.list', array(), 'buyer');
        $gradeNameArr = array_bind_key($gradeList, 'grade_id');
        $validGradeNameArr = array();
        foreach($validGrade as $v)
        {
            $validGradeNameArr[] = $gradeNameArr[$v]['grade_name'];
        }
        $pagedata['couponInfo']['valid_grade_name'] = implode(',', $validGradeNameArr);
        return $this->page('topm/member/coupon/couponDetail.html', $pagedata);
    }

    // 删除用户领取的优惠券
    /*public function deleteCoupon()
    {
        $postCouponCodes = input::get('coupon_code');
        foreach ($postCouponCodes as $code)
        {
            $params = array('coupon_code' => $code);
            $res = app::get('topm')->rpcCall('user.coupon.remove', $params, 'buyer');
            if( $res === false )
            {
                $msg = app::get('topm')->_('删除优惠券失败');
                return $this->splash('error', null, $msg, true);
            }
        }
        return $this->splash('success',null,  $msg, true);
    }*/

}
