<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
use Endroid\QrCode\QrCode;
class topc_ctl_item extends topc_controller {

    private function __setting()
    {
        $setting['image_default_id']= kernel::single('image_data_image')->getImageSetting('item');
        return $setting;
    }

    public function index()
    {
        $this->setLayoutFlag('product');
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) )
        {
            return redirect::action('topc_ctl_default@index');
        }

        if( userAuth::check() )
        {
            $pagedata['nologin'] = 1;
        }

        $pagedata['user_id'] = userAuth::id();

        $pagedata['image_default_id'] = $this->__setting();

        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.pc_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        $detailData = app::get('topc')->rpcCall('item.get',$params);
        if(!$detailData)
        {
            $pagedata['error'] = "很抱歉，您查看的宝贝不存在，可能已下架或者被转移";
            return $this->page('topc/items/error.html', $pagedata);
        }
        if(count($detailData['sku']) == 1)
        {
            $detailData['default_sku_id'] = array_keys($detailData['sku'])[0];
        }

        $detailData['valid'] = $this->__checkItemValid($detailData);

        //判断此商品发布的平台，如果是wap端，跳转至wap链接
        if($detailData['use_platform'] == 2 )
        {
            redirect::action('topm_ctl_item@index',array('item_id'=>$itemId))->send();exit;
        }

        //相册图片
        if( $detailData['list_image'] )
        {
            $detailData['list_image'] = explode(',',$detailData['list_image']);
        }
        //获取商品的促销信息
        $promotionInfo = app::get('topc')->rpcCall('item.promotion.get', array('item_id'=>$itemId));
        if($promotionInfo)
        {
            foreach($promotionInfo as $vp)
            {
                $basicPromotionInfo = app::get('topc')->rpcCall('promotion.promotion.get', array('promotion_id'=>$vp['promotion_id'], 'platform'=>'pc'));
                if($basicPromotionInfo['valid']===true)
                {
                    $pagedata['promotionDetail'][] = $basicPromotionInfo;
                }
            }
        }
        //echo '<pre>';print_r($pagedata['promotionDetail']);exit();
        $pagedata['promotion_count'] = count($pagedata['promotionDetail']);
        // 活动促销(如名字叫团购)
        $activityDetail = app::get('topc')->rpcCall('promotion.activity.item.info',array('item_id'=>$itemId,'valid'=>1),'buyer');
        if($activityDetail)
        {
            $pagedata['activityDetail'] = $activityDetail;
        }
        $detailData['spec'] = $this->__getSpec($detailData['spec_desc'], $detailData['sku']);
        $detailData['qrCodeData'] = $this->__qrCode($itemId);
        $pagedata['item'] = $detailData;

        //获取商品详情页左侧店铺分类信息
        $pagedata['shopCat'] = app::get('topc')->rpcCall('shop.cat.get',array('shop_id'=>$pagedata['item']['shop_id']));

        //获取该商品的店铺信息
        $pagedata['shop'] = app::get('topc')->rpcCall('shop.get',array('shop_id'=>$pagedata['item']['shop_id']));
        //print_r($pagedata['shop']);
        //获取该商品店铺的DSR信息
        $pagedata['shopDsrData'] = $this->__getShopDsr($pagedata['item']['shop_id']);

        $pagedata['next_page'] = url::action("topc_ctl_item@index",array('item_id'=>$itemId));

        if( $pagedata['user_id'] )
        {
            //获取该用户的最近购买记录
            $pagedata['buyerList'] = app::get('topc')->rpcCall('trade.user.buyerList',array('user_id'=>$pagedata['user_id']));
            $pagedata['buyerList'] = array_bind_key($pagedata['buyerList'],'item_id');
        }
        
        //print_r($pagedata['buyerList']);

        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');

        //设置此页面的seo
        $gbrand_fields = "brand_id,brand_name,brand_logo,brand_url,brand_desc";
        $brand = app::get('topc')->rpcCall('category.brand.get.info',array('brand_id'=>$detailData['brand_id'],"fields"=>$gbrand_fields));
        $cat = app::get('topc')->rpcCall('category.cat.get.info',array('cat_id'=>$detailData['cat_id']));
        
        //面包屑数据；
        $initLineData = array(
        	array("text" => $cat[$detailData['cat_id']]['cat_name'] ,'linksrc'=> url::action("topc_ctl_list@index",array('cat_id'=>$detailData['cat_id'])) ),
        	array("text" => "品牌：".$brand['brand_name'] ,'linksrc'=> url::action("topc_ctl_list@index",array('brand_id'=>$detailData['brand_id']))),
        	array("text" => $detailData['title'])
        );
        $pagedata['top_navTips'] = array("status"=> true , "menus" => $initLineData); //数据
        $pagedata['item_brandinfo'] = $brand ? $brand : false; 		//品牌数据
        //获取商品属性
        $props = '';
        if($detailData['spec_desc'])
        {
            foreach ($detailData['spec_desc'] as $k=>$v)
            {
                $str = '';
                $str = $detailData['spec']['specName'][$k] . '有';
                $mstr = '';
                foreach ($v as $spec_value_id => $spec_value)
                {
                    $mstr .= $spec_value['spec_value'] . '，';
                }

                $str = $str . $mstr;

                $props .= $str;
            }

            $props = rtrim($props, '，');
        }
        $seoData = array(
            'item_title' => $detailData['title'],
            'shop_name' =>$pagedata['shop']['shop_name'],
            'item_brand' => $brand['brand_name'],
            'item_bn' => $detailData['bn'],
            'item_cat' =>$cat[$detailData['cat_id']]['cat_name'],
            'sub_title' =>$detailData['sub_title'],
            'sub_props' => $props
        );
        seo::set('topc.item.detail',$seoData);

        return $this->page('topc/items/index.html', $pagedata);
    }
    
    /**
     * 热卖排行的商品列表或是店铺推荐的商品列表数据展示
     * 提交进入商品参数有：
     * $shopId  店铺ID，0或整数
     * $showtype 商品类型  （默认值：hotsale －热卖 ， tuijian -- 店铺推荐息自定义 ）
     */
    function hotsaleItem(){
    	$getdata  = input::get();
    	$shopid = isset($getdata['shop_id'])  ? intval($getdata['shop_id']) : 0;
    	$type = (isset($getdata['showtype']) && $getdata['showtype'] ) ? $getdata['showtype'] : "hotsale";
    	$searchParams = array('is_usexunsearch'=>0,'page_no'=>1,'page_size'=>5 ,'approve_status'=> 'onsale');
    	$shopid and $searchParams['shop_id'] = $shopid;
        $searchParams['fields'] = 'item_id,title,image_default_id,price,promotion,tax,sea_region';
        $ShowTitle = "热卖商品";
        //热买商品列表
        if($type =="hotsale"){
        	$ShowTitle = "热卖商品";
        	$searchParams['order'] = 'sold_quantity DESC';
        	
        }else if($type =="tuijian"){
        	$ShowTitle = "最新上架";
        	$searchParams['order'] = 'modified_time DESC';
        }
        $pagedata['showProjectName'] = $ShowTitle;  
        $itemsLs = app::get('topc')->rpcCall('item.search',$searchParams,'buyer');
        $pagedata['projectItems'] = $itemsLs;  
        return view::make('topc/items/basic/hotsale.html', $pagedata);
    }
    
    
    
    //商品列表页加入购物车
    public function miniSpec()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) )
        {
            return redirect::action('topc_ctl_default@index');
        }

        if( userAuth::check() )
        {
            $pagedata['nologin'] = 1;
        }
        $pagedata['user_id'] = userAuth::id();
        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.pc_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        $detailData = app::get('topc')->rpcCall('item.get',$params);
        if(!$detailData)
        {
            $pagedata['error'] = "很抱歉，您查看的宝贝不存在，可能已下架或者被转移";
            return $this->page('topc/items/error.html', $pagedata);
        }
        if(count($detailData['sku']) == 1)
        {
            $detailData['default_sku_id'] = array_keys($detailData['sku'])[0];
        }

        $detailData['valid'] = $this->__checkItemValid($detailData);

        //判断此商品发布的平台，如果是wap端，跳转至wap链接
        if($detailData['use_platform'] == 2 )
        {
            redirect::action('topm_ctl_item@index',array('item_id'=>$itemId))->send();exit;
        }

        $detailData['spec'] = $this->__getSpec($detailData['spec_desc'], $detailData['sku']);
        $detailData['qrCodeData'] = $this->__qrCode($itemId);
        $pagedata['item'] = $detailData;
        return $this->page('topc/list/spec_dialog.html', $pagedata);
    }

    // 获取商品的组合促销商品
    public function getPackage()
    {
        $params['item_id'] = intval (input::get('item_id'));
        $pagedata = app::get('topc')->rpcCall('promotion.package.getPackageItemsByItemId', $params);
        $basicPackageTag = [];
        foreach($pagedata['data'] as &$v)
        {
            $oldTotalPrice = 0;
            $packageTotalPrice = 0;
            foreach($v['items'] as $v1)
            {
                $oldTotalPrice += $v1['price'];
                $packageTotalPrice += $v1['package_price'];
            }
            $v['old_total_price'] = $oldTotalPrice;
            $v['package_total_price'] = $packageTotalPrice;
            $v['cut_total_price'] = ecmath::number_minus(array($v['old_total_price'], $v['package_total_price']));
            $basicPackageTag[] = array('name'=>$v['package_name'], 'package_id'=>$v['package_id']);
        }
        if(!$pagedata)return;
        $pagedata['package_tags'] = $basicPackageTag;
        return view::make('topc/items/package.html', $pagedata);
    }

    public function getPackageItemSpec()
    {

        $inputdata = input::get();
        $validator = validator::make([$inputdata['package_id']],['numeric']);
        if ($validator->fails())
        {
            return $this->splash('error',null,'数据格式错误！',true);
        }
        $params = array(
            'page_no' => 1,
            'page_size' => 10,
            'fields' =>'item_id,shop_id,title,image_default_id,price,package_price',
            'package_id' => $inputdata['package_id'],
        );
        $packageItemList = app::get('topc')->rpcCall('promotion.packageitem.list', $params);
        $itemsIds = array_column($packageItemList['list'],'item_id');
        $pagedata['packageInfo'] = $packageItemList['promotionInfo'];
        $packageItemList = array_bind_key($packageItemList['list'], 'item_id');
        if(!$itemsIds)return;
        $detailData = array();
        $specSkuData = array();
        $pagedata['valid'] = true;
        foreach($itemsIds as $itemId)
        {
            $params = array(
                'item_id'=>$itemId,
                'fields' => "item_id,item_store,image_default_id,price,title,spec_desc,sku,spec_index,item_status",
            );
            $detailData[$itemId] = app::get('topc')->rpcCall('item.get',$params);
            if(!$detailData[$itemId])
            {
                $detailData[$itemId] = $packageItemList[$itemId];
                $detailData[$itemId]['valid'] = false;
                $detailData[$itemId]['is_delete'] = true;
            }
            else
            {
                $detailData[$itemId]['valid'] = $this->__checkItemValid($detailData[$itemId]);
            }

            if( $pagedata['valid'] && ! $detailData[$itemId]['valid'] )
            {
                $pagedata['valid'] = false;
            }
            $detailData[$itemId]['spec'] = $this->__getSpec($detailData[$itemId]['spec_desc'], $detailData[$itemId]['sku']);
            $detailData[$itemId]['package_price'] = $packageItemList[$itemId]['package_price'];
            $specSkuData[$itemId] = $detailData[$itemId]['spec']['specSku'];
            if(count($detailData[$itemId]['sku']) == 1)
            {
                $detailData[$itemId]['default_sku_id'] = array_keys($detailData[$itemId]['sku'])[0];
            }
        }
        $pagedata['item'] = $detailData;
        $pagedata['image_default_id'] = $this->__setting();
        $pagedata['package_id'] = $inputdata['package_id'];
        $pagedata['total_package_price'] = ecmath::number_plus(array_column($packageItemList,'package_price'));
        $pagedata['total_old_price'] = ecmath::number_plus(array_column($packageItemList,'price'));
        foreach($specSkuData as &$v1)
        {
            foreach($v1 as &$v2)
            {
                $v2['package_price'] = $packageItemList[$v2['item_id']]['package_price'];
            }
        }
        $pagedata['specSkuData'] = $specSkuData; //用于规格选择
        return view::make('topc/items/package_spec.html', $pagedata);
    }

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

    private function __checkItemValid($itemsInfo)
    {
        if( empty($itemsInfo) ) return false;

        //违规商品
        if( $itemsInfo['violation'] == 1 ) return false;

        //未启商品
        if( $itemsInfo['disabled'] == 1 ) return false;

        //未上架商品
        if($itemsInfo['approve_status'] == 'instock' ) return false;

        return true;
    }

    private function __getShopDsr($shopId)
    {
        $params['shop_id'] = $shopId;
        $params['catDsrDiff'] = true;
        $dsrData = app::get('topc')->rpcCall('rate.dsr.get', $params);
        if( !$dsrData )
        {
            $countDsr['tally_dsr'] = sprintf('%.1f',5.0);
            $countDsr['attitude_dsr'] = sprintf('%.1f',5.0);
            $countDsr['delivery_speed_dsr'] = sprintf('%.1f',5.0);
        }
        else
        {
            $countDsr['tally_dsr'] = sprintf('%.1f',$dsrData['tally_dsr']);
            $countDsr['attitude_dsr'] = sprintf('%.1f',$dsrData['attitude_dsr']);
            $countDsr['delivery_speed_dsr'] = sprintf('%.1f',$dsrData['delivery_speed_dsr']);
        }
        $shopDsrData['countDsr'] = $countDsr;
        $shopDsrData['catDsrDiff'] = $dsrData['catDsrDiff'];
        return $shopDsrData;
    }

    private function __getRateResultCount($itemId)
    {
        $countRateData = app::get('topc')->rpcCall('item.get.count',array('item_id'=>$itemId,'fields'=>'item_id,rate_count,rate_good_count,rate_neutral_count,rate_bad_count'));
        if( !$countRateData[$itemId]['rate_count'] )
        {
            $countRate['good']['num'] = 0;
            $countRate['good']['percentage'] = '0%';
            $countRate['neutral']['num'] = 0;
            $countRate['neutral']['percentage'] = '0%';
            $countRate['bad']['num'] = 0;
            $countRate['bad']['percentage'] = '0%';
            return $countRate;
        }
        $countRate['good']['num'] = $countRateData[$itemId]['rate_good_count'];
        $countRate['good']['percentage'] = sprintf('%.2f',$countRateData[$itemId]['rate_good_count']/$countRateData[$itemId]['rate_count'])*100 .'%';
        $countRate['neutral']['num'] = $countRateData[$itemId]['rate_neutral_count'];
        $countRate['neutral']['percentage'] = sprintf('%.2f',$countRateData[$itemId]['rate_neutral_count']/$countRateData[$itemId]['rate_count'])*100 .'%';
        $countRate['bad']['num'] = $countRateData[$itemId]['rate_bad_count'];
        $countRate['bad']['percentage'] = sprintf('%.2f',$countRateData[$itemId]['rate_bad_count']/$countRateData[$itemId]['rate_count'])*100 .'%';
        $countRate['total'] = $countRateData[$itemId]['rate_count'];
        return $countRate;
    }

    public function getItemRate()
    {
        $itemId = input::get('item_id');
        if( empty($itemId) ) return '';

        $pagedata =  $this->__searchRate($itemId);
        $pagedata['countRate'] = $this->__getRateResultCount($itemId);
        $pagedata['item_id'] = $itemId;

        return view::make('topc/items/rate.html', $pagedata);
    }

    public function getItemRateList()
    {
        $itemId = input::get('item_id');

        $pagedata =  $this->__searchRate($itemId);

        return view::make('topc/items/rate/list.html',$pagedata);
    }

    private function __searchRate($itemId)
    {
        $current = input::get('pages',1);
        $params = ['item_id'=>$itemId,'page_no'=>$current,'page_size'=>10,'fields'=>'*'];

        if( in_array(input::get('result'), ['good','bad', 'neutral']) )
        {
            $params['result'] = input::get('result');
            $pagedata['result'] = $params['result'];
        }
        else
        {
            $pagedata['result'] = 'all';
        }
        if( input::get('content') )
        {
            $params['is_content'] = true;
        }
        if( input::get('picture') )
        {
            $params['is_pic'] = true;
        }

        $data = app::get('topc')->rpcCall('rate.list.get', $params);
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
            $pagedata['userName'] = app::get('topc')->rpcCall('user.get.account.name',array('user_id'=>$userId));
        }

        //处理翻页数据
        $filter = input::get();
        $filter['pages'] = time();
        if($data['total_results']>0) $total = ceil($data['total_results']/10);
        $current = $total < $current ? $total : $current;
        $pagedata['pagers'] = array(
            'link'=>url::action('topc_ctl_item@getItemRateList',$filter),
            'current'=>$current,
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        return $pagedata;
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

    //以下为商品咨询
    public function getItemConsultation()
    {

        $itemId = intval(input::get('item_id')) ;

        if( empty($itemId) ) return '';

        $pagedata =  $this->__searchConsultation($itemId);
        $pagedata['item_id'] = $itemId;
        $pagedata['user_id'] = userAuth::id();

        return view::make('topc/items/consultation.html', $pagedata);
    }

    public function getItemConsultationList()
    {
        $itemId = intval(input::get('item_id'));

        $pagedata =  $this->__searchConsultation($itemId);
        return view::make('topc/items/consultation/list.html',$pagedata);
    }

    private function __searchConsultation($itemId)
    {
        $current = intval(input::get('pages',1)) ;
        $params = ['item_id'=>intval($itemId),'user_id'=>userAuth::id(),'page_no'=>$current,'page_size'=>10,'fields'=>'*'];

        if( in_array(input::get('result'), ['item','store_delivery', 'payment','invoice']) )
        {
            $params['type'] = input::get('result');
            $pagedata['result'] = 'all';
        }
        else
        {
            $pagedata['result'] = 'all';
        }

        $data = app::get('topc')->rpcCall('rate.gask.list', $params);

        $pagedata['gask']= $data['lists'];
        $pagedata['count'] = app::get('topc')->rpcCall('rate.gask.count', $params);

        //处理翻页数据
        $filter = input::get();
        $pagedata['filter'] = $filter;
        $filter['pages'] = time();
        if($data['total_results']>0) $total = ceil($data['total_results']/10);
        $current = $total < $current ? $total : $current;
        $pagedata['pagers'] = array(
            'link'=>url::action('topc_ctl_item@getItemConsultationList',$filter),
            'current'=>$current,
            'total'=>$total,
            'token'=>$filter['pages'],
        );
        return $pagedata;
    }

    /**
     * @brief 商品咨询提交
     *
     * @return
     */
    public function commitConsultation()
    {
        $post = input::get();
        $params['item_id'] = $post['item_id'];
        $params['content'] = $post['content'];
        $params['type'] = $post['type'];
        $params['is_anonymity'] = $post['is_anonymity'] ? $post['is_anonymity'] : 0;
        $params['ip'] = request::getClientIp();

       if(userAuth::id())
        {
            $params['user_name'] = userAuth::getLoginName();
            $params['user_id'] = userAuth::id();
        }
        else
        {
            if(!$post['contack'])
            {
                return $this->splash('error',$url,"由于您没有登录，咨询请填写联系方式",true);
            }
            $params['contack'] = $post['contack'];
            $params['user_name'] = '游客';
            $params['user_id'] = "0";
        }

        try{
            if($params['contack'])
            {
                //$type = kernel::single('pam_tools')->checkLoginNameType($params['contack']);
                $type = app::get('topc')->rpcCall('user.get.account.type',array('user_name'=>$params['contack']),'buyer');
                if($type == "login_account")
                {
                    throw new \LogicException('请填写正确的联系方式(手机号或邮箱)');
                }
            }

            $params = utils::_filter_input($params);
            $result = app::get('topc')->rpcCall('rate.gask.create',$params);
            $msg = '咨询提交失败';
        }
        catch(\Exception $e)
        {
            $result = false;
            $msg = $e->getMessage();
        }

        if( !$result )
        {
            return $this->splash('error',$url,$msg,true);
        }

        $url = url::action('topc_ctl_item@index',array('item_id'=>$postdata['item_id']));

        $msg = '咨询提交成功,请耐心等待商家审核、回复';
        return $this->splash('success',$url,$msg,true);
    }

public function creatimg()
    {
    	$itemId = intval(input::get('item_id'));
    	$rows='*';
    	$filter['item_id'] = $itemId;
    	// 获取商品基本信息
    	$objMdlItem = app::get('sysitem')->model('item');
    	$itemInfo = $objMdlItem->getRow($rows, $filter);
    	$name		    = $itemInfo['title'];
    	$price  	    = substr($itemInfo['price'], 0, -1);
    	$filename   	= $itemInfo['image_default_id'];
    	//$size   	    = '';
    	$png   	        = $this->__qrCodeBlack($itemId);  //二维码
    	$origin 	    = '';
      //  $dst_path 		= 'http://new.myjmall.com/themes/img.jpg';
        $size   	    = $itemInfo['weight'].'千克/件';
        if($itemInfo['area_id']){
            $areaname = app::get('sysshop')->model('area')->getRow('cn_name', ['area_id'=>$itemInfo['area_id']]);
            if($areaname['cn_name']){
                $origin 	    = $areaname['cn_name'];
            }
        }

        $dst_path 		= "http://".$_SERVER['HTTP_HOST']."/themes/img.jpg"; //正式环境的路径
	//	$dst_path 		= "http://".$_SERVER['HTTP_HOST']."/jingmao/public/themes/img.jpg";  //测试环境的路径
 	//创建图片的实例
		$dst = imagecreatefromstring(file_get_contents($dst_path));
	//打上文字
		$font = '/themes/msyh.ttf';//黑雅字体
		$pricefont = '/themes/longzhoufeng.ttf';//时钟字体
		$black = imagecolorallocate($dst, 0x00,0x00,0x00F);//字体颜色
		imagefttext($dst, 14, 0, 70, 620, $black, $font, $name);
		imagefttext($dst, 13, 0, 120, 700, $black, $font, $size);
		imagefttext($dst, 13, 0, 120, 750, $black, $font, $origin);
		imagefttext($dst, 35, 0, 120, 820, $black, $pricefont, $price);
		$logo = imagecreatefrompng($png);
		imagecopyresampled($dst, $logo, 390, 669, 0, 0, 200, 200,200, 200);
		// Content type
		header('Content-Type: image/jpeg');
		// Get new dimensions
		list($width, $height) = getimagesize($filename);
		if($width>800){
			$percent  = 800/$width;
			$new_width = $width * $percent;
			$new_height = $height * $percent;			
		}else{
			$new_width = $width;
			$new_height = $height;
		}	
		// Resample
		$image_p = imagecreatetruecolor(800, 800);
		$image = imagecreatefromjpeg($filename);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);		
		imagecopyresampled($dst, $image_p, 80,120, 0, 0, 400,400, $width, $height);

		//输出图片
		list($dst_w, $dst_h, $dst_type) = getimagesize($dst_path);
		switch ($dst_type) {
		    case 1://GIF
		        header('Content-Type: image/gif');
		        imagegif($dst);
		        break;
		    case 2://JPG
		        header('Content-Type: image/jpeg');
		        imagejpeg($dst);
		        break;
		    case 3://PNG
		        header('Content-Type: image/png');
		        imagepng($dst);
		        break;
		    default:
		        break;
		}
		
		imagedestroy($dst);
 
    }
    
  
    private function __qrCodeBlack($itemId)
    {
    	$url = url::action("topm_ctl_item@index",array('item_id'=>$itemId));
    	$qrCode = new QrCode();
    	return $qrCode
    	->setText($url)
    	->setSize(150)
    	->setPadding(10)
    	->setErrorCorrection(1)
    	->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
    	->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
    	->setLabelFontSize(16)
    	->getDataUri('png');
    }


}


