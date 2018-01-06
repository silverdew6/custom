<?php

/**
 * 搜索
 *
 */
if (!defined('IN_ECM')) {
    die('Hacking attempt');
}

class searchBase {

    //是否开启分面搜索
    var $_open_face = true;
    //全文搜索对象
    var $_xs_search;
    //文档对象
    var $_xs_doc;
    //全文搜索到的商品ID数组
    var $_indexer_ids = array();
    //全文搜索到的品牌数组
    var $_indexer_brands = array();
    //全文检索到的商品分类数组
    var $_indexer_cates = array();
    //全文搜索结果总数
    var $_indexer_count;
    //搜索结果中品牌分面信息
    var $_face_brand = array();
    //搜索结果中品牌分面信息
    var $_face_attr = array();
    //搜索结果分类分面信息
    var $_cate_name;
    var $_face_cate = array();
    var $_xs_error = 0;

    function __construct($config = array()) {
	$this->searchBase($config);
    }

    function searchBase($config = array()) {
	$this->_createXS($config);
    }
    
    /**
     * 创建全文搜索对象，并初始化基本参数
     * @param number $pagesize 每页显示商品数
     * @param string $appname 全文搜索INI配置文件名
     */
    function _createXS($pagesize) {
	$subject = isset($config['subject']) ? $config['subject'] : 'dd4';
	require_once(ROOT_PATH . '/includes/xs/lib/XS.php');
	try {
	    $obj_doc = new XSDocument();
	    $obj_xs = new XS($subject);
	    $this->_xs_search = $obj_xs->search;
	    $this->_xs_search->setCharset(CHARSET);
	    $this->_xs_doc = $obj_doc;
	} catch (XSException $e) {
	    $this->_xs_error = 1;
	    //echo $e . "n" . $e->getTraceAsString() . "n";
	}

    }
    
    /**
     * 设置检索条数-分页
     * @param int $pagesize
     * @param int $page
     */
    function _setLimit($page = 0, $pagesize = 20){
	$pagesize = $pagesize>0 ? $pagesize : 20;
	if (is_numeric($page) && $page > 0) {
	    $curpage = intval($page);
	    $start = ($curpage - 1) * $pagesize;
	} else {
	    $start = 0;
	}
	$this->_xs_search->setLimit($pagesize, $start);
    }

    /**
     * 从全文索引库搜索关键词
     * @param unknown $condition 条件
     * @param unknown $order 排序
     * @param number $pagesize 每页显示商品数
     * @return
     */
    public function getIndexerList($condition = array(), $order = array(), $page = 1, $pagesize = 20) {
	
	if($condition['layer']<4){
	    $cate_key = 'cate_id_'.($condition['layer']+1);
	}else{
	    $cate_key = 'cate_id_1';
	}
	$this->_cate_name = $cate_key;

	//全文搜索初始化
//        $this->_createXS($pagesize);
	//设置搜索内容
	$this->_setQueryXS($condition, $order);
	
	//设置检索条数
	$this->_setLimit($page,$pagesize);
	
	//设置检索分面
	if ($this->_open_face) {
	    $this->_setFacets(array($this->_cate_name,'brand'));
	}

	//执行搜索
	$result = $this->_searchXS();

	if ($result) {
	    return array($this->_indexer_ids, $this->_indexer_count);
	} else {
	    return false;
	}
    }
    
    /**
     * 设置检索分面
     */
    function _setFacets($config = array()){
	if(!empty($config)){
	    $this->_xs_search->setFacets($config,TRUE);
	}
    }

    /**
     * 设置全文检索查询条件
     * @param unknown $condition
     * @param array $order
     */
    function _setQueryXS($condition, $order) {
	$this->_xs_search->setQuery('');//清除搜索条件
	$this->_xs_search->setQuery('closed:0 AND if_show:1');
	if (isset($condition['keyword'])) {
	    $this->_xs_search->addQueryString(is_null($condition['keyword']) ? '' : implode(" ",$condition['keyword']));
	    $this->_xs_search->addWeight('goods_name', is_null($condition['keyword']) ? '' : implode(" ",$condition['keyword']));
	}
	if (isset($condition['cate_id'])) {
	    $this->_xs_search->addQueryString('cate_id_' .$condition['layer'] . ':' . $condition['cate_id']);
//	    $this->_xs_search->setMultiSort(array('sales'=>false,'credit'=>false,'collects'=>false,'views'=>false,'add_time'=>false));
	}
	if (isset($condition['brand'])) {
	    $this->_xs_search->addQueryString('brand' . ':' . $condition['brand']);
	}
	if (isset($condition['store_id'])) {
	    $this->_xs_search->addQueryString('store_id' . ':' . $condition['store_id']);
	}
	if (isset($condition['store_name'])) {
	    $this->_xs_search->addQueryString('store_name' . ':' . $condition['store_name']);
	}
	if (isset($condition['goods_id'])) {
	    $this->_xs_search->addQueryString('goods_id' . ':' . $condition['goods_id']);
	}
//	if (isset($condition['price'])) {
//	    $min = $condition['price']['min']>0?$condition['price']['min']:null;
//	    $max = $condition['price']['max']>0?$condition['price']['max']:null;
//	    ($min ==null&&$max==null)&&$min = 0;
//	    if($max > 0){
//		$this->_xs_search->addRange('min_price',$min,null);
//		$this->_xs_search->addRange('max_price',null,$max);
//	    }else{
//		$this->_xs_search->addRange('min_price', $min , $max);
//	    }
//	}
	if (is_array($condition['attr_id'])) {
	    foreach ($condition['attr_id'] as $attr_id) {
		$this->_xs_search->addQueryString('attr_id' . ':' . $attr_id);
	    }
	}
	if($order){
//	    $this->_xs_search->setSort($order['key'], $order['value']);
	    $this->_xs_search->setMultiSort($order);
	}
//         echo $this->_xs_search->getQuery();
    }

    

    /**
     * 执行全文搜索
     */
    function _searchXS() {
	try {
//            $goods_class = H('goods_class') ? H('goods_class') : H('goods_class', true);

	    $docs = $this->_xs_search->search();
	    $count = $this->_xs_search->getLastCount();
	    $goods_ids = array();
	    $goods_data = array();
	    $brands = array();
	    $cates = array();
	    foreach ($docs as $k => $doc) {
		$goods_ids[] = $doc->goods_id;
		$tmp_goods = array();
		$tmp_goods['goods_id'] = $doc->goods_id;
		$tmp_goods['goods_name'] = $doc->goods_name;
		$tmp_goods['sku'] = $doc->sku;
		$tmp_goods['cate_id'] = $doc->cate_id;
		$tmp_goods['store_id'] = $doc->store_id;
		$tmp_goods['add_time'] = $doc->add_time;
		$tmp_goods['min_price'] = $doc->min_price;
		$tmp_goods['max_price'] = $doc->max_price;
		$tmp_goods['min_org_price'] = $doc->min_org_price;
		$tmp_goods['max_org_price'] = $doc->max_org_price;
		$tmp_goods['discount'] = $doc->discount;
		$goods_data[$doc->goods_id] = $tmp_goods;
//                 if ($doc->brand_id > 0) {
//                     $brands[$doc->brand_id]['brand_id'] = $doc->brand_id;
//                     $brands[$doc->brand_id]['brand_name'] = $doc->brand_name;                    
//                 }
//                 if ($doc->gc_id > 0) {
//                     $cates[$doc->gc_id]['gc_id'] = $doc->gc_id;
//                     $cates[$doc->gc_id]['gc_name'] = $goods_class[$doc->gc_id]['gc_name'];                    
//                 }
	    }
	    $this->_indexer_ids = $goods_ids;
	    $this->_search_data = $goods_data;
	    $this->_indexer_count = $count;
	    $this->_indexer_brands = $brands;
	    $this->_indexer_cates = $cates;

	    //读取分面结果
	    if ($this->_open_face) {
		$this->_face_cate = $this->_xs_search->getFacets($this->_cate_name);
		$this->_face_brand = $this->_xs_search->getFacets('brand');
//		$this->_face_attr = $this->_xs_search->getFacets('attr_id');
//		$this->_parseFaceAttr($this->_face_attr);
	    }
	} catch (XSException $e) {
	    if (Conf::get('debug')) {
//                showMessage($e->getMessage(),'','html','error');
	    } else {
//                Log::record($e->getMessage()."\r\n".$sql,Log::ERR);
		return false;
	    }
	}
	return true;
    }
    
    /**
     * 获取分面结果
     */
     function getFacetsResult($param){
        $data = array(
            'total_count' => 0,
            'by_category' => array(),
            'by_brand' => array(),
            'by_region' => array(),
            'by_price' => array()
        );
        $cate_facets = $this->_face_cate;
        $lang_mod = &m('commonMultiLan');
        $category_mod = & bm('gcategory');
        $children = $category_mod->get_children($param['cate_id'], true);
        foreach ($cate_facets as $ck => $cf){
	    if($ck>0 && !empty($children[$ck]['cate_name'])){
		$data['by_category'][$ck] = array(
		    'cate_id' => $ck,
		    'cate_name' => $children[$ck]['cate_name'],
		    'count' => $cf
		);
	    }
        }
        //价格
//        $minPrice = $this->_xs_search->setSort('min_price',true)->setLimit(1)->search();
//        $min = $minPrice[0]->price;
//        $maxPrice = $this->_xs_search->setSort('max_price',false)->setLimit(1)->search();
//        $max = $maxPrice[0]->price;
////print_r($maxPrice[0]->price);
//        $step = max(ceil(($max - $min) / 5), 50);
//        $i = 0;$tmin = 0;$tmax = 0;
//
//        while($tmax<$max){
//                $tmin = $min+($i*$step);
//		$tmax = $min + (($i+1) * $step);
//                $data['by_price'][] = array(
//                        'count' => 0,
//                        'min' => $tmin,
//                        'max' => $tmax
//                );
//                $i++;
//        }
//print_r($data);
        return $data;
    }

    /**
     * 处理属性多面信息
     */
    function _parseFaceAttr($face_attr = array()) {
	if (!is_array($face_attr))
	    return;
	$new_attr = array();
	foreach ($face_attr as $k => $v) {
	    $new_attr = array_merge($new_attr, explode('_', $k));
	}
	$this->_face_attr = $new_attr;
    }

    public function __get($key) {
	return $this->$key;
    }

}
