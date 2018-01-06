<?php
class sysuser_api_card {

    /**
     * 接口作用说明
     */
    public $apiDescription = '身份证查询';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        //接口传入的参数
        $return['params'] = array(
            'name' => ['type'=>'string','valid'=>'required', 'default'=>'', 'example'=>'', 'description'=>'会员名称'],
            'card_id' => ['type'=>'string', 'default'=>'', 'example'=>'', 'description'=>'身份证号'],
        );

        return $return;
    }

    public function index($apiData)
    {
        $objLibUserAddr =  kernel::single('sysuser_data_user_card');
        return $objLibUserAddr->getcard($apiData);
    }
    public function create($apiData){
        $objLibUserAddr =  kernel::single('sysuser_data_user_card');
        return $objLibUserAddr->create($apiData);

    }
}
