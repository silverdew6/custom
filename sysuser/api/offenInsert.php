<?php
class sysuser_api_offenInsert {

    /**
     * 接口作用说明
     */
    public $apiDescription = 'ERP批量发布优惠券操作';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        //接口传入的参数
        $return['params'] = array(
            'couponList' => ['type'=>'string','valid'=>'required', 'default'=>'', 'example'=>'', 'description'=>'活动编辑必填'],
        );
        return $return;
    }
    public function offenInsert($apiData)
    {
    	$is_erp = isset($apiData['erp_api']) && intval($apiData['erp_api'])==1 ? true :false;
    	$cpList_str = isset($apiData['couponList']) ? trim($apiData['couponList']) : false;
    	$cpList_str and $cpList_str =json_decode($cpList_str) ?  (array)json_decode($cpList_str)  :  false;
    	
    	$cpList_firstobj  = ($cpList_str && is_array($cpList_str)) ? (array) $cpList_str[0] : false; 
    	$userOffenAdl = kernel::single('sysuser_data_user_offen');
    	$return_sult = array("message"=> "SUCCESS" ,"returnData"=> 1,"msg"=>"操作成功");
		try{
	    	//活动对象
	    	$aciveObj = false;
	    	$is_array  = false;
	    	if($cpList_str && isset($cpList_str["activeCode"]) && !empty($cpList_str["activeCode"]) ){
	    		$aciveObj = $cpList_str; $is_array  = true ; //只保存一个
	    	}else{
	    		$aciveObj = (array)reset($cpList_str); 			//同时多个
	    	}
	    	if($aciveObj && !empty($aciveObj["activeCode"]) ){
	    		$num = 0;
	    		$result = $userOffenAdl -> saveOffenList($cpList_str , $is_array ,$num);
	    		$num >0 and $return_sult["returnData"] = $num ;
	    		if(!$result){
	    			throw new \LogicException('更新完成，但没有生成优惠券');
	    		}
	    	}else{
	    		throw new \LogicException('活动参数异常');
	    	}
		 }catch(Exception $e) {
	            $mssg =  $e->getMessage() ;
	            $return_sult = array("message"=> "FAIL" ,"returnData"=>0,"msg"=> $mssg);
	     }
    	return $return_sult ;
    }
}
