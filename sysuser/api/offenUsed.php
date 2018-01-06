<?php
class sysuser_api_offenUsed {

    /**
     * 接口作用说明
     */
    public $apiDescription = 'ERP查询单个优惠券状态（核销之前调用）';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        //接口传入的参数
        $return['params'] = array(
            'couponCode' => ['type'=>'string','valid'=>'required', 'default'=>'', 'example'=>'', 'description'=>'活动编辑必填'],
        );
        return $return;
    }
    /**
     * 查询券码状态（核销券码之前调用）
     */
    public function offenUsed($apiData)
    {
    	$userOffenAdl = kernel::single('sysuser_data_user_offen');
    	$cpList_str = isset($apiData['couponCode']) ? trim($apiData['couponCode']) : false;
    	//传的数据 是用逗号隔开的  
    	$cpList_str = explode(",",$cpList_str);
    	if($cpList_str && is_array($cpList_str)){
    		$cpList_str = reset($cpList_str);
    	}
		try{
	    	//活动对象  只能一张券码数据
	    	$return_sult = array("message"=> "SUCCESS" ,"returnData"=>1 ,"msg" => "没有使用"); //默认没有用
	    	if($cpList_str && !empty($cpList_str)){
	    		$allnum = 0; //查询是否已经核验，返回true
		    	$result = $userOffenAdl -> checkOffenStatus($cpList_str);
		    	if($result===true){
		    		$return_sult = array("message"=> "SUCCESS" ,"returnData"=>2,"msg"=> "券码已被使用");
		    	}
	    	}else{
	    		throw new \LogicException('券码参数异常');
	    	}
		 }catch(Exception $e) {
	            $return_sult = array("message"=> "FAIL" ,"returnData"=> 0,"msg"=>$e->getMessage()); //error
	     }
    	return $return_sult ;
    }
}
