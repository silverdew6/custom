<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2014-2014 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class sysuser_data_user_card
{
	public $offenModel = false;
	public $defaut_couponDesc;
	function __construct(){
		$this->offenModel = app::get('sysuser')->model('card');
		$this->defaut_couponDesc = "精茂城用户身份实名认证";
	}

	//查询身份信息
	public function getcard($postdata){
	    $name=isset($postdata['name']) ? trim($postdata['name']) : "";
        $card_id=isset($postdata['card_id']) ? trim($postdata['card_id']) : "";
        if(!empty($name)){
            $res = $this->offenModel->getList('card_id',['name'=>$name]);
            if($res[0]['card_id']){
                return $res[0]['card_id'];
            }else{
                return false;
            }
        }
    }
	//身份证添加
    public function create($postdata){
        $arrc=[];
        $name=isset($postdata['name']) ? trim($postdata['name']) : "";
        $card_id=isset($postdata['card_id']) ? trim($postdata['card_id']) : "";
        $arrc['name']=$name;
        $arrc['card_id']=$card_id;

        if($arrc && $this->offenModel->insert($arrc)){
                return true;
        }
        return false;
    }

}
