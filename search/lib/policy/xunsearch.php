<?php

class search_policy_xunsearch {

    public $name = 'XunSearch 搜索';
    public $type = 'xunsearch';
    public $description = '基于xunsearch开发的搜索引擎';
    public $_xsObj = null;
    public $_xsObj_search = null;
    public $_xsObj_index =  null;
    public $_xs_serverpath = null;
    public $_xs_source_name = "demo";
    public $_indexer_ids = null ;	//搜索结果ID集合

	/***
	 * 重建数据索引
	 * DEMO 保存商品分类的索引数据
	 * /usr/local/xunsearch/sdk/php/util/Indexer.php demo  --source=mysql://root:root2015@112.74.64.132/myjingmao --sql="SELECT cat_id ,cat_name ,level, parent_id,cat_path  FROM syscategory_cat" --rebuild
	 * 
	 * 
	 * 	Jmall 保存商品列表数据
	 *  服务器上执行全部更新索引命令：
	 * 
	 * /usr/local/xunsearch/sdk/php/util/Indexer.php jmall  --source=mysql://root:root2015@112.74.64.132/myjingmao --sql="SELECT tt.item_id AS item_id ,title,bn,sub_title,cat_id,tt.shop_id AS shop_id,shp.shop_name AS shop_name,shp.shop_type AS shop_type,shp.status AS shop_status,tt.brand_id,shop_cat_id,tax,sea_region,price,mkt_price,tt.order_sort,area_id,tt.disabled AS disabled_status,tt.modified_time AS modified_time,use_platform,is_selfshop ,tb.brand_name AS brand ,tsu.approve_status AS approve_status,tsu.list_time AS list_time,tsu.delist_time AS delist_time,tso.store AS  store,tso.freez AS  freez_store FROM sysitem_item tt LEFT JOIN sysshop_shop shp ON tt.shop_id = shp.shop_id LEFT JOIN syscategory_brand tb ON  tt.brand_id = tb.brand_id LEFT JOIN sysitem_item_status tsu ON  tsu.item_id = tt.item_id  LEFT JOIN sysitem_item_store tso ON  tso.item_id = tt.item_id  LIMIT 10000" --rebuild
	 *
	SQL:SELECT tt.item_id AS item_id ,title,bn,sub_title,cat_id,tt.shop_id AS shop_id,shp.shop_name AS shop_name,shp.shop_type AS shop_type,shp.status AS shop_status,tt.brand_id,shop_cat_id,tax,sea_region,price,mkt_price,tt.order_sort,area_id,tt.disabled AS disabled_status,tt.modified_time AS modified_time,use_platform,is_selfshop ,tb.brand_name AS brand ,tsu.approve_status AS approve_status,tsu.list_time AS list_time,tsu.delist_time AS delist_time,tso.store AS  store,tso.freez AS  freez_store 
FROM sysitem_item tt 
LEFT JOIN sysshop_shop shp ON tt.shop_id = shp.shop_id
LEFT JOIN syscategory_brand tb ON  tt.brand_id = tb.brand_id 
LEFT JOIN sysitem_item_status tsu ON  tsu.item_id = tt.item_id  
LEFT JOIN sysitem_item_store tso ON  tso.item_id = tt.item_id  LIMIT 10000
	 */


    /**
     *__construct 初始化类,连接sphinx服务
     */
    public function __construct()
    {
    	/*error_reporting(E_ALL); ini_set('display_errors', '1');*/
		$t_host = $_SERVER['HTTP_HOST'];//获取当前域名  
		$this->_xs_serverpath = "/usr/local/xunsearch";
    	$filePath = __DIR__."/XS.php";
    	if(isset($t_host) && (trim($t_host) =="www.gojmall.com" || trim($t_host) =='test.gojmall.com')){
    		$filePath = $this->_xs_serverpath."/sdk/php/lib/XS.php";//在线资源
    	}
    	require_once($filePath);
    }//End Function
    
    /**
     * 指定数据源，默认使用 DEMO 源
     * 对应的源配置文件：/sdk/php/app/demo.ini  
     */
    function instance($source='demo'){
    	if(!$source || empty($source)){ $source = "demo"; }
    	$source_file = $this->_xs_serverpath ."/sdk/php/app/{$source}.ini";
    	//存在
    	if(file_exists($source_file)){
    		$this->_xsObj = new XS($source_file);  
    		if (!empty($this->_xsObj)){  
	    		$this->_xsObj_search = $this->_xsObj->getSearch();  //获得搜索对象  
	    		$this->_xsObj_index = $this->_xsObj->index; 		//获得索引对象  
	    		$this->_xs_source_name = $source; //系统调用的数据源
		    }else{
		    	exit("XS 数据源加载失败"); 
		    }
    	}else{
    		 exit("XS 数据源配置文件不存在");
    	}
		return $this;    	
    }
    
    //获取当前DEMO的主键，或title ,body
    function getFieldId($field_name = '' ){
    	$idobj = isset($this->_xsObj) ?  (array)$this->_xsObj ->getFieldId() :false ;
    	if($field_name && trim($field_name)!="" ){
    		switch ( $field_name ) {
				case 'title':
					$idobj = isset($this->_xsObj) ?  (array)$this->_xsObj ->getFieldTitle() :false ;
					break;
				case 'body':
					$idobj = isset($this->_xsObj) ?  (array)$this->_xsObj ->getFieldBody() :false ;
					break;
			}
    	}
    	return (isset($idobj) && isset($idobj["name"]) && !empty($idobj["name"])) ? trim($idobj["name"]) : false ;
    }
    
    function getTotal_num(){
    	if(!$this->_xsObj_search){  return false; }
    	//获得搜索数据库中数据的总量  
        $total = $this->_xsObj_search->getDbTotal();  
        return $total ? $total : 0;
    }
    
    
    /**
     * 设置分面搜索字段
     * 以及读取分面结果 
     * $fid_counts = $search->getFacets('fid'); // 返回数组，以 fid 为键，匹配数量为值、
     * $year_counts = $search->getFacets('year'); // 返回数组，以 year 为键，匹配数量为值
     */
    function commonFacetsConfig($config , $is_get = false ){
    	if(!$this->_xsObj_search){  return false; }
    	if($config && !empty($config) && is_array($config)){
    		 $this->_xsObj_search->setFacets($config,TRUE);
    	}else if($config && is_string($config) && $is_get === true){ //读取分面结果
    		return $this->_xsObj_search->getFacets($config);
    	}
    	return false;
    }
    
    
    /**
     * 获取XunSearch的运行状态
     *
     */
    public function checkXS_status(&$msg)
    {
        if(true){ //$status = $this->query('SHOW STATUS');
            $msg = '已建立连接';
            return true;
        }  else {
            $msg = '连接状态异常';
            return false;
        }
    }//End Function
    
    /**
     * 过滤索引数据
     */
    function _filterData($index_data){
    	$id_name = $this->getFieldId();
    	if($id_name && $index_data && isset($index_data["{$id_name}"]) && intval($index_data["{$id_name}"]) > 0){
    		return true;
    	}
    	return false;
    }
    
    /**
     * 添加单个索引记录
     * 
     */
     public function add_One($index_data){
     	if($index_data && $this->_filterData($index_data)){
     		if($this->_xsObj_index){
	     		$doc = new XSDocument ;//创建文档对象
				$doc->setFields($index_data); 
				$this->_xsObj_index->add($doc);//添加到索引数据库中
	     	}
     	}else{
     		return false ; //exit("无效数据")
     	}
     }
     
     
   /**
     * 设置检索条数-分页
     * @param int $pagesize
     * @param int $page
     */
    function _setLimit($page = 1, $pagesize = 20){
    	if(!$this->_xsObj_search) return false;
		$pagesize = $pagesize>0 ? $pagesize : 20;
		if (is_numeric($page) && $page > 0) {
		    $curpage = intval($page);
		    $start = ($curpage - 1) * $pagesize;
		} else {
		    $start = 0;
		}
		$this->_xsObj_search->setLimit($pagesize, $start);
    }
    /**
     * 单个就返回这样的：  AND hoood:3
     * 数组就返回这样的  AND (limte:1 OR limit:2) '
     * 	AND hoood:3 AND (catdd_id:55 OR catdd_id:56)
     */
    function __useQueryString($fieldstr , $target_arr = false){
    	$last_query = " AND ";
    	if($fieldstr && $target_arr && !empty($target_arr)){
    		$target_vals = is_array($target_arr) ? $target_arr : array($target_arr);
    		$lent = count($target_vals);
    		$vii = 0;
    		$cate_query = "";
			foreach($target_vals as $cur_value ){ 
				$vii ++;
				if($cur_value && trim($cur_value) !="" ){
					$cate_query .= $fieldstr.':' . $cur_value;
				}
				if($vii>0 && $vii < $lent) $cate_query .= " OR ";
			}
			$last_query .= "({$cate_query})";
    	}
    	return $cate_query ? $last_query  : "";
    }
    
    /**
     * 设置全文检索查询条件
     * @param unknown $condition
     * @param array $order
     */
    function __setQueryXS($condition, $order="" ,&$query_wangzheng="") {
    	if(!$this->_xsObj_search) return false;
		$this->_xsObj_search->setQuery('');//清除搜索条件
		//return $condition;
		if($this->_xs_source_name && $this->_xs_source_name =="jmall"){
			$plus_condition = "disabled_status:0 AND shop_status:active AND approve_status:onsale";
			if (isset($condition['keyword'])) {
				$kws = is_array($condition['keyword']) ? implode(" ",$condition['keyword']) : trim($condition['keyword']) ;
			    //$this->_xsObj_search->addQueryString(is_null($kws) ? '' : $kws );  // OR 的关系 
			    //$this->_xsObj_search->addWeight('title', is_null($kws) ? '' :  $kws);
			    $plus_condition .=  ' AND (' . $kws . ')'; //关键字搜索引入AND
			}
			//$plus_condition  .= print_r($plus_condition,false);
			if (isset($condition['cat_id'])) {
				$plus_condition  .= $this->__useQueryString("cat_id", $condition['cat_id']);
			    //$cate_query and $this->_xsObj_search->addQueryString($cate_query);
				//$this->_xs_search->setMultiSort(array('sales'=>false,'credit'=>false,'collects'=>false,'views'=>false,'add_time'=>false));
			}
			if (isset($condition['brand_id'])) {
				$plus_condition  .= $this->__useQueryString("brand_id", $condition['brand_id']);
			    //$brand_query and $this->_xsObj_search->addQueryString($brand_query);
			}
			if (isset($condition['shop_id'])) {
				$plus_condition  .= $this->__useQueryString("shop_id", $condition['shop_id']);
			    //$shop_query and $this->_xsObj_search->addQueryString($shop_query);
			}
			if (isset($condition['store_name'])) {
			    $this->_xsObj_search->addQueryString('store_name' . ':' . $condition['store_name']);
			}
			if (isset($condition['item_id'])) {
			    $plus_condition  .= $this->__useQueryString("item_id", $condition['item_id']);
			    //$itids_query and $this->_xsObj_search->addQueryString($itids_query);
			}
			if (isset($condition['tax'])) {
			    $plus_condition  .= $this->__useQueryString("tax", $condition['tax']);
			   // $itids1_query and $this->_xsObj_search->addQueryString($itids1_query);
			}
			if (isset($condition['sea_region'])) {
			    $plus_condition  .= $this->__useQueryString("sea_region", $condition['sea_region']);
			    //$itids2_query and $this->_xsObj_search->addQueryString($itids2_query);
			}
			if (isset($condition['is_selfshop'])) {
			    $plus_condition  .= $this->__useQueryString("is_selfshop", $condition['is_selfshop']);
			    //$itids4_query and $this->_xsObj_search->addQueryString($itids4_query);
			}
			if (isset($condition['bn'])) {
			    $plus_condition  .= $this->__useQueryString("bn", $condition['bn']);
			    //$itids3_query and $this->_xsObj_search->addQueryString($itids3_query);
			}
			
			//把搜索规则 筛选条件加入到查询条件中
			if($plus_condition) {
				$query_wangzheng = $plus_condition;
			 	$this->_xsObj_search->setQuery($plus_condition); // 前提条件
			}
			//$this->_indexer_ids = $plus_condition;
			/*if (is_array($condition['attr_id'])) {
			    foreach ($condition['attr_id'] as $attr_id) {
				$this->_xsObj_search->addQueryString('attr_id' . ':' . $attr_id);
			    }
			}*/
			
		}else{ //默认使用 demo 数据源
			$this->_xsObj_search->setQuery(''); // 前提条件
			if (isset($condition['keyword'])) {
				$kws = is_array($condition['keyword']) ? implode(" ",$condition['keyword']) : trim($condition['keyword']) ;
			    $this->_xsObj_search->addQueryString(is_null($kws) ? '' : $kws );
			    $this->_xsObj_search->addWeight('cat_name', is_null($kws) ? '' :  $kws);
			}
			if (isset($condition['parent_id'])) {
			    $this->_xsObj_search->addQueryString('parent_id' . ':' . $condition['parent_id']);
			}
			if (isset($condition['level'])) {
			    $this->_xsObj_search->addQueryString('level' . ':' . $condition['level']);
			}
		}
		//排序字段		
		if($order){
		   $this->_xsObj_search->setMultiSort($order);
		}
	    ///return $this->_xsObj_search->getQuery() . "<br/>";
    }
     
     
     
     /**
      * 高亮处理相关的数据
      */
     function hightXsWord($rest_data , $source = "jmall"){
     	if(!$this->_xsObj_search || !$rest_data){  return false; }
     	//字段串就直接处理当前数据
     	if($rest_data && is_string($rest_data)) {
     		$rest_data = $this->_xsObj_search -> highlight( $rest_data ); 
     	}else if($rest_data && is_array($rest_data) && !empty($rest_data)) {
     		$rest_newdata  =  array();
     		if($source == "jmall" && count($rest_data) >0 ){
     			foreach($rest_data as $kk => $rest){
     				$ii_id = isset($rest["item_id"]) ? trim($rest["item_id"]) : false;
     				if($ii_id && $rest["title"] ){
     					$rest_newdata["{$ii_id}"] = $this->_xsObj_search -> highlight( $rest["title"] ); 
     				}
     			}
     		}else{ // 暂不处理
     		}
     		$rest_newdata and $rest_data = $rest_newdata;
     	}
     	return $rest_data;
     }
     
     /**
      * 统计当前关键字的估计商品数目
      * $is_cat 传入是分类， 并且还有标识分类的级别Level字段值
      */
     function countKeywords($keyword,$is_cat = false , $query_str = ""){
     	if(!$this->_xsObj_search && !$keyword){  return 0; }
     	$sqlquery = 'approve_status:onsale AND title:'.$keyword; //默认查询标题；
     	if($is_cat && trim($query_str) != ""){
     		$sqlquery = $query_str; //查询分类ID；
     	}
     	return $tojinum = $this->_xsObj_search->setQuery($sqlquery)->count();
     }
     
     /**
      * 获取当前搜索相关的词列表
      */
     function getXSWords($word = "" , $limit = 10 ){
     	if(!$this->_xsObj_search || !$word){  return false; }
     		//getHotQuery 热门搜索词
     	    //getCorrectedQuery();尝试修正
     	    //getRelatedQuery 智能补全 ===  获取相关搜索词列表
     	    //getExpandedQuery  获取展开的搜索词列表 (query )
     	    //setRequireMatchedTerm  设置在搜索结果文档中返回匹配词表 请在 search 前调用本方法, 然后使用 XSDocument::matched 获取
     		//getFields() //获取字段
	     	//getAddTerms()  //[XSDocument_meta]
	     	//getIterator()  IteratorAggregate 接口, 以支持 foreach 遍历访问字段列表
     		// 查询拆成哪些词来搜索；
     		 $this->_xsObj_search->setRequireMatchedTerm(true)->setAutoSynonyms(true);
     		 $relateWords = $this->_xsObj_search->getRelatedQuery($word,6);  // 取最多五个拆分词
     		 $chatWords = $this->_xsObj_search->terms($word,true);  // 取最多五个拆分词
     		 $newWords = array();
     		 ////第一个主词搜索； 本身体
     		 $newWords[] = array("keyword"=> $word , "number"=> $tojinum2 = $this->countKeywords($word));
     		 if($chatWords) {
     		 	$coutt = 0;
     		 	if($relateWords && !empty($relateWords)) $chatWords = array_unique(array_merge($relateWords,$chatWords)); 
     		 	foreach($chatWords as $k=> $words2){
     		 		if($words2 && trim($words2) != "" && $words2!=$word && $coutt < 5){
     		 			$tojinum = $this->countKeywords($words2); //->setQuery('title:'.$words2)->count();
     		 			if($tojinum >0 ){
     		 				$newWords[] = array("keyword"=> $words2 , "number"=>$tojinum); $coutt ++ ;
     		 			}
     		 		}
     		 	};
     		 }
     	return $newWords;
     }
     
    
    /**
     * ＝＝＝＝＝＝＝通过关键字来搜索数据结果＝＝＝＝＝＝＝
     * @param array $condition 查询条件
     * @param int  $page  当前页码
     * @param int $result_total 返回总查询条数
     */
    public function searchXs_Result($condition ,$page = 1 ,&$result_total = 0 ,$pagesize = 20){
    	
    	if(!$this->_xsObj_search){  return false; }
    	
    	$this->_indexer_ids = null ;
    	
		//设置是否开启模糊查询  
    	$this->_xsObj_search->setFuzzy(true);  
    	//设置是否开启同义词查询  
    	$this->_xsObj_search->setAutoSynonyms(true);
    	
    	$order = '';
    	$detailQuery = "";
		//添加查询条件
		$this->__setQueryXS($condition , $order ,$detailQuery ); 
		if($page && intval($page) >0){ //分页
    		$this->_setLimit($page , $pagesize);
    	}
		//获得本次查询的结果总数（这是个估值）  
        $result_total = 0 ; //$this->_xsObj_search->getLastCount();  
        //$this->_xsObj_search->addQueryString('(limte:1 OR limit:2) AND hoood:3 AND (catdd_id:55 OR catdd_id:56)  XDHOOD');
		
		$lastResult = $this->__searchXS($result_total);		//搜索结果，
		$detailQuery and $totunber  = $this->countKeywords("wordcount",true,$detailQuery);
		
		//print_r($this->_indexer_ids);
        
        /*//相关搜索  通过 XSSearch::getRelatedQuery  方法获取热门搜索词，返回相关搜索词组成的数组。
	   		$gl_words = $this->_xsObj_search->getRelatedQuery(); //  获取前 6 个和最近一次 setQuery()  相关的搜索词
	   		//$words = $search->getRelatedQuery(‘测试’, 10);  //  获取 10 个和 ‘ 测试’  相关的搜索词
	   		$words2 =  $this->_xsObj_search->getHotQuery(10);//获取热门搜索词10条
	   		$words3 = $this->_xsObj_search->getExpandedQuery('n'); //  返回 array(‘ 测试’)
	   		var_dump($words3);
	   	*/
	   	//$this->_indexer_ids = $this->_xsObj_search->getQuery();
        $result = array("total"=> $totunber>0 ?$totunber :$result_total,'mids'=>$this->_indexer_ids ,"result" => $lastResult);
        //获得搜索数据库中数据的总量  
        //echo "搜索结果记录(第 {$page} 页)：".$result_total .print_r($result,true) ;
        return ($result && $result_total >0 && $this->_indexer_ids) ? $result : false;
    }
    
    
    
    /**
     * 执行全文搜索
     */
    function __searchXS( &$count = 0) {
    	$goods_data = array();
		try {
			if(!$this->_xsObj_search){  return false; }
		    $docs = $this->_xsObj_search->search();
		    //$count = $this->_xsObj_search->getLastCount();
		    $count =  $this->_xsObj_search->count();
		    
		    //取对应的数据
		    $goods_ids = array();
		    $brands = array();
		    $cates = array();
		    $main_id = $this-> getFieldId(); //主键字段名
		    foreach ($docs as $k => $doc) {
		    	$linedata = $doc->getFields();  //获取当前对象
		    	if($main_id && $linedata && !empty($linedata) && isset($linedata["{$main_id}"])){
		    		//设置主键ID值
		    		$gid = intval($linedata["{$main_id}"]) >0 ? intval($linedata["{$main_id}"])  : 0 ;
		    		$gid >0 and $goods_ids[] = $gid ;
		    		//标题高亮  
                	//$subject = $this->_xsObj_search->highlight($doc->cat_name);  
		    		$goods_data[$gid]	=  $linedata ;
		    	}
		    }
		    $goods_ids and $this->_indexer_ids = $goods_ids;
		    //读取分面结果
		    /*if ($this->_open_face) {
				$this->_face_cate = $this->_xs_search->getFacets($this->_cate_name);
				$this->_face_brand = $this->_xs_search->getFacets('brand');
				$this->_face_attr = $this->_xs_search->getFacets('attr_id');
				$this->_parseFaceAttr($this->_face_attr);
		    }*/
		    return $goods_data ;
		} catch (XSException $e) {
			return false;
		}
		return true;
    }
    
    public function index($index, $extends=false)
    {
        $this->index = $index;
        return $this;
    }
 

}//End Class

