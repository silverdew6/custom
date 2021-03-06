<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2014-2014 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class sysuser_data_user_offen
{
	public $offenModel = false;
	public $defaut_couponDesc;
	function __construct(){
		$this->offenModel = app::get('sysuser')->model('cpoffen');
		$this->defaut_couponDesc = "精茂城指定门店使用，每单限用一张券，活动解释权归精茂所有。";
	}
	
	
	/**
     * 会员中心线下优惠券保存
     * @param string user id
     * @param
     * @param array postdata 不能为空
     * @return boolean true or false
     */
	public function saveOffenList($postdata,$isFirst=false, &$successNum = 0)
	{
		$couponQrcMdl  = kernel::single('topm_couponqrc');
		$couponQrcMdl -> loadingqrcLib(); //加载类
		$user_m_id = 0;
		$userMobile_flag = false; //用户传值传有手机号的过来
		$firstPb =$postdata ? (array)reset($postdata) : false; //取第一个；
		$is_ok1 = (isset($postdata["userMobile"]) && trim($postdata["userMobile"])!="") ? true : false;
		$is_ok2 = ($firstPb && isset($firstPb["userMobile"]) && trim($firstPb["userMobile"])!="") ? true : false;
		if($is_ok1 || $is_ok2){
			$userMobile_flag = trim($postdata["userMobile"])!="" ? trim($postdata["userMobile"]) : $firstPb["userMobile"]; //取手机号
			//新加一个手机号调用信息接口  //user.get.erpinfo
			$userData  =  app::get('topc')->rpcCall('user.get.erpinfo', ['mobile' =>$userMobile_flag], 'buyer');
			 if($userData && isset($userData[0]) && intval($userData[0]["user_id"])>0){ //得到有效用户信息
			 	$user_m_id = $userData[0]["user_id"]; //用户ID
			 }else{
			 	throw new \LogicException(app::get('sysuser')->_('提供的手机号匹配不到用户信息'));
				return false;
			 }
		}
		//JSON解析
		if($isFirst){
			$offenInfo = $this->_getOffenInfo( $postdata ,  $user_m_id);
			if(!($offenInfo && $res = $this->offenModel->insert($offenInfo))){
				throw new \LogicException(app::get('sysuser')->_('优惠券添加失败'));
				return false;
			}$successNum = 1;
		}else{
			//多个使用事来操作
			$db = app::get('sysuser')->database();
	        $db->beginTransaction();
	        try{
				foreach($postdata as $key => $oneOff ){
					$oneOff = $oneOff ?  (array)$oneOff : false; 
					$offenInfo = $this->_getOffenInfo($oneOff,$user_m_id);  
					//添加单个对象
					if($offenInfo && $res = $this->offenModel->insert($offenInfo)){
						$cfile_qrc_path = "";
			        	$cfile_qrcc = $couponQrcMdl -> createQrc($offenInfo["coupon_code"],$cfile_qrc_path,false); 
						$successNum = $successNum + 1 ; //成功一次
					}
				}$db->commit(); //执行完，则提交事事务
	        }
	        catch(\Exception $e)
	        {
	            $db->rollback(); throw $e;
	            return false;
	        }
		}
		return true;
	}
	// one 
	function _getOffenInfo($offinfo , $user_id = 0) {
		if(!$offinfo) return false;
		if(!$offinfo["activeCode"]) return false;
		if(!$offinfo["couponCode"]) return false;
		$couponCode  = trim($offinfo["couponCode"]);
		$is_aready  = $this->offenModel->count(array("coupon_code" => $couponCode));
		//var_dump($is_aready);exit;
		if($is_aready>0) return false;  //已经存在就不再添加
		$sysdata = array(
			'user_id'=> $user_id, //默认为0
			'shopid'=>10000,
			'active_code'=>isset($offinfo["activeCode"]) ? trim($offinfo["activeCode"]) : "",
			'coupon_name'=>isset($offinfo["couponName"]) ? trim($offinfo["couponName"]) : "",
			'coupon_amount'=>isset($offinfo["couponAmount"]) ? floatval($offinfo["couponAmount"]) : 0.0,
			'coupon_code'=>isset($offinfo["couponCode"]) ? trim($offinfo["couponCode"]) : "",
			'coupon_desc'=>!empty($offinfo["couponDesc"]) ? trim($offinfo["couponDesc"]) : $this->defaut_couponDesc,
			'offen_status'=> 0,	//核验状态
			'status'=>0,
			'palt'=>"ERP",
			'vaild'=> 0,		//默认不拉取
			'qrc_path'=>"upload/qrcpng/{$couponCode}.png"
		);
		//开始结束时间
		if(isset($offinfo["sTime"]) && strtotime($offinfo["sTime"]) > 0){
			$sysdata["use_starttime"] =  strtotime($offinfo["sTime"]." 00:00:00");
		}
		if(isset($offinfo["eTime"]) && strtotime($offinfo["eTime"]) > 0){
			$sysdata["use_endtime"] =  strtotime($offinfo["eTime"]." 23:59:59");
		}
		if($user_id >0 ){
			$sysdata["get_time"]=time();
			$sysdata["status"]=1;//已领券
		}
		return $sysdata;		
	}
	
	/**
	 * 更新	优惠券核验状态；
	 * @param $couponCode 券码号，或数组
	 * @param $countNum 更新成功数量
	 */
	function useOffen($couponCode , $handtype = 1,&$countNum = 0){
		if(!$couponCode) return false;
		if(!$handtype || !in_array($handtype ,array(1,2)) ) {
			throw new \LogicException(app::get('sysuser')->_('无效操作类型'));
		}
		$updateParam = (intval($handtype) == 2) ? array("vaild" => 1) : array("offen_status"=>1,"vaild" => 1,"offen_date" => time());
		if($couponCode && is_array($couponCode)){
			//多个券
			foreach($couponCode as $kv => $vcode  ){
				if($vcode && is_string($vcode))	{
					//核销券时，默认把vaild ＝1 默认拉取了检验状态
					$is_ok  = $this->offenModel->update($updateParam,['coupon_code'=>$vcode]);	
					if($is_ok){
						$countNum = $countNum +1;
					}						
				}
			}
		}else if($couponCode && is_string($couponCode)){
			//更新单个
			$countNum = $this->offenModel->update($updateParam,['coupon_code'=>$couponCode]) ;	
		}else{
			throw new \LogicException(app::get('sysuser')->_('优惠券参数为空'));
			return false;
		}
		return true;
	}
	
	
	/**]
	 * 
	 * 查询优惠券的核验状态是否已被核销
	 * 
	 */
	function checkOffenStatus($couponCode ){
		$info= $this->offenModel->getRow("user_id,coupon_code,offen_status",['coupon_code'=>$couponCode]) ;
		if(isset($info) && isset($info["coupon_code"]) && intval($info["user_id"]) > 0){
			
			if(isset($info["offen_status"]) && intval($info["offen_status"]) ==1 ){
				
				return true;//已使用
			}else{
				return false;//已使用
			}
		}else{
			//不存在
			throw new \LogicException('券码无效');
		}
		return false;
	}
	
	/**
	 * 
	 * 查询网页端被核销的优惠券列表
	 * 状态控制：  user_id >0 
	 * status =1 
	 * offen_status != 1  
	 * palt = PC  
	 * vaild != 1
	 */
	function selectOffenList($params , &$countNum = 0 ){
		//状态控制： user_id >0 status =1 offen_status != 1  palt = PC  vaild != 1
		$fiterparams = array("user_id|than"=> "0","status"=> 1 ,"offen_status"=>0,"palt"=> "PC","vaild|noequal"=>1); //lthan 小于，than大于，
		if($params && !empty($params) ) $fiterparams = array_merge($fiterparams,$params); //组合
		//查询PC端没有拉取的数据
		$countNum = $this->offenModel->count($fiterparams); //总数
		$couponList= $this->offenModel->getList("cid,user_id,active_code,coupon_code,offen_status,status,palt,vaild",$fiterparams) ;
		if(isset($couponList) && !empty($couponList)){
			$coupon_cids = array();//所有券码
			$couponRe_List = array();//所有券码
			foreach($couponList as $kk => $value ){
				$cno = isset($value["coupon_code"]) ? $value["coupon_code"] : false;
				if($cno){
					intval($value["cid"]) > 0 and  $coupon_cids[] = intval($value["cid"]) ;
					$couponRe_List[] = array("userId"=> $value["user_id"],"couponCode"=> $cno,"activeCode"=> trim($value["active_code"]) );
				}
			}
			if($couponRe_List && count($couponRe_List)>0){
				$filter = array("cid|in"=>$coupon_cids);
				$rest = $this->offenModel->update(array("vaild" =>2), $filter);
				return $couponRe_List;
			}
		}
		return false;
	}
	
	
	/**
	 * 查询券码；每次取20张券码
	 * 
	 * 查询字段：$offset, $rowNum, 'logtime DESC'
	 */
	 function __get_rand_cuplist($fiterparams, $active_NO = ""){
	 	if(isset($fiterparams["active_code"])){
	 		$active_NO =$active_NO &&  trim($active_NO)!="" ? $active_NO : $fiterparams["active_code"];
	 		unset($fiterparams["active_code"]);
	 	}
	 	$couponList = array();
	 	//取某个活动的券；
	 	if($active_NO && trim($active_NO)!=""){
	 		$fiter = array_merge($fiterparams,array("active_code" => $active_NO));
	 		$couponList= $this->offenModel->getList("cid",$fiter,0,100);	 		
	 	}else{
	 		$act_codes = array("CXD1609285100000","CXD1609280100001","CXD1609282100002"); //3种现金券
	 		foreach($act_codes as $v){
	 			if($v && trim($v)!=""){
	 				$fiter = array_merge($fiterparams,array("active_code" => $v));
	 				$coupon= $this->offenModel->getList("cid",$fiter,0,30);	
	 				$coupon and  $couponList = array_merge($coupon,$couponList);
	 			}
	 		}
	 	}
	 	return !empty($couponList) ? $couponList : false;
	 }	 
	
	/**
	 * 把当前活动的券码发出来
	 * 
	 */
	function _activeCoupon($user_id,$active_No=false){
		$cache_key = "already_coupons";		
	 	if(!$user_id)return false; 	
		//缓存现在正在用的所有券
	 	$all_CouponSn = cache::store('vcode')->get($cache_key); 	//缓存中取所有券码
	 	if(!$all_CouponSn) { $all_CouponSn = array();  }
	 	$is_sucess = false ; 		//是否重复
	 	$last_selectCid = false; //最后得到的券码	
	 	//随机获取一张券码；
	 	do{
	 		//判断是否已经有可以领取的券码；
		 	$fiterparams = array("user_id"=>0);
		 	if($active_No && trim($active_No)!=""){
		 		$fiterparams["active_code"] = $active_No;
		 	}
		 	$countNum = $this->offenModel->count($fiterparams); //总数		 	
		 	//单个活动，还存在有券时；
		 	if($countNum>0){
		 		$couponIds = array();
		 		$couponList= $this->__get_rand_cuplist($fiterparams,$active_No); //取出100张券码来随机取券号
		 		if($couponList){
		 			foreach($couponList as $k=> $vkd ){
		 				$cid = isset($vkd["cid"]) ? intval($vkd["cid"]) : 0;
		 				$cid > 0 and $couponIds[$cid]= $cid; //所有券ID组合
		 			}
		 		}
		 		//随机取一个
		 		$last_selectCid = (isset($couponIds) && count($couponIds)>1 )  ?  array_rand($couponIds,1) : reset($couponIds) ; //随机一个ID元素
		 				 		
		 		//判断这个券码是否被锁定在缓存中
		 		if($last_selectCid && in_array($last_selectCid ,$all_CouponSn)){
		 			$is_sucess = true; 	//券码被锁定了，需要重新挑券码
		 		}else if($last_selectCid){
		 			//挑券成功; 把券加到缓存中去；并把券就发给对应的用户；
			 		$all_CouponSn[]  = $last_selectCid;
			 		cache::store('vcode')->put($cache_key, false ,-1); //清空缓存	  
			 		cache::store('vcode')->put($cache_key, $all_CouponSn ,3600*24 ); //永久缓存所有的已选择ID， 新加一个ID，
			 		
			 		//关联券用户  －－－〉更新券的使用人
		 			$data_2 = array("user_id"=>$user_id,"status"=>1,"get_time"=> time());
		 			$ress = $this->offenModel->update($data_2,array("cid"=>$last_selectCid));
		 			
		 			//取实时缓存,并清除当前ID
	 				$all_CSn = cache::store('vcode')->get($cache_key);//缓存中取所有券码 by xch 
		 			if($all_CSn) {
		 				$all_NewSn =array();
			 			foreach($all_CSn as $newsn){
			 				// 把其它在用的ID，给重新缓存起来
			 				if(isset($newsn) && intval($newsn) > 0  && intval($newsn) != intval($last_selectCid)){
			 					$all_NewSn[] =  intval($newsn); //新的券码存入缓存；
			 				}			 				
			 			}
			 			if($all_NewSn && count($all_NewSn) >0){
			 				cache::store('vcode')->put($cache_key, false ,-1); 				//清空缓存	  
			 				cache::store('vcode')->put($cache_key, $all_NewSn ,3600*24 ); //永久缓存所有的已选择ID， 新加一个ID，
			 			}
			 		}
		 		}
		 	}else{ 
		 		//没有券，break
		 		$last_selectCid = false;	 		
		 	}
	 		
	 	}while($is_sucess);
	 	//返回 当前选择的ID
		return $last_selectCid ? $last_selectCid : false ;
	}
	
	/**
	 * 
	 * 用户领取优惠券的新流程
	 * 
	 * $is_exception 返回异常信息，false 异常不提示，返回false
	 */	 
	 function sendCouponTo($user_id , $data = false,$is_exception =true ){
	 	//获取所有的活动编号；( 给每一个活动都发一张券码  )
	 	$active_No = "";//活动编号
	 	$rsult = $this-> _activeCoupon($user_id , false);
	 	//没有就直接返回false	
		return $rsult ;
	 }
	 
	 	 
	

}
