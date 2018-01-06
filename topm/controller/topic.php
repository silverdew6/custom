<?php
use Endroid\QrCode\QrCode;
class topm_ctl_topic extends topm_controller{
	
	
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
        $this->setLayout('default.html'); //默认的layout页面
    }
	/**
	 * 专题页面定义 ；； 使用格式有：
	 * 1、 /wap/topic-（专题名称）.html  （优先使用这个）
	 * 2、 /wap/topic/（专题名称）.html
	 * 3、 /wap/topic/index.html?tname=专题名称（如 ： lingshi_m）
	 * 	  说明： 模板目录位置：topm/common/topic/（下面放置手机端的专题页面）
	 */
    function index($tName){
    	$getdata = input::get();
    	if(!isset($tName) || trim($tName)==""){
    		$tName = isset($getdata["tname"])? trim($getdata["tname"]): "index"; //默认用index
    	}
    	//特殊处理情况； list 表示进入立屏模式；
    	if($tName=="list"){
    		$cid = isset($getdata["cid"])?intval($getdata["cid"]) : 781;
    		if($cid==10000){ //在线客服
    			$this->setLayout('lpheader.html');
    			$pagedata["topnav_list"]=$this->__getDefaultNavs(); //保存当前ID值；
		        return $this->page("topm/common/lp_search_kefu.html",$pagedata);
    		}
    		return $this->lpsearch($cid); 
    	}
    	/*自定义的专题 暂时不用获取动态产品数据；到时需要时再添加*/
    	$pagedata["template_name"]= $tName;
        $pagedata['site_url'] = url::route('topm'); //根目录；
		$templateFileName = $this->__checkContentPage($getdata,$pagedata,$tName);
		if(!$templateFileName|| trim($templateFileName)==""){
			$templateFileName = "common/topic";//默认为专题
		}
        /* 选择相对应的产品模板；位置   */
        $template_dir = "topm/{$templateFileName}/{$tName}.html";
        
        //是否存在
        
        //调用 模板        
        return $this->page($template_dir, $pagedata);
    }
    
    /**
     * 新的内页系统
     */
    function __checkContentPage($getdata,&$pagedata,$tName="index"){
    	if(isset($getdata["to"]) && !empty($getdata["to"]) && trim($getdata["to"]) !="" && trim($getdata["to"]) !="topic"){
    		$cType = isset($getdata["to"])? trim($getdata["to"]): "default"; //默认用default
    		trim($tName)!="" and $tName=isset($getdata["tname"])? trim($getdata["tname"]):"index";
    		$pagedata["title"]="Wap版内面标题";
    		//不同内容页的逻辑处理；
    		return "common/content";
    	}else{
    		switch ( $tName ) {
				case "index":　break;
				default: break;
			}
			//返回的模板位置是默认 专题的    		
    		return "common/topic";
    	}
    }
    
    
    
    /**
     * 大立屏新版本页面
     * 1、主产品（大分类展示多个子类的商品类页）  左右拉加载
     * 
     * 2、二级分类所有产品（第二级以及第三级分类的所有商品）  下拉
     * 
     * 3、单个产品详细页面（只显示产品名称及详细内容描述） 右边固定一个二维码图。
     * 
     * 搜索方式有3种： cate  keyword   both (同时分类和关键字)
     * themes/demo/images/jmall_loading.png
     */
	function lpsearch($getCatId=0){
		$this->setLayout('lpheader.html');
		$defaultPic = app::get('image')->getConf('image.set');
		//获取一个分类
		if(!$getCatId || intval($getCatId)<= 0){
			$getCatId = input::get("cid");
		}
		$filter_param = array("next_page"=>2);//下一页的
		$getitemId = input::get("search_goods_id");
		$getKeyword = input::get("search_keywords");
		$getShopId = input::get("search_shop_id");
		$deault_pageFile = "topm/common/lp_search_list.html";
		$class_levals = array(1=>"one",2=> "two");
		$class_current_f = "";
		//保存上级参数过来
		if($getCatId){
			$filter_param["cid"] = $getCatId;
			$pagedata["current_default_id"]=$getCatId; //保存当前ID值；(当ID是一级目录时，保存当前值)
		}
		$getKeyword and $filter_param["search_keywords"] = $getKeyword;
		$getitemId and $filter_param["search_goods_id"] = $getitemId;
		$getShopId and $filter_param["search_shop_id"] = $getitemId;
		
		$thisCatObj = array("type"=>"cate","keywords"=>$getKeyword,"cateId"=>$getCatId );  //默认按关键字来搜索结果
		if(isset($getCatId) && !empty($getCatId) && intval($getCatId)>0 ){
			//是否同时有关键字
			if(isset($getKeyword) && trim($getKeyword)!=""){ $thisCatObj["type"]="both"; }
			$cateinfo = app::get('topm')->rpcCall('category.cat.get.info', array("cat_id"=> $getCatId)); //获取当前分类名称；
			//分类存在就取分类下面的所有子分类结构出来
			if(isset($cateinfo) && !empty($cateinfo) && count($cateinfo)>0){
				$currentCat = reset($cateinfo); //取当前类名；
				//获取所有分类 category.cat.get.list ；  ===  获取一级类目下的所有分类category.cat.get    
				$parentObj = app::get('topm')->rpcCall('category.cat.get.info', array("parent_id"=> $getCatId)); //获取当前分类列表
				if($currentCat && intval($currentCat["level"])==1 && $getCatId == 797 ){
					$othercat = app::get('topm')->rpcCall('category.cat.get.info', array("parent_id"=>814)); //护肤中把个护给加进来。
					$parentObj = array_merge($parentObj,$othercat);
					$parentObj = array_bind_key($parentObj,"cat_id");
				}else if($currentCat && intval($currentCat["level"])>=2){
					$parent_Id = isset($currentCat["parent_id"]) ? intval($currentCat["parent_id"]) :0;
					if($parent_Id > 0 && intval($currentCat["level"])==3){
						$threeCat = app::get('topm')->rpcCall('category.cat.get.info', array("cat_id"=> $parent_Id)); //获取当前分类名称；
						$threeCat and $threeCat = reset($threeCat);
						$threeCat and $parent_Id = $threeCat["parent_id"];
					}
					$pagedata["current_default_id"]=$parent_Id; //保存当前ID值；(当ID是二或3级目录时，保存父级)
				}
				if(isset($currentCat) && $currentCat && $parentObj && count($parentObj)>0  ){
					foreach($parentObj as $ka => $pva){
						if(true){
							$parentObj[$ka]["cat_smallpic"]= "http://www.gojmall.com/themes/demo/images/cates/".$pva["cat_id"].".jpg";
						}
						if($ka && intval($ka)==830){unset($parentObj[$ka]);}
					}
					$currentCat["childs"] = $parentObj;
				}
				$thisCatObj["info"] = $currentCat;
			} 
		}else if(isset($getKeyword) && trim($getKeyword)!=""){
			$thisCatObj["type"]="keyword";
		}else if(isset($getitemId) && intval($getitemId)>0){
			$thisCatObj["type"]="goods_id";
		}else if(isset($getShopId) && intval($getShopId)>0){
			$thisCatObj["type"]="shop_id";
		}else{
			$thisCatObj["type"]="all";
			$thisCatObj["info"]=array("cat_name"=>"所有商品");
		}
		$orderBy = "order_sort DESC";
		$commfilter = array('disabled'=>0,'approve_status'=>'onsale','class'=>$class_current_f,'fields'=>'item_id,shop_id,cat_id,title,price,image_default_id,tax,sea_region','orderBy'=>$orderBy,'page_size'=>6,'platform'  =>'wap');
		if(isset($thisCatObj) && (intval($getCatId)>0 || intval($getShopId)>0 || trim($getKeyword)!="" || trim($getitemId) !="" || $thisCatObj["type"]=="all" )){
			//通过分类,关键字、商品ID，以及店铺ID来获取所有产品。
			switch(strtoupper($thisCatObj["type"])){
				case "CATE": $commfilter["cat_id"] = intval($getCatId);break; 
				case "KEYWORD": $commfilter["search_keywords"] = $getKeyword ;break;
				case "GOODS_ID": $commfilter["item_id"] = $getitemId ;break;
				case "SHOP_ID": $commfilter["shop_id"] = $getShopId ;break;
				case "BOTH":$commfilter["cat_id"] = $getCatId ;$commfilter["search_keywords"] = $getKeyword ;break;
				default :$commfilter["disabled"]=0; break;
			}
			//对于首面，且为(LEVEL)一级分类来说，加载一次4*6个产品  
			if(isset($thisCatObj["info"]) &&  intval($thisCatObj["info"]["level"])== 1 && strtoupper($thisCatObj["type"])=="CATE"){
				//调用 首页楼层莫模板页面    －－－－ 否则调用搜索页面 
				$deault_pageFile = "topm/common/lp_search_index.html";
				$commfilter["page_size"]= 24; //取每个子分类的前24个产品；
				$productlist = array();
				//分别取四个分类下的前24个产品；
				//print_r($thisCatObj);
				$total_number = 0;
				foreach($thisCatObj["info"]["childs"] as $kcuId => $cated ){
					if($cated && intval($cated)>0){
						$commfilter["cat_id"] = intval($kcuId);
						$commfilter["class"] = intval($cated["level"])==2 ? "two" : "";
						$plist =  app::get('topm')->rpcCall('item.search', $commfilter);
						
						//获取二维码的操作；
						if(isset($plist["list"]) && intval($plist["total_found"])>0){
							foreach($plist["list"] as $kv=> $vdd){
								$plist["list"][$kv]["wap_qrcpath"]= $this->__getGoodsQrcPath($vdd["item_id"]); //二维码图片地址；
							}
						}
						$productlist[$kcuId] = $plist; //(包括所有的条数及当前要展示的数据列表： total_found ， list )
					}else{
						unset($thisCatObj["info"]["childs"][$kcuId]);
					}
				}
				$thisCatObj["productlist"]=  $productlist;
			}else{ //搜索结果显示
				if(isset($thisCatObj["info"]) &&  intval($thisCatObj["info"]["level"])>0 &&  intval($thisCatObj["info"]["level"])<=2 ){
					$commfilter["class"] = intval($thisCatObj["info"]["level"])==2 ? "two" : "";
					$filter_param["class"] =  intval($thisCatObj["info"]["level"])==2 ? "two" : ""; //传入下一页使用的参数
					$commfilter["page_size"]= 12;
				}
				$list = app::get('topm')->rpcCall('item.search', $commfilter);
				$thisCatObj["total_found"]=  intval($list["total_found"]);
				$losst_uri ="";
				if(intval($list["total_found"])>0){
					foreach($list["list"] as $k=>$vv){
						if($vv && ( !isset($vv["image_default_id"]) || trim($vv["image_default_id"]) =="") ){
							$list["list"][$k]["image_default_id"]= "themes/demo/images/jmall_loading.png"; //默认一张空图。
						}
						//获取二维码的操作；
						$list["list"][$k]["wap_qrcpath"] = $this->__getGoodsQrcPath($vv["item_id"]);
					}
					$losst_nums = intval($list["total_found"])-$commfilter["page_size"] ;
					if($losst_nums>0){
						$losst_uri = url::action("topm_ctl_topic@ajaxlplist",$filter_param ); //取到第二页
					}
				}
				//是否还有可以显示更多，即分页；
				$thisCatObj["getmoreinfo"]= array("loading_nums"=> $losst_nums>0 ?$losst_nums : 0,"more_action"=> $losst_uri);
				$thisCatObj["productlist"] =  intval($list["total_found"])>0 ?  $list["list"] :false;
			}
			if(!$list || empty($list) && count($list)<=0 ){   //默认推荐的产品数据列表；
				$this->__getDefaultgoods($thisCatObj,$commfilter); //搜索没有结果的话，建议放6个推荐商品；
			}
		}else{
			$this->__getDefaultgoods($thisCatObj,$commfilter); //搜索没有结果的话，建议放6个推荐商品；
		}
		//print_r($_SERVER["HTTP_USER_AGENT"]);
		//调用 模板        
		$pagedata["results"]=$thisCatObj;
		$pagedata["topnav_list"]=$this->__getDefaultNavs(); //保存当前ID值；
		$pagedata["default_load_image"] = $defaultPic["L"]["default_image"];; //默认一张空图。
		//print_r($thisCatObj);
        return $this->page($deault_pageFile, $pagedata);
	} 
	function __getGoodsQrcPath($itemId,$fields ="wap_qrcpath"){
		if(!$itemId) return false;
		$qrc_path ="";
		$objMdlItemDesc = app::get('sysitem')->model('item_desc');
		$fields = str_append($fields,'item_id');
        $itemInfoDesc = $objMdlItemDesc->getRow($fields, array('item_id'=>$itemId));
		if($itemInfoDesc && isset($itemInfoDesc["wap_qrcpath"])&&  trim($itemInfoDesc["wap_qrcpath"])!="" ){
			$qrc_path =  $itemInfoDesc["wap_qrcpath"]; //存在时，就直接调用，没有就直接生成二维码图片；
		}else{ 
			$qrc_path = $this -> __qrCode($itemId); //更新数据库里，是不是有保存；
			$qrc_path and  $objMdlItemDesc->update(array("wap_qrcpath"=> $qrc_path),array("item_id"=>$itemId ));
		}
		return $qrc_path? $qrc_path : false;
	}
	//获取线上 或本地的测试分类ID；
	function __getDefaultNavs(){
		$host = $_SERVER["HTTP_HOST"];
		$navlist = false;
		if($host=="www.gojmall.com" || $host=="www.myjmall.com"){
			$navlist = array( 781=>"母嬰馆", 786=>"美食馆", 827=>"保健馆", 797=>"美妝馆", 705=>"轻奢馆" );
		}else{
			$navlist = array( 549=>"母嬰馆", 548=>"美食馆", 646=>"保健馆", 550=>"美妝馆", 551=>"生活馆" );
		}
		return $navlist ? $navlist : false;
	}
	
	/**
	 * 二维码图片，现场生成
	 */
	private function __qrCode($itemId)
    {
        $url = url::action("topm_ctl_item@index",array('item_id'=>$itemId));
        $qrCode = new QrCode();
        return $qrCode
            ->setText($url)
            ->setSize(80)
            ->setPadding(10)
            ->setErrorCorrection(1)
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabelFontSize(16)
            ->getDataUri('png');
    }
	
	function __getDefaultgoods(&$thisCatObj , $commfilter = array(),$is_all = false ){
		//搜索没有结果的话，建议放6个推荐商品；
		if(!isset($thisCatObj["total_found"]) || intval($thisCatObj["total_found"]) <=0 ){
			unset($commfilter["cat_id"]);//去掉搜索条件来查询；
			unset($commfilter["search_keywords"]);//去掉搜索条件来查询；
			$commfilter["page_size"]= 12; //取每个子分类的前24个产品；
			$list= app::get('topm')->rpcCall('item.search', $commfilter);
			$thisCatObj["default_list"]=  intval($list["total_found"])>0 ? $list["list"] :false;
		}
	}
	
	
	//异步加载产品详细信息
	function lpdetail(){
		$this->setLayout('lpheader.html');
		$url=url::action('topm_ctl_topic@index')."?tname=list";
		$itemId = intval(input::get('item_id'));
        if(!$itemId || intval($itemId)<0) {
        	$itemId = intval(input::get("id"));
        }
        if(empty($itemId)){
            return redirect::action('topm_ctl_topic@index',array("tname"=>"list"));
        }
		$pagedata['title'] = "查看商品详情  [精茂城大厅立屏展]";
        //$pagedata['image_default_id'] = $this->__setting();
        
        $defaultPic = app::get('image')->getConf('image.set');
		$pagedata["default_load_image"] = $defaultPic["L"]["default_image"];; //默认一张空图。
		
        //查询商品参数
        $params=array('item_id'=> $itemId  ,'use_platform' => 1);
        $params['fields'] = "*,item_desc.wap_desc,item_desc.wap_qrcpath,item_count,item_store,item_status,sku,item_nature,spec_index";
        
        //获取商品
        $detailData = app::get('topm')->rpcCall('item.get',$params);
        
        if($detailData && isset($detailData["item_id"]) && intval($detailData["item_id"])>0) {
            if(count($detailData['sku']) == 1) {
	            $detailData['default_sku_id'] = array_keys($detailData['sku'])[0];
	        }
	        
	        //相册图片
	        if( $detailData['list_image'] ) {
	            $detailData['list_image'] = explode(',',$detailData['list_image']);
	        }
			//获取店家信息
	        //$pagedata['shopCat'] = app::get('topm')->rpcCall('shop.cat.get',array('shop_id'=>$pagedata['item']['shop_id']));
	        $pagedata['shop'] = app::get('topm')->rpcCall('shop.get',array('shop_id'=>$detailData['shop_id']));
	        // HTML 转码操作
	        $detailData["wap_desc"] = isset( $detailData["wap_desc"]) ? htmlspecialchars_decode( $detailData["wap_desc"]) : "";
            $pagedata["item"]=$detailData;
            if(isset($detailData["cat_id"]) && $currentCId =  intval($detailData["cat_id"])){
            	//获取所有分类 category.cat.get.list ；  ===  获取一级类目下的所有分类category.cat.get    
				$parentObj = app::get('topm')->rpcCall('category.cat.get.info', array("cat_id"=> $currentCId)); //获取当前分类列表
				$parentObj and $parentObj = reset($parentObj);
				$parent_Id = isset($parentObj["parent_id"]) ? intval($parentObj["parent_id"]) :0;
				if($parent_Id > 0 && intval($parentObj["level"])==3){
					$threeCat = app::get('topm')->rpcCall('category.cat.get.info', array("cat_id"=> $parent_Id)); //获取当前分类名称；
					$threeCat and $threeCat = reset($threeCat);
					$threeCat and $parent_Id = $threeCat["parent_id"];
				}
				$pagedata["current_default_id"]=$parent_Id; //保存当前ID值；(当ID是二或3级目录时，保存父级)
            }
        }else{
        	$pagedata['error'] = "商品过期不存在";
        }
        /*$seoData = array(
            'item_title' => $detailData['title'],
            'shop_name' =>$pagedata['shop']['shop_name'],
            'item_bn' => $detailData['bn'],
            'item_brand' => $brand['brand_name'],
            'item_cat' =>$cat[$detailData['cat_id']]['cat_name'],
            'sub_title' =>$detailData['sub_title'],
            'sub_props' => $props
        );
        seo::set('topm.item.detail',$seoData);*/
        $pagedata["topnav_list"]=$this->__getDefaultNavs(); //保存当前ID值；
        return $this->page("topm/common/lp_search_detail.html", $pagedata);
	}  
	
	//异步加载产品列表
	function ajaxlplist($getCatId＝0,$curr_page = 1){
		//分页获取分页显示的商品列表信息
		if(!$getCatId || intval($getCatId)<= 0){
			$getCatId = input::get("cid");
			$curr_page = input::get("next_page");
		}
		$defaultPic = app::get('image')->getConf('image.set');
		$curr_page = $curr_page>1 ? $curr_page : 2 ;//默认取第二页
		$getitemId = input::get("search_goods_id");
		$getKeyword = input::get("search_keywords");
		$getShopId = input::get("search_shop_id");
		$getShopId = input::get("search_shop_id");
		$filter_params = input::get(); //获取所有参数；
		$thisCatObj = array("type"=>"cate","keywords"=>$getKeyword,"cateId"=>$getCatId );  //默认按关键字来搜索结果
		if(isset($getCatId) && !empty($getCatId) && intval($getCatId)>0 ){
			if(isset($getKeyword) && trim($getKeyword)!=""){ $thisCatObj["type"]="both"; } //是否同时有关键字
		}else if(isset($getKeyword) && trim($getKeyword)!=""){
			$thisCatObj["type"]="keyword";
		}else if(isset($getitemId) && intval($getitemId)>0){
			$thisCatObj["type"]="goods_id";
		}else if(isset($getShopId) && intval($getShopId)>0){
			$thisCatObj["type"]="shop_id";
		}else{
			$thisCatObj["type"]="all";
		}
		$orderBy = "order_sort DESC";
		$commfilter = array('disabled'=>0,'approve_status'=>'onsale','fields'=>'item_id,shop_id,cat_id,title,price,image_default_id,tax,sea_region','orderBy'=>$orderBy,'page_size'=>6,'platform'  =>'wap');
		if(isset($thisCatObj) && (intval($getCatId)>0 || intval($getShopId)>0 || trim($getKeyword)!="" || trim($getitemId) !="" || $thisCatObj["type"]=="all")){
			//通过分类,关键字、商品ID，以及店铺ID来获取所有产品。
			switch(strtoupper($thisCatObj["type"])){
				case "CATE": $commfilter["cat_id"] = intval($getCatId);break; 
				case "KEYWORD": $commfilter["search_keywords"] = $getKeyword ;break;
				case "GOODS_ID": $commfilter["item_id"] = $getitemId ;break;
				case "SHOP_ID": $commfilter["shop_id"] = $getShopId ;break;
				case "BOTH":$commfilter["cat_id"] = $getCatId ;$commfilter["search_keywords"] = $getKeyword ;break;
				default :$commfilter["disabled"]=0; ; break;
			}
			$commfilter["class"] = $filter_params["class"] ?  $filter_params["class"] : "";
			$curr_page and $commfilter["page_no"] = $curr_page;
			//查询结果显示
			$list = app::get('topm')->rpcCall('item.search', $commfilter);
			//查询是否还有下一页；
			$losst_nums = intval($list["total_found"]) - ($curr_page * $commfilter["page_size"]) ;
			if($losst_nums>0){
				$filter_params["next_page"]= $curr_page + 1 ; //可以到下一页
				$thisCatObj["next_total_nums"] = $losst_nums;
				$thisCatObj["getmore_action"]= url::action("topm_ctl_topic@ajaxlplist",$filter_params); //取到第二页
			}
			$thisCatObj["total_found"]=  intval($list["total_found"]);
			if(isset($list["list"]) && !empty($list["list"])){
				foreach($list["list"] as $k=>$vv){
					if($vv && ( !isset($vv["image_default_id"]) || trim($vv["image_default_id"]) =="") ){
						$list["list"][$k]["image_default_id"]= $defaultPic["L"]["default_image"]; //默认一张空图。
					}
					$list["list"][$k]["wap_qrcpath"] = $this->__getGoodsQrcPath($vv["item_id"]);	//获取二维码的操作；
				}
			}
			$thisCatObj["productlist"] =  !empty($list["list"]) ?  $list["list"] : false;
			//是否还有可以显示更多，即分页；
			$pagedata["results"]=$thisCatObj;
			$msg = view::make('topm/common/lp_ajax_goodslist.html',$pagedata)->render();
        	return $this->splash('success',null,$msg,true);
		}   
    	return $this->splash('error',null,"亲，已经到最底部了……",true);
	}
    
}
