<?php
class topc_ctl_topic extends topc_controller{
	
	
	/**
	 * 每页搜索多少个商品
	 */
	public $limit = 20;
	
	/**
	 * 最多搜索前100页的商品
	 */
	public $maxPages = 100;
	
	

    public function __construct($app)
    {
        parent::__construct();
        //$this->setLayoutFlag('item');
    }
	/**
	 * 自定义专题页面定义； 使用格式有：
	 * 1、 (/wap)/topic-（专题名称）.html  （优先使用这个）
	 * 2、 (/wap)/topic/（专题名称）.html
	 * 3、 (/wap)/topic/index.html?tname=专题名称（如 ： lingshi_m）
	 * 说明：
	 * 	  模板目录位置：
	 * 		PC端－  topc/view/common/topic/（下面放置手机端的专题页面）
	 * 	    WAP端－	topm/view/common/topic/（下面放置手机端的专题页面）
	 */
    function index($tName){
    	$getdata = input::get();
    	if(!isset($tName) || trim($tName)==""){
    		$tName = isset($getdata["tname"])? trim($getdata["tname"]): "index"; //默认用index
    	}
    	/*自定义的专题 暂时不用获取动态产品数据；到时需要时再添加*/
    	$pagedata["template_name"]= $tName;
        $pagedata['site_url'] = url::route('topm'); //根目录；
    	switch ( $tName ) {
			case "index":　break;
			
			default: break;
		}
        /* 选择相对应的产品模板；位置   */
        $template_dir = "topc/common/topic/{$tName}.html";
        //调用 模板        
        return $this->page($template_dir, $pagedata);
    }
    /**
     * PC端首页显示的分类产品Ajax挂件数据
     * 根据分类获取相关的产品列表（默认6个产品）
     */
    function ajaxCateGoods(){
    	$cate_id = input::get("catId"); //获取产品分类的ID；
    	$orderBy="";
    	$pagedata["cate_id"]=$cate_id;
    	$commfilter = array('disabled'=>0,'approve_status'=>'onsale','fields'=>'item_id,shop_id,cat_id,title,price,image_default_id,tax,sea_region','orderBy'=>$orderBy,'page_size'=>6,'platform'  =>'pc');
    	if($cate_id && intval($cate_id)>0){
    		$cateinfo = app::get('topm')->rpcCall('category.cat.get.info', array("cat_id"=> $cate_id)); //获取当前分类名称；
			$cateinfo and $cateinfo = reset($cateinfo);
			
    		$levels = isset($cateinfo["level"])? intval($cateinfo["level"]): false;
    		switch ( $levels ) {
				case 1:
				case 2:	$commfilter["class"] = ($levels==2) ? "two" : "one";break;
				default: break;
			}
    		$commfilter["cat_id"] = intval($cate_id);
			$goodslist = app::get('topm')->rpcCall('item.search', $commfilter);
    		if($goodslist && !empty($goodslist) && count($goodslist)>0){						 
				$lastgoodslist = isset($goodslist["list"]) ? $goodslist["list"] :false;  //主分类下面的6首先展示出来；
				if($lastgoodslist){
					foreach($lastgoodslist as $k=> $vlg){
						$lastgoodslist[$k]["title_length"]=  isset($vlg["title"]) ? $this->_utf8_strlen($vlg["title"]):0;
					}
				}
				$pagedata["search_goodslist"]= $lastgoodslist;
			}
    	}
    	//return view::make('topc/common/index_goodslist.html', $pagedata);
    	return  view::make('topc/common/index_goodslist.html', $pagedata)->render();
    }
    
    // 计算中文字符串长度
    function _utf8_strlen($string = null){
		preg_match_all("/./us",$string, $match);// 将字符串分解为单元
		return count($match[0]);// 返回单元个数
    }
    
    
    
}
