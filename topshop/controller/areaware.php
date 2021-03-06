<?php

/**
 * @brief 商家商品管理
 */
class topshop_ctl_areaware extends topshop_controller {

    public $limit = 10;
    
    public function index(){
    	 $ectoolsdata = app::get('sysshop')->model('ware_region');
		 $fields = "id,name,state,shop_id,shippings,tax,modified_time";
		 $filter =array('shop_id'=>$this->shopId);
		 $pagedata['wregions']=$ectoolsdata ->getList($fields, $filter);	
         $pagedata['nowtime'] = time();
    	$this->contentHeaderTitle = app::get('topshop')->_('仓库管理');
        return $this->page('topshop/areaware/list.html', $pagedata);
    }
    
    
    /**
     * 保存当前仓库的信息 
     * 	by xch 09.09
     */
    public function saveWare()
    {
        //$pagedata['return_to_url'] = request::server('HTTP_REFERER');
        // $objWear = kernel::single('sysshop_data_wereregion');
        $objMdlwear = app::get('sysshop')->model('ware_region');
        $postData = input::get();
        $edit_wid  = isset($postData["editware_id"]) ?   intval($postData["editware_id"]) : 0;
        $wearData =array("shop_id"=>$this->shopId);
        $wearData["name"] = isset($postData["ware_name"])? trim($postData["ware_name"]) : ""; 
        $wearData["tax"] = isset($postData["ware_tax"])? intval($postData["ware_tax"]) : 1; 
        $wearData["state"] = isset($postData["ware_status"])? intval($postData["ware_status"]): 0; 
        if($wearData["tax"]==2){
        	$wearData["state"] = 2; //需要审核才能上线；
        }
        $wearData["shippings"] = (isset($postData["ware_shipping"])&& !empty($postData["ware_shipping"]) ) ? serialize($postData["ware_shipping"]) : array(); 
        $result_json = array("success"=>1,"message"=> "保存成功");
        try
        {
        	$wearData["modified_time"] =  time();
            if($edit_wid>0){
	        	//unset($wearData["shop_id"]);
	        	$filter = array("id"=> $edit_wid,'shop_id'=> $this->shopId);
	        	$weraoInfo = $objMdlwear->getRow('id,name,shop_id', $filter);
	        	if($weraoInfo){
	        		$resut  = $objMdlwear -> update($wearData, array("id"=>$edit_wid) );//编辑        	
	        	}else{
	        		 $result_json = array("success"=>0,"message"=> "数据已丢失");
	        	}
	        }else{
	        	//保存入库
	        	$filter = array('shop_id'=> $this->shopId,"name"=> $wearData["name"],"tax"=> $wearData["tax"]);
	        	$weraoInfo = $objMdlwear->getRow('id', $filter);
	        	if($weraoInfo && !empty($weraoInfo) && intval($weraoInfo["id"])>0 ){
	        		 $result_json = array("success"=>0,"message"=> "同一业务类型仓库名称不能重复");
	        	}else{
	        		$resut  = $objMdlwear -> insert($wearData , null ); //添加
	        	}
	        }
            echo json_encode($result_json);exit;
        }
        catch (Exception $e)
        {
            $result_json = array("success"=>0,"message"=> "失败". $e->getMessage());
            echo json_encode($result_json);exit;
        }
    }
    
    /**
     * 删除选择项；
     * 
     */
    function removeWare(){
    	$postidData = input::get("delwids");
        $objMdlwear = app::get('sysshop')->model('ware_region');
        $result_json = array("success"=>1,"message"=> "操作成功");
        $result_num = 0;
        try
        {
            if(isset($postidData) && !empty($postidData)){
	        	foreach($postidData as $val)
				{
			  		$filter = array("id"=> intval($val),'shop_id'=> $this->shopId);
					$result = $objMdlwear->delete($filter);
					$result_num += $result;
				}
	        }
	        if(!$result_num){
	        	$result_json = array("success"=>0,"message"=> "没有删除项");
	        }
            echo json_encode($result_json);exit;
        }
        catch (Exception $e)
        {
            $result_json = array("success"=>0,"message"=> "操作失败". $e->getMessage());
            echo json_encode($result_json);exit;
        }
    }
	/**
	 * 新增仓库，新改版 xch by 	//--2016/11/1
	 * 
	 */
    public function add()
    {
        $pagedata['shopId'] = $this->shopId;
        $wareMdlregion = app::get('sysshop')->model('ware_region');
        
        $input_wid = input::get("waid"); //仓库ID；
        
        if($input_wid && intval($input_wid)>0){
        	
        	$filter = array("id" => $input_wid );        	
        	$wareInfo  = $wareMdlregion->getRow("*", $filter);
        	$pagedata['editTax'] = $wareInfo; //已有的信息；
        }
		/*$fields = "id,name,state,shop_id,shippings,tax,modified_time";
		$filter =array('shop_id'=>$this->shopId);
		
		$pagedata['wregions']=$ectoolsdata ->getList($fields, $filter);*/	
        $pagedata['nowtime'] = time();
        
        //业务区域的支付方式；
        $pagedata['all_seaList'] = array(array("id"=>1 ,"name"=>"深圳海关"),array("id"=>2 ,"name"=>"沙头角海关"));
        
        //业务支持运费模板；
        $tmpParams = array(
            'shop_id' => $this->shopId, 'status' => 'on','fields' => 'shop_id,name,template_id','select_tax' => 1,//所有的业务类型
        );
        $dtytmpls = app::get('topc')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        $pagedata['all_shippList'] = (isset($dtytmpls["data"])&&!empty($dtytmpls["data"]))? $dtytmpls["data"] : false ;  // array(array("shippid"=>1 ,"name"=>"中通"),array("shippid"=>2 ,"name"=>"顺丰"));
        
        //业务状态
        $pagedata['all_status']=array(0=>"无效",1=>"正常",2=>"待审核");
        
        
		//业务模式2016/3/1  ，先固定为业务为1完税，3直邮   _____by xch 09.08
		$pagedata['tax_list']=array(array("id"=>1 ,"name"=>"完税"),array("id"=>2 ,"name"=>"保税"),array("id"=>3 ,"name"=>"直邮")) ;  //业务类型
		
		
        return view::make('topshop/areaware/add.html', $pagedata);
        //return $this->page('topshop/areaware/add.html', $pagedata);
    }
    //商品库存报警设置
    public function storePolice()
    {
        $shopId = $this->shopId;
        $params['shop_id'] = $shopId;
        $params['fields'] = 'police_id,policevalue';

        $storePolice = app::get('topshop')->rpcCall('item.store.info',$params);
        //echo '<pre>';print_r($storePolice);exit();
        $pagedata['storePolice'] = $storePolice;
        $this->contentHeaderTitle = app::get('topshop')->_('设置商品库存报警数');
        return $this->page('topshop/item/storepolice.html', $pagedata);
    }
    //保存库存报警
    public function saveStorePolice()
    {
        $storePolice = intval(input::get('storepolice'));
        $policeId = intval(input::get('police_id'));
        $url = url::action('topshop_ctl_item@storePolice');
        try
        {
            $validator = validator::make(
                    [$storePolice],
                    ['required|integer|min:1|max:99999'],
                    ['库存预警值必填!|库存预警值必须为整数!|库存预警值最小为1!|库存预警值最大为99999!']
                );
            $validator->newFails();
        }
        catch( \LogicException $e )
        {
            return $this->splash('error', $url, $e->getMessage(), true);
        }
        $shopId = $this->shopId;
        $params['shop_id'] = $shopId;
        $params['policevalue'] = $storePolice;
        if(!is_null($policeId))
        {
            $params['police_id'] = $policeId;
        }
        try
        {
            app::get('topshop')->rpcCall('item.store.add',$params);
        }
        catch( \LogicException $e )
        {
            return $this->splash('error', null, $e->getMessage(), true);
        }

        return $this->splash('success', $url, '保存成功', true);
    }

    public function edit()
    {
        //$pagedata['return_to_url'] = request::server('HTTP_REFERER');
        $itemId = intval(input::get('item_id'));
        $pagedata['shopId'] = $this->shopId;

        // 店铺关联的商品品牌列表
        // 商品详细信息
        $params['item_id'] = $itemId;
        $params['shop_id'] = $this->shopId;
        $params['fields'] = "*,sku,item_store,item_status,item_count,item_desc,item_nature,spec_index";
        $pagedata['item'] = app::get('topshop')->rpcCall('item.get',$params);
        $pagedata['item']['title'] = $pagedata['item']['title'];
        // 商家分类及此商品关联的分类标示selected
        $scparams['shop_id'] = $this->shopId;
        $scparams['fields'] = 'cat_id,cat_name,is_leaf,parent_id,level';
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.cat.get',$scparams);
        $selectedShopCids = explode(',', $pagedata['item']['shop_cat_id']);
        foreach($pagedata['shopCatList'] as &$v)
        {
            if($v['children'])
            {
                foreach($v['children'] as &$vv)
                {
                    if(in_array($vv['cat_id'], $selectedShopCids))
                    {
                        $vv['selected'] = true;
                    }
                }
            }
            else
            {
                if(in_array($v['cat_id'], $selectedShopCids))
                {
                    $v['selected'] = true;
                }
            }
        }
        // 获取店铺区域和业务模式基本信息  //2016/2/24 lcd
		 $filter['shop_id']= $this->shopId;
        $objMdlshop = app::get('sysshop')->model('shop_info');

        $shopInfo = $objMdlshop->getRow('sea_region,tax', $filter);
        $filter = unserialize($shopInfo['sea_region']);
		//	区域2016/3/1
      	 $ectoolsdata = app::get('ectools')->model('region');
		 $filter =array('id'=>$filter);
		 $fields = "id,name";
		 $pagedata['region']=$ectoolsdata ->getList($fields, $filter);	
         $filter_tax= unserialize($shopInfo['tax']);
		 //业务模式2016/3/1
		 $ectoolsdata = app::get('sysshop')->model('tax');
		 $filter_tax =array('id'=>$filter_tax);
		 $pagedata['tax']=$ectoolsdata ->getList('*', $filter_tax);
        $this->contentHeaderTitle = app::get('topshop')->_('添加商品');
        return $this->page('topshop/item/edit.html', $pagedata);
    }

    public function itemList()
    {
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');

        $status = input::get('status',false);
        $pages =  input::get('pages',1);
        $pagedata['status'] = $status;
        $filter = array(
            'shop_id' => $this->shopId,
            'approve_status' => $status,
            'page_no' =>intval($pages),
            'page_size' => intval($this->limit),
        );
        $shopCatId = input::get('shop_cat_id',false);
        if( $shopCatId )
        {
            $filter['shop_cat_id'] = $shopCatId;
        }
        $filter['fields'] = 'item_id,list_time,modified_time,title,image_default_id,price,approve_status,store';
        //库存报警判断
        if($status=='oversku')
        {
            $params['shop_id'] = $this->shopId;
            $params['fields'] = 'policevalue';
            $storePolice = app::get('topshop')->rpcCall('item.store.info',$params);
            $filter['store'] = $storePolice['policevalue']?$storePolice['policevalue']:0;
            //echo '<pre>';print_r($filter);exit();
            $itemsList = app::get('topshop')->rpcCall('item.store.police',$filter);
        }
        else
        {
            $itemsList = app::get('topshop')->rpcCall('item.search',$filter);
        }
        $pagedata['item_list'] = $itemsList['list'];
        $pagedata['total'] = $itemsList['total_found'];

        $totalPage = ceil($itemsList['total_found']/$this->limit);
        $pagersFilter['pages'] = time();
        $pagersFilter['status'] = $status;
        $pagers = array(
            'link'=>url::action('topshop_ctl_item@itemList',$pagersFilter),
            'current'=>$pages,
            'use_app' => 'topshop',
            'total'=>$totalPage,
            'token'=>time(),
        );
        $pagedata['pagers'] = $pagers;

        //获取当前店铺商品分类
        $catparams['shop_id'] = $this->shopId;
        //$catparams['fields'] = 'cat_id,cat_name';
        $itemCat = app::get('topshop')->rpcCall('shop.cat.get', $catparams);
        $pagedata['item_cat'] = $itemCat;

        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');

        $this->contentHeaderTitle = app::get('topshop')->_('商品列表');
        return $this->page('topshop/item/list.html', $pagedata);
    }
    //商品搜所
    public function searchItem()
    {
        $filter = input::get();

        if($filter['min_price']&&$filter['max_price'])
        {
            if($filter['min_price']>$filter['max_price'])
             {
                $msg = app::get('topshop')->_('最大值不能小于最小值！');
                return $this->splash('error', null, $msg, true);
            }
        }
        $pages =  $filter['pages'] ? $filter['pages'] : 1;
        $params = array(
            'shop_id' => $this->shopId,
            'search_keywords' => $filter['item_title'],
			'bn' => $filter['bn'],//2015/12/11增加SKU搜索,雷成德
            'min_price' => $filter['min_price'],
            'max_price' => $filter['max_price'],
            'page_no' =>intval($pages),
            'page_size' => intval($this->limit),
        );

        if($filter['use_platform'] >= 0)
        {
            $params['use_platform'] = $filter['use_platform'];
        }
        if($filter['item_cat'] && $filter['item_cat'] > 0)
        {
            $params['search_shop_cat_id'] = (int)$filter['item_cat'];
        }
        if($filter['item_no'])
        {
            $params['bn'] = $filter['item_no'];
        }

        $pagedata['use_platform'] = $filter['use_platform'];
        $pagedata['min_price'] = $filter['min_price'];
        $pagedata['max_price'] = $filter['max_price'];
        $pagedata['search_keywords'] = $filter['item_title'];
        $pagedata['item_cat_id'] = $filter['item_cat'];
        $pagedata['item_no'] = $filter['item_no'];
        $params['fields'] = 'item_id,list_time,modified_time,title,image_default_id,price,approve_status,store';
        $itemsList = app::get('topshop')->rpcCall('item.search',$params);

        $pagedata['item_list'] = $itemsList['list'];
        $pagedata['total'] = $itemsList['total_found'];

        $totalPage = ceil($itemsList['total_found']/$this->limit);
        $pagersFilter['pages'] = time();
        $pagersFilter['min_price'] = $filter['min_price'];
        $pagersFilter['max_price'] = $filter['max_price'];
        $pagersFilter['use_platform'] = $filter['use_platform'];
        $pagersFilter['item_title'] = $filter['item_title'];
        $pagersFilter['item_cat'] = $filter['item_cat'];
        $pagersFilter['item_no'] = $filter['item_no'];
        $pagers = array(
            'link'=>url::action('topshop_ctl_item@searchItem',$pagersFilter),
            'current'=>$pages,
            'use_app' => 'topshop',
            'total'=>$totalPage,
            'token'=>time(),
        );
        $pagedata['pagers'] = $pagers;

        //获取当前店铺商品分类
        $catparams['shop_id'] = $this->shopId;
        //$catparams['fields'] = 'cat_id,cat_name';
        $itemCat = app::get('topshop')->rpcCall('shop.cat.get', $catparams);
        $pagedata['item_cat'] = $itemCat;

        $this->contentHeaderTitle = app::get('topshop')->_('商品列表');
        return $this->page('topshop/item/list.html', $pagedata);

    }
    public function storeItem()
    {
        $postData = input::get();
        //特殊符号转义
        $postData['item']['title'] = htmlspecialchars($postData['item']['title']);
        $postData['item']['sub_title'] = htmlspecialchars($postData['item']['sub_title']);
        $postData['item']['shop_id'] = $this->shopId;
        $postData['item']['cat_id'] = $postData['cat_id'];
        $postData['item']['approve_status'] = 'instock';
        if(!implode(',', $postData['item']['shop_cids']))
        {
            return $this->splash('error', '', '店铺分类至少选择一项!', true);
        }
        $postData['item']['shop_cat_id'] = ','.implode(',', $postData['item']['shop_cids']).',';
         //判断店铺是不是自营店铺 gongjiapeng
        $selfShopType = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$this->shopId));
        if($selfShopType['shop_type']=='self')
        {
            $postData['item']['is_selfshop'] = 1;
        }
        try
        {
            $postData = $this->_checkPost($postData);
            $result = app::get('topshop')->rpcCall('item.create',$postData);
            //$url = $postData['return_to_url'];
            $url = url::action('topshop_ctl_item@itemList');
            $msg = app::get('topshop')->_('保存成功');
            return $this->splash('success', $url, $msg, true);
        }
        catch (Exception $e)
        {
            return $this->splash('error', '', $e->getMessage(), true);
        }
    }

    private function _checkPost($postData)
    {
        if(!$postData['item']['mkt_price'])
        {
            $postData['item']['mkt_price'] = 0;
        }
        if(!$postData['item']['cost_price'])
        {
            $postData['item']['cost_price'] = 0;
        }
        if(!$postData['item']['weight'])
        {
            $postData['item']['weight'] = 0;
        }
        if(!$postData['item']['order_sort'])
        {
            $postData['item']['order_sort'] = 1;
        }

        if(mb_strlen($postData['item']['title'],'UTF8') > 50)
        {
            throw new Exception('商品名称至多50个字符');
        }
		 if(mb_strlen($postData['item']['tax'],'UTF8') ==0)
        {
            throw new Exception('业务模式不能为空');
        }
		if(mb_strlen($postData['item']['sea_region'],'UTF8') ==0)
        {
            throw new Exception('区域不能为空');
        }

		if($postData['item']['tax'] ==1)
        {
        	$postData['item']['gs_code']='';
        	$postData['item']['postyun_code']='';
        	$postData['item']['postyun_rate']=0;
			/*$postData['item']['gno']='';
			$postData['item']['ciqGno']='';
			$postData['item']['ciqGmodel']='';
			$postData['item']['gcode']='';
			$postData['item']['gname']='';
			$postData['item']['gmodel']='';
			$postData['item']['unit']='';*/
        }else{
        		$llent = mb_strlen($postData['item']['gs_code'],'UTF8');
        		if($llent <=5 || $llent> 15)
		        {
		            throw new Exception('海关编号长度有误');
		        }
		        $llent2 = mb_strlen($postData['item']['postyun_code'],'UTF8');
		        if($llent2 <=5 || $llent2> 20)
		        {
		            throw new Exception('行邮税号长度有误');
		        }
		        $feelis = isset($postData['item']['postyun_rate']) ? floatval($postData['item']['postyun_rate']) : 0 ;
		        if($feelis < 0 || $feelis >= 1)
		        {
		            throw new Exception('税率的范围在0到1之间的小数');
		        }
		        /*if(mb_strlen($postData['item']['gno'],'UTF8') !=18)
		        {
		            throw new Exception('海关商品备案号必须18个字符');
		        }
		        if(mb_strlen($postData['item']['ciqGno'],'UTF8') > 18)
		        {
		            throw new Exception('检疫商品备案号至多18个字符');
		        }
		        if(mb_strlen($postData['item']['gcode'],'UTF8') !=10)
		        {
		            throw new Exception('海关商品编码必须10个字符');
		        }
		        if(mb_strlen($postData['item']['gname'],'UTF8') > 250)
		        {
		            throw new Exception('海关商品名称至多250个字符');
		        }
		        if(mb_strlen($postData['item']['gmodel'],'UTF8') > 250)
		        {
		            throw new Exception('海关规格型号至多250个字符');
		        }
				if(mb_strlen($postData['item']['ciqGmodel'],'UTF8') > 250)
		        {
		            throw new Exception('检疫规格型号至多250个字符');
		        }*/
		}
        return $postData;
    }


    public function setItemStatus(){

        $postData = input::get();
        try
        {
            if(!$itemId = $postData['item_id'])
            {
                $msg = app::get('topshop')->_('商品id不能为空');
                return $this->splash('error',null,$msg,true);
            }

            if($postData['type'] == 'tosale')
            {
                $shopdata = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$this->shopId),'seller');
                if( empty($shopdata) || $shopdata['status'] == "dead" )
                {
                    $msg = app::get('topshop')->_('抱歉，您的店铺处于关闭状态，不能发布(上架)商品');
                    return $this->splash('error',null,$msg,true);
                }
                $status = 'onsale';
                $msg = app::get('topshop')->_('上架成功');
            }
            elseif($postData['type'] == 'tostock')
            {
                $status = 'instock';
                $msg = app::get('topshop')->_('下架成功');
            }
            else
            {
                return $this->splash('error',null,'非法操作!', true);
            }

            $params['item_id'] = intval($itemId);
            $params['shop_id'] = intval($this->shopId);
            $params['approve_status'] = $status;
            app::get('topshop')->rpcCall('item.sale.status',$params);
            $queue_params['item_id'] = intval($itemId);
            $queue_params['shop_id'] = intval($this->shopId);
            //发送到货通知的邮件
            if($status == "onsale")
            {
                system_queue::instance()->publish('sysitem_tasks_userItemNotify', 'sysitem_tasks_userItemNotify', $queue_params);
            }
            $url = url::action('topshop_ctl_item@itemList');
            return $this->splash('success', $url, $msg, true);
        }
        catch(Exception $e)
        {
            return $this->splash('error',null,$e->getMessage(), true);
        }
    }

    public function deleteItem()
    {
        $postData = input::get();
        //订单状态
        $orderStatus = array('WAIT_BUYER_PAY', 'WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS');

        try
        {
            if(!$itemId = $postData['item_id'])
            {
                $msg = app::get('topshop')->_('商品id不能为空');
                return $this->splash('error',null,$msg, true);
            }

            //判断商品所在订单的状态
            $orderParams = array();
            $orderParams['item_id'] = (int)$itemId;
            $orderParams['fields'] = 'status';
            $orderList = app::get('topshop')->rpcCall('trade.order.list.get', $orderParams);
            if($orderList)
            {
                $orderArrStatus = array_column($orderList, 'status');
                foreach ($orderStatus as $status)
                {
                    if(in_array($status, $orderArrStatus))
                    {
                        $msg = app::get('topshop')->_('商品存在未完成的订单，不能删除');
                        return $this->splash('error',null,$msg, true);
                    }
                }
            }

            app::get('topshop')->rpcCall('item.delete',array('item_id'=>intval($itemId),'shop_id'=>intval($this->shopId)));
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        return $this->splash('success',null,'删除成功', true);
    }

    public function ajaxGetBrand($cat_id)
    {
        $params['shop_id'] = $this->shopId;
        $params['cat_id'] = input::get('cat_id');
        try
        {
            $brand = app::get('topshop')->rpcCall('category.get.cat.rel.brand',$params);
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        return response::json($brand);exit;
    }
	   public function ajaxCounty()
    {
	   $objMdlShopCat = app::get('sysshop')->model('area')->getList('*');
      return response::json($objMdlShopCat);exit;
    }

		   public function ajaxUnit()
    {
	   $objMdlUnit = app::get('sysitem')->model('unit')->getList('*');
      return response::json($objMdlUnit);exit;
    }
//2016/4/24 通过海关编码获取名称 雷成德
	public function ajax_itemcode()
    {
	$item_gcode = input::get('item_gcode');
	$filter['code_ts']=$item_gcode;
   $objItem_code = app::get('sysitem')->model('item_code')->getRow('g_name',$filter);
      return $objItem_code['g_name'];exit;
    }


}


