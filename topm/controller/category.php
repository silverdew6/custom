<?php
class topm_ctl_category extends topm_controller{


    public function __construct()
    {
        parent::__construct();
        $this->setLayoutFlag('topics');
    }
    
    /*
     * 获取当前所有第三级的数据；
     */
    function _getThreeList($cate_id=0){
    	$catePicObj = array();
        $objMdlCat = app::get('syscategory')->model('cat');
        $filter = array("level"=> 3);
        $cate_id and $filter["cat_id"] = $cate_id;
    	$catList = $objMdlCat->getList("cat_id,addon", $filter );
    	foreach($catList as $k => $currentCat ){
    		$ca_id = isset($currentCat["cat_id"])?intval($currentCat["cat_id"]) : 0;
    		if($ca_id && isset($currentCat["addon"]) && !empty($currentCat["addon"])){
				$dommObj =(array)json_decode(trim($currentCat["addon"]));
				if($dommObj && !empty($dommObj)){
					$currentCat["adv_pic"] =  isset($dommObj["adv_pic"])? trim($dommObj["adv_pic"]):"";
					$currentCat["adv_link"] =  isset($dommObj["adv_link"])? trim($dommObj["adv_link"]):"";
					$currentCat["default_sett"] =  isset($dommObj["default_goods_id"])? (array) $dommObj["default_goods_id"]: false;
					unset($currentCat["addon"]);
				}
			}else{
				$currentCat = false;
			}
    		$currentCat and $catePicObj[$ca_id] = $currentCat;
    		unset($currentCat);
    	}
    	return $catePicObj ? $catePicObj : false;
    }

    //一级类目页
    public function index()
    {
        $catList = app::get('topm')->rpcCall('category.cat.get.list',array('fields'=>'cat_id,cat_name,addon'));
        //图片
        $allSetting = $this->_getThreeList();
        $pagedata['data'] = $catList;
        $pagedata['all_setting_data'] = $allSetting ? $allSetting :false;
        $pagedata['title'] = "商品分类";
        return $this->page('topm/category/category.html',$pagedata);
    }

    //二三级类目页
    public function catList()
    {
        $catId = input::get('cat_id');
        $catInfo = app::get('topm')->rpcCall('category.cat.get',array('cat_id'=>$catId,'fields'=>'cat_id,cat_name'));
        $pagedata['data'] = $catInfo[$catId];
        $pagedata['title'] = "商品分类";
        return $this->page('topm/category/catlistinfo.html',$pagedata);
    }
}
