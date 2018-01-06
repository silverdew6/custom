<?php
class sysuser_api_offenpclist {

    /**
     * 接口作用说明
     */
    public $apiDescription = 'PC端获取已核销券码信息';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        //接口传入的参数
        $return['params'] = array(
            'activeCode' => ['type'=>'string','valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'提供活动编号'],
        );
        return $return;
    }
    /**
     * ERP批量更新优惠（核销券码）
     */
    public function offenPclist($apiData)
    {
    	$userOffenAdl = kernel::single('sysuser_data_user_offen');
    	$cpact_code = isset($apiData['activeCode']) ? trim($apiData['activeCode']) : false;
    	$param = false ;
    	$return_sult = array("message"=> "SUCCESS" ,"returnData"=> 1,"msg"=> "操作成功");
		try{
	    	//活动对象
	    	$allnum = 0;
	    	trim($cpact_code)!=""  and $param =array("active_code|has"=> $cpact_code) ; //查询条件 ；
	    	$result = $userOffenAdl -> selectOffenList( $param , $allnum);
    		if($result){
    			$returndata = array("totalNum"=> $allnum , "Data"=> $result);
    			$allnum > 0 and $return_sult["returnData"] = json_encode($returndata);
    		}else{
    			throw new \LogicException('没有更新数据');
    		}
		 }catch(Exception $e) {
	           	$mssg =  $e->getMessage() ;
	            $return_sult = array("message"=> "FAIL" ,"returnData"=> 0,"msg"=> $mssg);
	     }
    	return $return_sult ;
    }
    
}
