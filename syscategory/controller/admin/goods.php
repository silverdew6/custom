<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2014 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class syscategory_ctl_admin_goods extends desktop_controller {

    public $workground = 'syscategory.workground.category';

    /**
     * 商品管理列表
     */
    public function index()
    {
         $postData = input::get();
         if(isset($postData["filter"]) && !empty($postData["filter"])){
         	$cateId = isset($postData["filter"]["cat_id"]) ? intval($postData["filter"]["cat_id"]):0;
         	$pagedata = array("cat_id"=> $cateId);
         	//获取当前分类下已配置的商品ID或商品信息；
     		$params = array('cat_id'=>$cateId,'fields'=>'cat_id,cat_name,parent_id,addon');
    		$catInfo = app::get('syscategory')->rpcCall('category.cat.get.info',$params);
    		$catInfo and $catInfo = reset($catInfo);
         	if($cateId && $catInfo){
         		$pagedata["cateinfo"] = $catInfo;
         		$setting_config = array("default_goods_id"=>0 , "adv_pic"=>"","adv_link"=>"" );
        		if(isset($catInfo["addon"])&& trim($catInfo["addon"])!=""){
        			 $setting = (array)json_decode(trim($catInfo["addon"])) ; 
        			 $detailObj = isset($setting["default_goods_id"]) ? (array)$setting["default_goods_id"] : false;
        			 $setting_config["default_goods_id"]= isset($detailObj)?intval($detailObj["item_id"]):0;
        			 $setting_config["adv_pic"]= isset($setting["adv_pic"])?trim($setting["adv_pic"]):"";
        			 $setting_config["adv_link"]= isset($setting["adv_link"])?trim($setting["adv_link"]):"";
        			 //获取对应的商品信息；
        			 if($setting_config["default_goods_id"] &&   !empty($detailObj)){
        			 	$setting_config["default_goods_detail"]=$detailObj;
        			 }
        		} 
        		//还没有配置信息；则显示有商品搜索功能；
        		$pagedata["setting"] = $setting_config;
         		return view::make('syscategory/admin/category/setting.html', $pagedata); 
         	}else{
         	 	return $this->splash('error',null,"分类信息不存在");
         	}
         }else{
         	 return $this->splash('error',null,"数据异常");
         }
    }
	
	/**
	 * 保存分类关联数据
	 */
    public function save()
    {
        //$this->begin();
        $saveData = $_POST['addon'];
        $objMdlCat = app::get('syscategory')->model('cat');
        //目录分类ID
        $targetCatId = isset($_POST["edit_cat_id"])? intval($_POST["edit_cat_id"]) : 0;
        if($targetCatId && $saveData && !empty($saveData)) {
        	$catData = array();
        	$defaultItemId = isset($saveData["default_goods_id"]) ? trim($saveData["default_goods_id"]) : 0;
        	//获取当前商品的ID信息
        	$params = array('item_id'=>$defaultItemId,'fields'=>'item_id,shop_id,cat_id,title,price,image_default_id','page_size'=>1,'platform'=>'pc');
			$iteminfo = app::get('topm')->rpcCall('item.list.get', $params);
			if(!$iteminfo || empty($iteminfo)){
				$msg =  app::get('syscategory')->_('没有找到商品');
            	return $this->splash('error',null,$msg);
			}
			$iteminfo = reset($iteminfo);
        	if($iteminfo){
        		$catData["default_goods_id"]=$iteminfo;
        	}else{
        		$catData["default_goods_id"]=false;
        	}
        	//配置广告地址及链接地址；
        	$catData["adv_pic"]=isset($saveData["adv_pic"]) ? trim($saveData["adv_pic"]) : "";
        	$catData["adv_link"]=isset($saveData["adv_link"]) ? trim($saveData["adv_link"]) : "";
        	$updateData = array('addon'=>json_encode($catData));
        	$db = app::get('syscategory')->database();
	        $db->beginTransaction();
	        try
	        {
        		$result  = $objMdlCat -> update($updateData, array("cat_id"=>$targetCatId) );//编辑        	
		        $msg = $result ? app::get('syscategory')->_('配置更新成功') :app::get('syscategory')->_('配置更新失败');
		        $db->commit();
	        }
	        catch(\LogicException $e)
	        {
	            $db->rollback();  throw new \LogicException($e->getMessage());
	            return false;
	        }
            return $this->splash('success',null,$msg);
        }
        else
        {
            $msg =  app::get('syscategory')->_('没有数据要保存');
            return $this->splash('error',null,$msg);
        }
    }

}
