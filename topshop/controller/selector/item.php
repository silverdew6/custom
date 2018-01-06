<?php

class topshop_ctl_selector_item extends topshop_controller {

    public $limit = 14;
    
    public $taxs = array("1"=> "完税",/*"2"=> "保税",*/"3"=> "直邮");

    public function loadSelectGoodsModal()
    {
        $isImageModal = true;
        $taxRegionModal = app::get('sysshop')->model("ware_region");
        $taxs = isset($this->taxs) ? $this->taxs : array("1"=> "完税","2"=> "保税","3"=> "直邮");
        foreach($taxs as $k=> $vx){
        	$taxregions[$k] = $taxRegionModal->getList('id,name,tax',array("shop_id"=>$this->shopId,"tax"=> $k ));
        }
        // $pagedata = $this->searchItem(false);
        $defaultCanku = isset($taxregions) ? reset($taxregions["1"]) :false; //默认取第完税第一个仓库；
        $pagedata['default_taxregion'] = '完税-'.$defaultCanku["name"];
        $pagedata['imageModal'] = true;
        $pagedata['textcol'] = input::get('textcol');
        $pagedata['view'] = input::get('view');
        $pagedata['taxList'] = array("taxs"=> $taxs,"taxregs"=> $taxregions) ;
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.authorize.cat',array('shop_id'=>$this->shopId));
        return view::make('topshop/selector/item/index.html', $pagedata);
    }

    public function formatSelectedGoodsRow()
    {
        $itemIds = input::get('item_id');
        $textcol = input::get('textcol');
        $extendView = input::get('view');
        $searchParams['fields'] = 'item_id,title,image_default_id,price,brand_id,tax,sea_region';
        $searchParams['item_id'] = implode(',', $itemIds);
        //$searchParams['approve_status'] = 'onsale';
        $itemsList = app::get('topshop')->rpcCall('item.search', $searchParams);

        //特殊判断 有待优化
        if($itemsList['total_found']>40)
        {
            $pages = ceil($itemsList['total_found']/40);

            for($i=2;$i<=$pages;$i++)
            {
                $searchParams = array(
                    'page_no' => $i,
                    'item_id' => implode(',',$itemIds),
                    //'approve_status' => 'onsale',
                    'fields' => 'item_id,title,image_default_id,cat_id,brand_id,price',
                );
                $itemsListData = app::get('syspromotion')->rpcCall('item.search',$searchParams);
                $itemsList['list'] = array_merge($itemsList['list'],$itemsListData['list']);
            }
        }

        $extends = json_decode(input::get('extends'), 1);
        $extendsData = json_decode(input::get('extends_data'), 1);
        if( count($extends) > 0 )
        {
            $fmtItemExtendsData = array();
            foreach($extendsData as $item)
            {
                $itemId = $item['item_id'];

                $fmtItemExtendsData[$itemId] = $item;
            }

            foreach($itemsList['list'] as $key=>$value)
            {
                $itemId = $value['item_id'];
                $itemsList['list'][$key]['extendsData'] = $fmtItemExtendsData[$itemId];
            }

            $pagedata['_input']['extends'] = $extends;
        }

        $pagedata['_input']['itemsList'] = $itemsList['list'];
        $pagedata['_input']['view'] = $extendView;
        if(!$textcol)
        {
            $pagedata['_input']['_textcol'] = 'title';
        }
        else
        {
            $pagedata['_input']['_textcol'] = explode(',',$textcol);
        }

//      echo "<pre>";
//      print_r($pagedata);exit;
//      print_r(input::get());exit;
        return view::make('topshop/selector/item/input-row.html', $pagedata);
    }

    //根据商家id和3级分类id获取商家所经营的所有品牌
    public function getBrandList()
    {
        $shopId = $this->shopId;
        $catId = input::get('catId');
        $params = array(
            'shop_id'=>$shopId,
            'cat_id'=>$catId,
            'fields'=>'brand_id,brand_name,brand_url'
        );
        $brands = app::get('topshop')->rpcCall('category.get.cat.rel.brand',$params);
        return response::json($brands);
    }

    //根据商家类目id的获取商家所经营类目下的所有商品
    public function searchItem($json=true)
    {
    	$taxRegionModal = app::get('sysshop')->model("ware_region");
        $shopId = $this->shopId;
        $catId = input::get('catId');
        $brandId = input::get('brandId');
        $keywords = input::get('searchname');
        $pages = input::get('pages');
        
        //默认业务类型及仓库数据；
        $taxRegion = input::get('taxRegion');
        $taxRegionArr = (isset($taxRegion) && "" !=trim($taxRegion))  ?  explode("-",$taxRegion) : false ; 
        $taxId= $region_id=0 ;
        if(!$taxRegion  || count($taxRegionArr) < 2 ){
        	$taxId  =  ($taxRegionArr && count($taxRegionArr)>0) ? intval($taxRegionArr[0]) : 1; 	//默认取完税仓库；
        	//取第一个仓库
        	$taxrg = $taxRegionModal->getRow('id,name,tax',array("shop_id"=>$this->shopId,"tax"=> $taxId ));
        	$region_id = isset($taxrg)?intval($taxrg["id"]): 0 ;//默认取完税仓库；
        }else{
        	list($taxId,$region_id)= $taxRegionArr;
        }
        // $fullminusId = input::get('fullminusId');
        //查询参数
        $searchParams = array(
                'shop_id' => $shopId,
                'cat_id' => $catId,
                'search_keywords' => $keywords,
                'page_no' => intval($pages),
                'page_size' => intval($this->limit),
            ); 
        $brandId and $searchParams['brand_id'] = $brandId;  //选择的品牌
        $searchParams['tax'] = $taxId ? $taxId : 1; 	//选择的业务类型
        $region_id>0 and $searchParams['sea_region'] = $region_id;	//选择的产品发货仓库
        //$searchParams['approve_status'] = 'onsale';
        $searchParams['fields'] = 'item_id,title,image_default_id,price,brand_id,tax,sea_region';
        //print_r($searchParams);
        $itemsList = app::get('topshop')->rpcCall('item.search', $searchParams);
        $pagedata['itemsList'] = $itemsList['list'];
        $pagedata['total'] = $itemsList['total_found'];
        $totalPage = ceil($itemsList['total_found']/$this->limit);
        $pagersFilter['pages'] = time();
        $pagers = array(
            'link' => url::action('topshop_ctl_selector_item@searchItem', $pagersFilter),
            'current' => $pages,
            'use_app' => 'topshop',
            'total' => $totalPage,
            'token' => time(),
        );
        $pagedata['pagers'] = $pagers;
        // if($fullminusId)
        // {
        //     $objMdlFullminusItem = app::get('syspromotion')->model('fullminus_item');
        //     $notEndItem = $objMdlFullminusItem->getList('item_id', array('end_time|than'=>time() ) );

        //     $pagedata['notEndItem'] = array_column($notEndItem, 'item_id');
        // }
        // else
        // {
        //      $pagedata['notEndItem'] = array();
        // }
        $pagedata['image_default_id'] = app::get('image')->getConf('image.set');
        // $data = $json ? response::json($pagedata) : $pagedata;
        return view::make('topshop/selector/item/list.html', $pagedata);
    }
}
