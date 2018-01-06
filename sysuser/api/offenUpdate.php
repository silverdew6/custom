<?php
class sysuser_api_offenUpdate {

    /**
     * 接口作用说明
     */
    public $apiDescription = 'ERP批量更新优惠（核销券码）';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        //接口传入的参数
        $return['params'] = array(
        	'datatype' => ['type'=>'string','valid'=>'', 'default'=>'dottom', 'example'=>'', 'description'=>'数据类型'],
            'couponCode' => ['type'=>'string','valid'=>'required', 'default'=>'', 'example'=>'', 'description'=>'活动编辑必填'],
            'type' => ['type'=>'number','valid'=>'required', 'default'=>'0', 'example'=>'', 'description'=>'操作类型必填（1|2）'],
        );
        return $return;
    }
    /**
     * ERP批量更新优惠（核销券码）
     */
    public function offenUpdate($apiData)
    {
    	$userOffenAdl = kernel::single('sysuser_data_user_offen');
    	$cpList_str = isset($apiData['couponCode']) ? trim($apiData['couponCode']) : false;
    	//传的数据 是用逗号隔开的  
    	$dtype  = isset($apiData['datatype']) ? trim($apiData['datatype']) : "dottom"; //默认用json格式
    	$handtype  = isset($apiData['type']) ? intval($apiData['type']) : 1; //默认核验1
    	$return_sult = array("message"=> "SUCCESS" ,"returnData"=> 1,"msg"=> "操作成功");
    	//数据采用分隔符号 
    	$codeList = $cpList_str ?  explode(",",$cpList_str) : false ;
    	if($cpList_str && isset($dtype) && strtoupper($dtype) == "JSON"){
    		$codeList =json_decode($cpList_str) ?  (array)json_decode($cpList_str)  :  false;	
    	}
	

		try{
			$handtype=1;
	    	//活动对象
	    	if($codeList && !empty($codeList)){
	    		$allnum = 0;
		    	$result = $userOffenAdl -> useOffen($codeList , $handtype , $allnum );
	    		if($result){
	    			$allnum > 0 and $return_sult["returnData"] = $allnum ;
	    		}else{
	    			throw new \LogicException('没有更新数据');
	    		}
	    	}else{
	    		throw new \LogicException('券码参数异常');
	    	}
		 }catch(Exception $e) {
	           	$mssg =  $e->getMessage() ;
	            $return_sult = array("message"=> "FAIL" ,"returnData"=> 0,"msg"=> $mssg);
	     }
    	return $return_sult ;
    }
    
    /**
     * //
		    	$result = $userOffenAdl -> selectOffenList($cpList_str);
		    	print_r($result);
		    	exit;
     */
    
}
