<?php
/**
 * 接口作用说明
 * item.search
 */
class sysitem_api_search_xunsearchItems{

    public $apiDescription = '根据条件XunSearch获取商品列表';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
         	'item_id' => ['type'=>'string','valid'=>'','description'=>'商品id，多个id用，隔开','example'=>'2,3,5,6','default'=>''],
            'shop_id' => ['type'=>'int','valid'=>'integer','description'=>'店铺id','example'=>'','default'=>''],
            'store_name' => ['type'=>'string','valid'=>'','description'=>'店铺名称来搜索','example'=>'','default'=>''],
            //'search_shop_cat_id' => ['type'=>'int','valid'=>'','description'=>'店铺搜索自有一级类目id','example'=>'','default'=>''],
            'cat_id' => ['type'=>'string','valid'=>'','description'=>'商城类目id,以及ID数组集合','example'=>'','default'=>''],
            'brand_id' => ['type'=>'string','valid'=>'','description'=>'品牌ID','example'=>'1,2,3','default'=>''],
            //'prop_index' => ['type'=>'string','valid'=>'','description'=>'商品自然属性','example'=>'','default'=>''],
            'search_keywords' => ['type'=>'string','valid'=>'','description'=>'搜索商品关键字','example'=>'','default'=>''],
            'buildExcerpts' => ['type'=>'bool','valid'=>'','description'=>'是否关键字高亮','example'=>'','default'=>''],
            'is_selfshop' => ['type'=>'bool','valid'=>'','description'=>'是否是自营','example'=>'','default'=>''],
            'tax' => ['type'=>'int','valid'=>'integer','description'=>'业务类型','example'=>'','default'=>''],
            'sea_region' => ['type'=>'int','valid'=>'integer','description'=>'仓库ID','example'=>'','default'=>''],
            'use_platform' => ['type'=>'string','valid'=>'','description'=>'商品使用平台(0=全部支持,1=仅支持pc端,2=仅支持wap端)如果查询不限制平台，则不需要传入该参数','example'=>'1','default'=>'0'],
            //'min_price' => ['type'=>'int','valid'=>'numeric','description'=>'搜索最小价格','example'=>'','default'=>''],
            //'max_price' => ['type'=>'int','valid'=>'numeric','description'=>'搜索最大价格','example'=>'','default'=>''],
            'bn' => ['type'=>'string','valid'=>'','description'=>'搜索商品货号','example'=>'','default'=>''],
            'approve_status' => ['type'=>'string','valid'=>'','description'=>'商品上架状态','example'=>'','default'=>''],
            'page_no' => ['type'=>'int','valid'=>'numeric','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'numeric','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'string','valid'=>'','description'=>'排序方式','example'=>'','default'=>'modified_time desc,list_time desc'],
            'fields' => ['type'=>'field_list','valid'=>'','description'=>'要获取的商品字段集','example'=>'','default'=>''],
        );
       	//$return['extendsFields'] = ['promotion','store'];
        return $return;
    }
    
    
    /**
     * 获取子分类ID
     * 默认取字段：$fields ＝ cat_id
     */
    function __get_chirendIds ($cat_id,$fields = "cat_id"){
    	$filter = array('parent_id' => $cat_id);
    	$data = app::get('syscategory')->model('cat')->getList($fields, $filter);
    	$ids = array_column($data, $fields);
    	return $ids ? $ids : false;
    }
    
    /**
     * 查询分类是否有数据
     */
    function __findCats($keyword , $XunSearchMdl = false){
    	if(!$keyword)return false;
    	$keyword = str_replace(array("\'","\"","’","‘","\\","\/")  ,'',strtolower($keyword));
    	$db = app::get('syscategory')->database();
    	$sql = "SELECT cat_id,cat_name, level, name_jp, name_quanp, parent_id FROM syscategory_cat WHERE (name_jp LIKE '%{$keyword}%' OR name_quanp LIKE '%{$keyword}%' OR cat_name LIKE '%{$keyword}%') LIMIT 10";
    	$catlist = $db->fetchAll($sql);
    	if(!$XunSearchMdl){
    		$XunSearchMdl = kernel::single("search_policy_xunsearch")->instance("jmall");
    	}
    	//获取分类数据；
    	if($catlist && !empty($catlist)) {
    		foreach ($catlist as $k => $catobj ){
    			$this_cid = isset($catobj["cat_id"]) ? intval($catobj["cat_id"]) :false;
    			if(!$this_cid){ continue ;}
    			$idss = false;
				$query_detail =  "cat_id:".$this_cid;
				switch (intval($catobj["level"])) {
					case 2:
						$sqlQurey = "SELECT  cat_id FROM syscategory_cat WHERE parent_id = {$this_cid}";
						$idss = $db->fetchAll($sqlQurey); break;
					case 1:
						$sqlQurey = "SELECT  cat_id FROM syscategory_cat WHERE parent_id IN (SELECT cat_id FROM syscategory_cat WHERE parent_id = '{$this_cid}')"; 
						$idss = $db->fetchAll($sqlQurey);break;
				}
				$idss and $idss = array_column($idss,'cat_id');
				if($idss && !empty($idss)){  
					$query_detail = "";
					$vii = 0;$viilen  = count($idss);
					foreach($idss as $cur_value ){ 
						$vii ++;
						if(isset($cur_value) && intval($cur_value) > 0 ){
							$query_detail .=  'cat_id:' . $cur_value;
						}
						if($vii>0 && $vii < $viilen ) $query_detail .= " OR ";
					}
					$query_detail = "({$query_detail})";
				}
				//统计估算结果数据
				if($query_detail){
					$plus_condition = "disabled_status:0 AND shop_status:active AND {$query_detail}";
	    			$result = $XunSearchMdl->countKeywords($keyword, true,$plus_condition);
	    			$catlist[$k]["querystr"] = $query_detail;
	    			$catlist[$k]["countwords"] = $result;
				}
    		}
    	 }
        ///$catInfo = $objMdlCat->getRow('cat_id,cat_name, level, name_jp, name_quanp, parent_id', array('name_jp|has'=>$keyword));
        return $catlist;
    }

    public function getItemids($params)
    {
        $row = $params['fields']['rows'];
        $pageNo = $params['page_no'];
        $last_result = false;
		$last_success_ids = false;
        //开启搜索引擎索功能；
        $last_snumber = 0 ;
        //默认是搜索产品并分页处理 ；
        if(isset($params["use_platform"]) &&  isset($params["search_keywords"])  && trim($params["search_keywords"])!="" &&strtoupper($params["use_platform"])=="FINDS"){
        	//搜索关键字，显示相关的关键字及统计
        	//error_reporting(E_ALL);ini_set('display_errors', '1');
        	$XunSearchMdl = kernel::single("search_policy_xunsearch")->instance("jmall");
        	$word = isset($params["search_keywords"]) ? trim($params["search_keywords"]) : "";
        	
        	//通过关键字搜索有关联的分类名：        	
        	$catSearch =  $this->__findCats($word,$XunSearchMdl);
        	
        	//用搜索引擎搜索拆分词结果
        	$result = $XunSearchMdl->getXSWords($word,5);
        	
        	//返回结果中再对关键字整理
        	$last_result = array("words" => $result ,"searchcat"=>$catSearch, "params"=> $word);
        }else{
	        if($params){	//过滤参数
	        	if($params["use_platform"]=="0,1") unset($params["use_platform"]);
	        	$params["cat_id"]!="" and $params["cat_id"] =array_unique(explode(",",$params["cat_id"])) ; 
	        	$params["search_keywords"]!="" and $params["keyword"] = $params["search_keywords"] ; 
	        	$params["item_id"]!="" and $params["item_id"] = explode(",",$params["item_id"]) ; 
	        }
	    	/**
			 * 新增用搜索引擎XunSearch 来查询数据
			 * XunSearch是否开启,开启就使用搜索引擎来搜索产品
			 * 否则就采用接口来搜索商品数据；
			 */
			$is_xs_success = false; //标识是否使用搜索引擎来搜索，并取搜索结果
	     	$XunSearchMdl = kernel::single("search_policy_xunsearch")->instance("jmall");
	     	if($XunSearchMdl && $XunSearchMdl -> checkXS_status() ){
	     		//echo "提醒：使用搜索引擎来搜索相关商品数据  ： 索引总数＝".$XunSearchMdl->getTotal_num()."<br/>";;
	     		//$xs_condition = $this->__preFilterXs($params);   //用户XS的搜索条件组装
	     		//$xs_condition = array("shop_id"=>1 , "keyword"=> "韩国进口");
	     		$rest = $XunSearchMdl-> searchXs_Result($params, $pageNo , $last_snumber , $params["page_size"]);
	     		//var_dump($rest);
	     		if($rest && isset($rest["mids"]) &&  $rest["total"] > 0  ){
	     			$last_snumber = $rest["total"] ;
	     			$is_xs_success = true ;
	     			$last_success_ids = empty($rest["mids"]) ? false : $rest["mids"];
	     			
	     			//返回高凉的结果信息
        			$hightwords = $XunSearchMdl-> hightXsWord($rest["result"]);
	     		}
	     	}
	     	$last_result = $last_snumber >0 ?  array("total"=>$last_snumber, "xs_success"=> $is_xs_success , "items" => $last_success_ids ,"hightwords" => $hightwords,'papp'=>json_encode($rest) ) : false ;
        }
        return $last_result;
    }
}
