<?php
/**
 * 接口作用说明
 * item.search.filterItems
 */
class sysitem_api_search_filterItems {

    public $apiDescription = '根据搜索条件，列出渐进式的筛选项';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
            'item_id' => ['type'=>'string','valid'=>'','description'=>'商品id，多个id用，隔开','example'=>'2,3,5,6','default'=>''],
            'shop_id' => ['type'=>'int','valid'=>'','description'=>'店铺id','example'=>'','default'=>''],
            'shop_cat_id' => ['type'=>'int','valid'=>'','description'=>'店铺自有类目id','example'=>'','default'=>''],
            'cat_id' => ['type'=>'int','valid'=>'','description'=>'商城类目id','example'=>'','default'=>''],
            'approve_status' => ['type'=>'string','valid'=>'','description'=>'商品上架状态','example'=>'','default'=>''],
            'search_keywords' => ['type'=>'string','valid'=>'','description'=>'搜索商品关键字','example'=>'','default'=>''],
            'use_platform' => ['type'=>'string','valid'=>'','description'=>'商品使用平台(0=全部支持,1=仅支持pc端,2=仅支持wap端)如果查询不限制平台，则不需要传入该参数','example'=>'1','default'=>'0'],
        );
        return $return;
    }

    private function __getFilter($params)
    {
        $filterCols = array('item_id','shop_id','shop_cat_id','cat_id','search_keywords','use_platform','approve_status','tax','sea_region');
        foreach( $filterCols as $col )
        {
            if( $params[$col] )
            {
                $params[$col] = trim($params[$col]);

                if( in_array($col,array( 'item_id','use_platform')) )
                {
                    $params[$col] = explode(',',$params[$col]);
                }
                $filter[$col] = $params[$col];
            }
        }

        return $filter;
    }

    public function get($params)
    {
        $objMdlItem = app::get('sysitem')->model('item');

        $params['approve_status'] = 'onsale';
        if( !$params['cat_id'] )
        {
            $data = kernel::single('search_object')->instance('item')
                ->page(0,1)
                ->groupBy('cat_id')
                ->orderBy('count desc')
                ->search('cat_id,count(*) as count',$this->__getFilter($params));

            $catId = $data['list'][0]['cat_id'];
        }
        else
        {
            $catId = $params['cat_id'];
        }

        if( !$params['search_keywords'] )
        {
            $catInfo = app::get('sysitem')->rpcCall('category.cat.get.info',array('cat_id'=>$catId,'fields'=>'cat_name'));
            $filterItems['keyword'] = $catInfo[$catId]['cat_name'];
        }
        else
        {
            $shopParams['shop_name'] = $params['search_keywords'];
            $shopinfo = app::get('sysitem')->rpcCall('shop.get.search',$shopParams);
            //去除被关闭的店铺
            $shopList = array();
            foreach ($shopinfo as $shop)
            {
                if($shop['status'] !='dead')
                {
                    $shopList[] = $shop;
                }
            }
            $filterItems['shopInfo'] = $shopList[0];
            $filterItems['keyword'] = $params['search_keywords'];
        }
		//品牌
        $brandIdArr = kernel::single('search_object')->instance('item')
            ->page(0,100)
            ->groupBy('brand_id')
            ->search('brand_id',$this->__getFilter($params));
        if( $brandIdArr['list'] )
        {
            $brandFilter['brand_id'] = implode(',', array_column($brandIdArr['list'],'brand_id'));
            $brandFilter['fields'] = 'brand_id,brand_name';
            $brand = app::get('sysitem')->rpcCall('category.brand.get.list', $brandFilter);
            if( $brand )
            {
                $filterItems['brand'] = $brand;
            }
        }
        
        //仓库ID  byxch   
        $seaIdArr = kernel::single('search_object')->instance('item')
            ->page(0,100)
            ->groupBy('sea_region')
            ->search('sea_region',$this->__getFilter($params));
        if($seaIdArr['list'])
        {
        	foreach ($seaIdArr['list'] as $k => $sea)
            {
                $seid =isset($sea['sea_region']) ? intval($sea['sea_region'])  : 0;
                $seid and $filterItems['sea_region'][$seid] = $seid;
            }
        }   
        if( $catId ) {
            $props = kernel::single('syscategory_data_props')->getNatureProps($catId);
        }

        if( $props ) {
            foreach( $props as $key=>$row ) {
                foreach( $row['prop_value'] as $k=>$value )
                {
                    $props[$key]['prop_value'][$k]['prop_index'] = $row['prop_id'].'_'.$value['prop_value_id'];
                }
            }
            $filterItems['props'] = $props;
        }
        $filterItems['cat_id'] = $catId;
        return $filterItems;
    }
}

