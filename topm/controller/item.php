<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topm_ctl_item extends topm_controller {

    public function __construct($app)
    {
        parent::__construct();
        $this->setLayoutFlag('product');
    }

    private function __setting() {
        $setting['image_default_id']= kernel::single('image_data_image')->getImageSetting('item');
        return $setting;
    }
	/**
	 * 获取单个商品信息及商品描述内容；
	 */
    public function index()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) )
        {
            return redirect::action('topm_ctl_default@index');
        }
		
		$pagedata['title'] = "商品详情";
        if( userAuth::check() )
        {
            $pagedata['nologin'] = 1;
        }

        $pagedata['user_id'] = userAuth::id();

        $pagedata['image_default_id'] = $this->__setting();

        $params['item_id'] = $itemId;
        $params['use_platform'] = 1;
        $params['fields'] = "*,item_desc.wap_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        $detailData = app::get('topm')->rpcCall('item.get',$params);
        //print_r($detailData);

        if(!$detailData)
        {
            $pagedata['error'] = "商品过期不存在";
            return $this->page('topm/items/error.html', $pagedata);
        }
        
        //业务类型促销优惠提醒；
        $current_tax =  isset($detailData["tax"]) ? intval($detailData["tax"]) :0;
        switch ( $current_tax ) {
        	case 1:$mian_title = '全场<i class="hight">完税商品</i>订单购买满￥50元即可享免运费！';break;
			case 2:$mian_title = '全场<i class="hight">保税商品</i>订单促销期间全部包邮！';break;
			case 3:$mian_title = '全场<i class="hight">直邮商品</i>订单购买满￥150元或购满2件包邮！';break;
			default : $mian_title = '全场<i class="hight">直邮商品</i>订单购买满150元包邮！'; break;
		} $pagedata["zeropostfee_desc"] = $mian_title;
		//默认SKU——ID
        if(isset($detailData['sku']) && count($detailData['sku']) == 1) {
        	$sku_s = array_keys($detailData['sku']);
        	$detailData['default_sku_id'] = !empty($sku_s) ? reset($sku_s) :  0; //取默认的一个SKU 产品ID数据；
        }
		//商品是否可销售
        $detailData['valid'] = $this->__checkItemValid($detailData);

        if($detailData['use_platform'] != 2 && $detailData['use_platform'] != 0)
        {
            redirect::action('topm_ctl_item@index',array('item_id'=>$itemId))->send();exit;
        }
        //相册图片
        if( $detailData['list_image'] ){
            $detailData['list_image'] = explode(',',$detailData['list_image']);
        }

        //获取商品的促销信息
        $promotionInfo = app::get('topc')->rpcCall('item.promotion.get', array('item_id'=>$itemId));
        
        if($promotionInfo){
            foreach($promotionInfo as $vp)
            {
                $basicPromotionInfo = app::get('topc')->rpcCall('promotion.promotion.get', array('promotion_id'=>$vp['promotion_id'], 'platform'=>'wap'));
                if($basicPromotionInfo['valid']===true)
                {
                    $pagedata['promotionDetail'][$vp['promotion_id']] = $basicPromotionInfo;
                }
            }
        }
        $pagedata['promotion_count'] = count($pagedata['promotionDetail']);

        //活动促销(如名字叫团购)
        $activityDetail = app::get('topm')->rpcCall('promotion.activity.item.info',array('item_id'=>$itemId,'valid'=>1),'buyer');
        if($activityDetail) {
            $pagedata['activityDetail'] = $activityDetail;
        }
        
        //当前商品是否有优惠券 ，然后对登录用户计算是否已领取(领取操作，且不与其它促销冲突)
        $couponDetail = app::get('topm')->rpcCall('item.promotion.usecoupon',array('item_id'=>$itemId,'shop_id'=>$detailData["shop_id"]),'buyer');
        if($couponDetail) {
            $pagedata['couponDetail'] = $couponDetail; 
        }
		//获取商品属性，规格内容
        $detailData['spec'] = $this->__getSpec($detailData['spec_desc'], $detailData['sku']);
        
        //获取商品属性
        $props = '';
        if($detailData['spec_desc']) {
            foreach ($detailData['spec_desc'] as $k=>$v)  {
                $str = '';
                $str = $detailData['spec']['specName'][$k] . '有';
                $mstr = '';
                foreach ($v as $spec_value_id => $spec_value) {
                    $mstr .= $spec_value['spec_value'] . '，';
                }
                $str = $str . $mstr;
                $props .= $str;
            }
            $props = rtrim($props, '，');
        }
        
        //整个商品信息
        $pagedata['item'] = $detailData;
        //print_r($detailData);
        $pagedata['shopCat'] = app::get('topm')->rpcCall('shop.cat.get',array('shop_id'=>$pagedata['item']['shop_id']));
        $pagedata['shop'] = app::get('topm')->rpcCall('shop.get',array('shop_id'=>$pagedata['item']['shop_id']));
        $pagedata['next_page'] = url::action("topm_ctl_item@index",array('item_id'=>$itemId));

        if(empty($pagedata['item']['item_id'])) {
            return $this->page('topm/items/goodsEmpty.html');
        }

		$last_cat_id = isset($detailData['cat_id']) ? intval($detailData['cat_id']) :0 ;
        //设置此页面的seo
        $brand = app::get('topm')->rpcCall('category.brand.get.info',array('brand_id'=>$detailData['brand_id']));
        $cat = app::get('topm')->rpcCall('category.cat.get.info',array('cat_id'=>$last_cat_id));
        $currentCat_info = isset($cat[$last_cat_id]) ? $cat[$last_cat_id] : false ;
        //查看当前端口同分类条件下的商品
        if($currentCat_info && !empty($currentCat_info) ){
        	//搜索同类别下的产品列表信息。
        	$searchParams = array("cat_id"=> $last_cat_id,'fields' => 'item_id,title,image_default_id,price,brand_id,tax,sea_region','page_no'=>1,'page_size'=>8);
        	$looktuijian = app::get('topm')->rpcCall('item.search',$searchParams);
        	if($looktuijian && $looktuijian["total_found"] >0){
        		$looktuijian["cat_id"]=$last_cat_id;
        		$looktuijian["cat_name"]=isset($currentCat_info['cat_name']) ? $currentCat_info['cat_name'] : "";
        		$pagedata['secondSearchList'] =  $looktuijian ? $looktuijian : false;
        	}
        }
        
        $seoData = array(
            'item_title' => $detailData['title'],
            'shop_name' =>$pagedata['shop']['shop_name'],
            'item_bn' => $detailData['bn'],
            'item_brand' =>isset( $brand['brand_name']) ?  $brand['brand_name'] : "",
            'item_cat' =>isset($currentCat_info['cat_name']) ? $currentCat_info['cat_name'] : "",
            'sub_title' =>$detailData['sub_title'],
            'sub_props' => $props
        );
        seo::set('topm.item.detail',$seoData); 		//get_default_seo   get_seo_conf('topm.item.detail')
		//echo '<pre>';print_r($pagedata);exit();
        return $this->page('topm/items/index.html', $pagedata);
    }

    private function __checkItemValid($itemsInfo)
    {
        if( empty($itemsInfo) ) return false;
        //违规商品
        if( $itemsInfo['violation'] == 1 ) return false;
        //未启商品
        if( $itemsInfo['disabled'] == 1 ) return false;
        //未上架商品
        if($itemsInfo['approve_status'] == 'instock' ) return false;
        //库存小于或者等于0的时候，为无效商品
        //if($itemsInfo['realStore'] <= 0 ) return false;
        return true;
    }


    private function __getSpec($spec, $sku)
    {
        if( empty($spec) ) return array();

        foreach( $sku as $row )
        {
            $key = implode('_',$row['spec_desc']['spec_value_id']);

            if( $key )
            {
                $result['specSku'][$key]['sku_id'] = $row['sku_id'];
                $result['specSku'][$key]['item_id'] = $row['item_id'];
                $result['specSku'][$key]['price'] = $row['price'];
                $result['specSku'][$key]['store'] = $row['realStore'];
                if( $row['status'] == 'delete')
                {
                    $result['specSku'][$key]['valid'] = false;
                }
                else
                {
                    $result['specSku'][$key]['valid'] = true;
                }

                $specIds = array_flip($row['spec_desc']['spec_value_id']);
                $specInfo = explode('、',$row['spec_info']);
                foreach( $specInfo  as $info)
                {
                    $id = each($specIds)['value'];
                    $result['specName'][$id] = explode('：',$info)[0];
                }
            }
        }
        return $result;
    }
    //商品照片
    public function itemPic()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) )
        {
            return redirect::action('topm_ctl_default@index');
        }

        $pagedata['image_default_id'] = $this->__setting();
        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.wap_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        $detailData = app::get('topm')->rpcCall('item.get',$params);
        $pagedata['title'] = "商品描述";

        $pagedata['itemPic'] = $detailData;
        return $this->page('topm/items/itempic.html', $pagedata);
    }
    //商品参数
    public function itemParams()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) )
        {
            return redirect::action('topm_ctl_default@index');
        }

        $pagedata['image_default_id'] = $this->__setting();
        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.wap_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        $detailData = app::get('topm')->rpcCall('item.get',$params);

        $pagedata['itemParams'] = $detailData['params'];
        $pagedata['title'] = "商品参数";
        return $this->page('topm/items/itemparams.html', $pagedata);
    }

    public function getItemRate()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) ) return '';

        $pagedata =  $this->__searchRate($itemId);
        $pagedata['item_id'] = $itemId;

        $pagedata['title'] = '产品评价';
        return $this->page('topm/items/rate/index.html', $pagedata);
    }

    public function getItemRateList()
    {
        $itemId = intval(input::get('item_id'));

        $pagedata =  $this->__searchRate($itemId);

        if( input::get('json') )
        {
            $data['html'] = view::make('topm/items/rate/list.html',$pagedata)->render();
            $data['pagers'] = $pagedata['pagers'];
            $data['success'] = true;
            return response::json($data);exit;
        }

        return view::make('topm/items/rate/list.html',$pagedata);
    }

    private function __searchRate($itemId)
    {
        $current = input::get('pages',1);
        $limit = 10;
        $params = ['item_id'=>$itemId,'page_no'=>intval($current),'page_size'=>intval($limit),'fields'=>'*'];

        if( input::get('query_type') == 'content'  )
        {
            $params['is_content'] = true;
        }
        elseif( input::get('query_type') == 'pic' )
        {
            $params['is_pic'] = true;
        }

        $data = app::get('topm')->rpcCall('rate.list.get', $params);
        foreach($data['trade_rates'] as $k=>$row )
        {
            if($row['rate_pic'])
            {
                $data['trade_rates'][$k]['rate_pic'] = explode(",",$row['rate_pic']);
            }

            $userId[] = $row['user_id'];
        }

        $pagedata['rate']= $data['trade_rates'];
        if( $userId )
        {
            $pagedata['userName'] = app::get('topm')->rpcCall('user.get.account.name',array('user_id'=>$userId),'buyer');
        }

        //处理翻页数据
        $filter = input::get();
        $pagedata['filter'] = $filter;
        $filter['pages'] = time();
        if($data['total_results']>0) $total = ceil($data['total_results']/$limit);
        $current = $total < $current ? $total : $current;
        $pagedata['pagers'] = array(
            //'link'=>url::action('topm_ctl_item@getItemRateList',$filter),
            'current'=>$current,
            'total'=>$total,
        );

        return $pagedata;
    }
}

