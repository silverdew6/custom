<?php
/**
 * 接口作用说明
 * item.search
 */
class sysitem_api_search_searchItems{

    public $apiDescription = '根据条件获取商品列表';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
            'item_id' => ['type'=>'string','valid'=>'','description'=>'商品id，多个id用，隔开','example'=>'2,3,5,6','default'=>''],
            'shop_id' => ['type'=>'int','valid'=>'integer','description'=>'店铺id','example'=>'','default'=>''],
            'shop_cat_id' => ['type'=>'int','valid'=>'string','description'=>'店铺自有类目id','example'=>'','default'=>''],
            'search_shop_cat_id' => ['type'=>'int','valid'=>'','description'=>'店铺搜索自有一级类目id','example'=>'','default'=>''],
            'cat_id' => ['type'=>'int','valid'=>'integer','description'=>'商城类目id','example'=>'','default'=>''],
            'brand_id' => ['type'=>'string','valid'=>'','description'=>'品牌ID','example'=>'1,2,3','default'=>''],
            'prop_index' => ['type'=>'string','valid'=>'','description'=>'商品自然属性','example'=>'','default'=>''],
            'search_keywords' => ['type'=>'string','valid'=>'','description'=>'搜索商品关键字','example'=>'','default'=>''],
            'buildExcerpts' => ['type'=>'bool','valid'=>'','description'=>'是否关键字高亮','example'=>'','default'=>''],
            'is_selfshop' => ['type'=>'bool','valid'=>'','description'=>'是否是自营','example'=>'','default'=>''],
            'use_platform' => ['type'=>'string','valid'=>'','description'=>'商品使用平台(0=全部支持,1=仅支持pc端,2=仅支持wap端)如果查询不限制平台，则不需要传入该参数','example'=>'1','default'=>'0'],
            'min_price' => ['type'=>'int','valid'=>'numeric','description'=>'搜索最小价格','example'=>'','default'=>''],
            'max_price' => ['type'=>'int','valid'=>'numeric','description'=>'搜索最大价格','example'=>'','default'=>''],
            'bn' => ['type'=>'string','valid'=>'','description'=>'搜索商品货号','example'=>'','default'=>''],
            'is_usexunsearch' => ['type'=>'int','valid'=>'','description'=>'默认使用搜索引擎来操作','example'=>'','default'=>'1'],

            'approve_status' => ['type'=>'string','valid'=>'','description'=>'商品上架状态','example'=>'','default'=>''],
            'page_no' => ['type'=>'int','valid'=>'numeric','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'numeric','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'int','valid'=>'numeric','description'=>'排序方式','example'=>'','default'=>'modified_time desc,list_time desc'],
            'fields' => ['type'=>'field_list','valid'=>'','description'=>'要获取的商品字段集','example'=>'','default'=>''],
        );
        $return['extendsFields'] = ['promotion','store'];
        return $return;
    }

    private function __getFilter($params)
    {
        $filterCols = ['modified_time_end','modified_time_start','item_id','shop_id','shop_cat_id','cat_id','search_keywords','approve_status','brand_id','prop_index','is_selfshop','bn'];

		//增加展示二级类目方法，当二级类目传递数组过来直接赋值，避免被覆盖2015/12/9雷成德
        if($params['class']=='two')
        {
        	$cat_id['cat_id']=$params['cat_id'];
        }
        //完毕
        foreach( $filterCols as $col )
        {
            if( $params[$col] )
            {
                $params[$col] = trim($params[$col]);
				//查询条件新增了tax 	和sea_region
				$allow_fields = array('item_id','brand_id','shop_cat_id','prop_index','tax','sea_region');
                if( in_array($col,$allow_fields ) ) {
                    if( $col == 'shop_cat_id')
                    {
                        foreach( explode(',',$params[$col]) as $v)
                        {
                            $val = intval($v);
                            $shopCatId[]= ','. $val .',';
                        }
                        $params['shop_cat_id'] = $shopCatId;
                    }
                    else
                    {
                        $params[$col] = explode(',',$params[$col]);
                    }
                }
                $filter[$col] = $params[$col];
            }
        }
        //获取指定商铺类目下的所有类目 
        if($params['search_shop_cat_id'] >0 && $params['shop_id']){
            $catParams = array();
            $catParams['shop_id'] = $params['shop_id'];
            $catParams['parent_id'] = $params['search_shop_cat_id'];
            $catParams['fields'] = 'cat_id';
            $catIds = app::get('sysshop')->rpcCall('shop.cat.get', $catParams);
            $catIds = array_column($catIds, 'cat_id');
            $catIds[] = (int)$params['search_shop_cat_id'];
            $catIds = array_unique($catIds);
            $shopCatIds = array();
            foreach ($catIds as $v){
                $shopCatIds[] = ','. $v .',';
            }
            $filter['shop_cat_id'] = $shopCatIds;
        }
        if(isset($params['use_platform']) && $params['use_platform'] != null )
        {
            $filter['use_platform'] = explode(',',$params['use_platform']);
        }

        if($params['max_price'] && $params['min_price'])
        {
            $filter['price|between'] = [$params['min_price'],$params['max_price']];
        }
        elseif($params['max_price'] && !$params['min_price'])
        {
            $filter['price|sthan'] = $params['max_price'];
        }
        elseif (!$params['max_price'] && $params['min_price'])
        {
            $filter['price|bthan'] = $params['min_price'];
        }

        if( $filter['prop_index'] )
        {
            foreach( (array)$filter['prop_index'] as $key=>$row )
            {
                $val = explode('_', $row);
                $propIndex[$val[0]][] = $val[1];
            }
            $filter['prop_index'] = $propIndex;
        }
        //商品货号
        if($params['bn'])
        {
            $filter['bn|has'] = $params['bn'];
        }
		if($params['class']=='two')
        {
        	$filter['cat_id'] =$cat_id['cat_id'];
        }
        
        //商品业务类型
        intval($params['tax']) >0 and  $filter['tax'] = $params['tax'];
        //商品业务仓库
        intval($params['sea_region']) >0 and  $filter['sea_region'] = $params['sea_region'];

		//2016/5/17  lcd 下线ERP专用
        if( $params['erp_api'] ) {
			$filter['modified_time|bthan'] = $params['modified_time_start'];
            $filter['modified_time|lthan'] = $params['modified_time_end'];
        }
        return $filter;
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
    
    function  __assemble($params) {
        if(!is_array($params))  return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params AS $key=>$val){
            if(is_null($val))   continue;
            if(is_bool($val))   $val = ($val) ? 1 : 0;
            $sign .= $key . (is_array($val) ? $this->__assemble($val) : $val);
        }
        return $sign;
    }
    
    function __httpgetids($basic_params){
		$api_params = array( 
            'sign_type' => 'MD5',  'v' => 'v1',  'format' => 'json', 'timestamp' =>time(), 
            'method'	=> 'item.xunsearch.itemids'
        );
        $params = array_merge($api_params, $basic_params); 
        $token="1447906ce053c67e661dfe73cbe804eab189ea644cbe22ced58784564bae0485";
        //密钥：
       // $token="180c5bc2d9d1a1e95fea4860266fe3e07a5b4f3f45bbf6354fa216ec9e0232b4";
        $params['sign'] = strtoupper(md5(strtoupper(md5($this->__assemble($params))).$token));
        //$url = 'http://www.mjm.net/index.php/api';
        $url = 'http://www.gojmall.com/api';
		$data_string = json_encode($params);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_string)));
        $result =  curl_exec($ch);
		$data = $result ? json_decode($result, true) : false; 
		//print_r($data);
		if($data && isset($data["result"]) && !empty($data["result"])){
			return (array)$data["result"];
		}
    	return false;
    }

    public function getList($params)
    {
    	if(isset($params['ajaxkeyword']) && intval($params['ajaxkeyword']) === 1 && trim($params["search_keywords"]) != ""){
    		//采用Ajax 来获取数据；
	    	$xs_condition = array("page_no"=>1 ,"page_size"=> 10 ,"order_by"=>  '' ,"use_platform"=>"FINDS","search_keywords"=> trim($params["search_keywords"])); 
			//先搜索商品的分类； 可以通过拼音及简写来搜索分类 出来； $getItemIds = app::get('topc')->rpcCall('item.xunsearch.itemids',$xs_condition,'buyer');
	        $getCatIds  = $this->__httpgetids($xs_condition); //ajax 获取 搜索引擎返回的数据；
	        return $getCatIds ? $getCatIds : false;
    	}
        $objMdlItem = app::get('sysitem')->model('item');
        $row = $params['fields']['rows'];
		//增加二级分类和一级分类2016/1/11 lcd    -----by xch edit 17.02.08
		switch ( $params['class'] ) {
			case "two":
				$params['cat_id'] = $this->__get_chirendIds($params['cat_id'] , "cat_id");//取所有二级分类下的第三级类
				break;
			case "one":
				$cat_ids=array();
	        	$ids = $this->__get_chirendIds($params['cat_id'] , "cat_id");  //取所有二级分类 
	        	if($ids && !empty($ids)){
	        		foreach ($ids as $val) {
		        		$cat_id = $this->__get_chirendIds($val, "cat_id"); //取所有二级分类下的所有第三级类
		        		$cat_id and $cat_ids = array_merge($cat_ids, $cat_id);
		        	}
	        	}
	        	$params['cat_id']=$cat_ids; $params['class']='two';
				break;
		}
        //新增列表页按业务类型和仓库来筛选  by xch 
        if(isset($params['d_t']) && intval($params['d_t']) >0){
        	$params['tax']=intval($params['d_t']);
        }
        if(isset($params['d_se']) && intval($params['d_se']) >0){
        	$params['sea_region']=intval($params['d_se']);
        }

        //分页使用
        $pageSize = $params['page_size'] ? $params['page_size'] : 40;
        $pageNo = $params['page_no'] ? $params['page_no'] : 1;
        $max = 1000000;
        if($pageSize >= 1 && $pageSize < 500 && $pageNo >=1 && $pageSize*$pageNo < $max) {
            $limit = $pageSize;
            $page = ($pageNo-1)*$limit;
        }
        $orderBy = (isset($params['orderBy']) && trim($params['orderBy']) != "") ? $params['orderBy'] :"modified_time desc,list_time desc";
        $last_result= null;
        $itemIds = null;
        //开启搜索引擎搜索功能；
        $hightLightwords = false;
        $last_snumber = 0 ;
        $is_xs_success = false;
        //如果传入参数只有Item_id,并且Item_id 不止一个时，则不走搜索引擎；$itemsList
        $is_xunsearch = isset($params["is_usexunsearch"]) ? intval($params["is_usexunsearch"]): 1; //默认可以使用搜索引擎
        if(isset($params["item_id"]) && trim($params["item_id"])!=""){
        	$itemids = explode(",",trim($params["item_id"]));
        	if(count($itemids) >1){$is_xunsearch = 0; 	}
        }
        if($is_xunsearch && $is_xunsearch===1){
        	$last_condition = $this->__getFilter($params);   // cat_id (array)  approve_status = onsale  use_platform =0,1    tax =1  brand_id = 22
        	if($last_condition && !empty($last_condition)){
        		$last_condition["cat_id"] and $last_condition["cat_id"] = is_array($last_condition["cat_id"]) ?  implode("," ,$last_condition["cat_id"]) : $last_condition["cat_id"];
        		$last_condition["use_platform"] and $last_condition["use_platform"] = implode("," ,$last_condition["use_platform"]);
        	}
        	unset($last_condition["approve_status"]);
        	$xs_condition = array("page_no"=>$pageNo ,"page_size"=> $pageSize ,"order_by"=>  $orderBy); 
        	$xs_condition = array_merge($last_condition,$xs_condition);		//最后的搜索条件
        	//$getItemIds = app::get('topc')->rpcCall('item.xunsearch.itemids',$xs_condition,'buyer');
        	$getItemIds  = $this->__httpgetids($xs_condition); //ajax 获取 搜索引擎返回的数据；
        	if(isset($getItemIds["total"]) && intval($getItemIds["total"]) >0 ){
        		$is_xs_success = intval($getItemIds["xs_success"]) > 0 ? true : false;
        		$last_snumber = intval($getItemIds["total"]);	//搜索结果
        		$itemIds = isset($getItemIds["items"]) ?$getItemIds["items"] : false ;	//商品ID
        		$hightLightwords = isset($getItemIds["hightwords"]) ? $getItemIds["hightwords"] : false ; //高亮标题
        	}
        }
        //引擎 搜索成功；
        if($is_xs_success && $last_snumber >0  && $itemIds && count($itemIds) >0 ){
        	$list = app::get('sysitem')->model('item')->getList($row,array('item_id|in'=>$itemIds,'approve_status'=>'onsale'));
        	if($list && !empty($list) && $hightLightwords){
        		foreach ($list as $k => $vf){
        			$item_id = intval($vf["item_id"]);
        			if($item_id && isset($hightLightwords[$item_id]) && !empty($hightLightwords[$item_id])) 
        				$list[$k]["title"] = trim($hightLightwords[$item_id]);
        		}
        	}
        	$last_result = array("total_found"=> $last_snumber , "list"=> $list);
        }else{
        	$last_result = kernel::single('search_object')->instance('item')
			            ->page($page, $limit)
			            ->buildExcerpts($params['buildExcerpts'], 'title')
			            ->orderBy($orderBy)
			            ->search($row,$this->__getFilter($params));
			$itemIds = array_column($last_result['list'], 'item_id');
        }
        
        if( $itemIds && $params['fields']['extends']['store'] )
        {
            $itemStore =  kernel::single('sysitem_item_info')->getItemStore($itemIds);
        }
        if( $itemIds && $params['fields']['extends']['promotion'] ) {
            $promotionTag = app::get('sysitem')->model('item_promotion')->getList('*',array('item_id'=>$itemIds));
            $promotionArr = array();
            foreach ($promotionTag as $key => $v) {
                $promotionArr[$v['item_id']][$v['promotion_id']] = $v;
            }
        }
        if( $itemStore || $promotionTag ) {
            foreach ($last_result['list'] as $key => &$value) {
                if( $itemStore ) {
                    $value['store'] = $itemStore[$value['item_id']]['store'];
                    $value['freez'] = $itemStore[$value['item_id']]['freez'];
                }

                if( $promotionTag )  {
                    $value['promotion'] = $promotionArr[$value['item_id']];
                }
            }
        }
        return $last_result;
    }
}
