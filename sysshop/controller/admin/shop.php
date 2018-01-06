<?php

/**
 * @brief 店铺
 */
class sysshop_ctl_admin_shop extends desktop_controller{

    /**
     * @brief  店铺列表
     *
     * @return
     */
    public function index()
    {
        return $this->finder('sysshop_mdl_shop',array(
            'use_buildin_delete' => false,
            'actions' => array(
                array(
                    'label'=>app::get('sysshop')->_('添加自营店铺'),
                    'href'=>'?app=sysshop&ctl=admin_shop&act=addSelfShop',
                    'target'=>'dialog::{title:\''.app::get('sysshop')->_('添加自营店铺').'\',  width:500,height:320}',
                ),
            ),
        ));
    }
    //添加自营店铺
    public function addSelfShop()
    {
        $this->contentHeaderTitle = '添加自营店铺';
        return view::make('sysshop/admin/shop/addSelfShop.html');
    }
    //修改自营店铺
    public function updateSelfShop($shopId)
    {
        if(empty($shopId))
        {
            return $this->splash('error',null,"店铺id不能为空");
        }
        $objShop = kernel::single('sysshop_data_shop');
        $shopInfo = $objShop->getShopById($shopId,'*');
        $pagedata['shop'] = $shopInfo;
        return view::make('sysshop/admin/shop/addSelfShop.html', $pagedata);
    }
    //保存自营店铺
    public function saveSelfShop()
    {
        $postdata = input::get('shop');
        $validator = validator::make(
            [explode("（",$postdata['shop_name'])[0],
             $postdata['shop_descript'],
             $postdata['shopuser_name'],
             $postdata['shopuser_identity'],
             $postdata['shop_area'],
             $postdata['shop_addr'],
             $postdata['mobile'],
             $postdata['email']
            ],
            ['required|max:20','required|max:200','required|max:20',
             'required|max:18','required|max:20','required|max:50',
             'required|mobile','required|email'
            ],
            ['店铺名称不能为空!|店铺名称最大不能超过20个字符!',
             '店铺描述不能为空!|店铺描述最大不能超过200个字符!',
             '店主姓名不能为空!|店主姓名最大不能超过20个字符!',
             '店主身份证号不能为空!|店主身份证号最大不能超过18个字符!',
             '所在地区不能为空!|所在地区最大不能超过20个字符!',
             '详细地址不能为空!|详细地址最大不能超过50个字符!',
             '手机号码不能为空!|手机格式不正确！',
             '邮箱不能为空!|邮箱格式不正确!'
            ]
        );
        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();

            foreach( $messages as $error )
            {
                return $this->splash('error',null,$error[0]);
            }
        }
        if(empty($postdata['shop_id']))
        {
            $shopName = $postdata['shop_name'].'（自营店铺）';
            $postdata['shop_name'] = $shopName;
        }
        if(mb_strlen(trim($postdata['shop_descript']),'utf8') > 200)
        {
            return $this->splash('error',null,"店铺描述不能高于200个字符");
        }
        $postdata['shop_type'] = 'self';
        $postdata['status'] = 'active';
        $postdata['seller_id'] = 0;//临时添加的seller_id等客户上级后去掉
        $postdata['open_time'] = time();

        $objShop = kernel::single('sysshop_data_shop');
        try
        {
            $objShop->saveShop($postdata);
            $this->adminlog("保存自营店铺[shop_name:{$postdata['shop_name']}]", 1);
        }
        catch(Exception $e)
        {
            $this->adminlog("保存自营店铺[shop_name:{$postdata['shop_name']}]", 0);
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
        return $this->splash('success',null,"自营店铺添加成功");
    }


    public function doUpdateShopStatus($shopId,$status,$shopname)
    {
        $pagedata['status'] = $status;
        $pagedata['shopid'] = $shopId;
        $pagedata['shopname'] = $shopname;
        return view::make('sysshop/admin/shop/canceloropen.html', $pagedata);
    }

    public function updateShopStatus()
    {
        try{
            $postdata = input::get('shop');
            $objShop = kernel::single('sysshop_data_shop');
            $result = $objShop->updateShopStatus($postdata);
            $this->adminlog("更新店铺状态[shop_id:{$postdata['shop_id']}]", 0);
        }
        catch(Exception $e)
        {
            $this->adminlog("更新店铺状态[shop_id:{$postdata['shop_id']}]", 0);
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
        return $this->splash('success',null,"店铺状态更新成功");
    }

    public function catFeeList()
    {
        $shopId = input::get('shopId');
        return $this->finder('sysshop_mdl_shop_rel_lv1cat', array(
            'title' => app::get('sysshop')->_('类目费用'),
            'base_filter' => array('shop_id' => $shopId),
        ));
    }

    /**
        * @brief 根据shopid获取对应的类目下的3级分类
        *
        * @return
     */
    public function updateCatInfo($shopId,$catId)
    {
        $shopLibCat = kernel::single('sysshop_data_cat');
        $shopRelCatMdl = app::get('sysshop')->model('shop_rel_lv1cat');
        $catList = $shopLibCat->getCatInfo($shopId);
        $catIds = implode(',',array_column($catList,'cat_id'));
        $catInfo = app::get('sysshop')->rpcCall('category.cat.get',array('cat_id'=>$catIds,'fields'=>'cat_id,parent_id,cat_path,level,cat_name,guarantee_money,platform_fee,cat_service_rates'));

        $shopFee = $shopRelCatMdl->getRow('fee_confg',array('shop_id'=>$shopId,'cat_id'=>$catId));
        $shopFeeConfg = unserialize($shopFee['fee_confg']);

        foreach ($catInfo as $key => $value)
        {
            if($shopFeeConfg[$value['cat_id']])
            {
                $catInfo[$key]['platform_fee'] = $shopFeeConfg[$value['cat_id']]['lvfee'];
                $catInfo[$key]['display'] = 'true';
                foreach ($value['lv2'] as $k => $v)
                {
                    if($shopFeeConfg[$value['cat_id']][$v['cat_id']])
                    {
                        $catInfo[$key]['lv2'][$k]['guarantee_money'] = $shopFeeConfg[$value['cat_id']][$v['cat_id']]['lv2fee'];
                        $catInfo[$key]['lv2'][$k]['display'] = 'true';
                        foreach ($v['lv3'] as $item => $val)
                        {
                            if( isset($shopFeeConfg[$value['cat_id']][$v['cat_id']][$val['cat_id']]) )
                            {
                                $catInfo[$key]['lv2'][$k]['lv3'][$item]['cat_service_rates'] = $shopFeeConfg[$value['cat_id']][$v['cat_id']][$val['cat_id']];
                                $catInfo[$key]['lv2'][$k]['lv3'][$item]['display'] = 'true';
                            }
                        }
                    }
                }
            }
        }
        $pagedata['data'] = $catInfo;
        $pagedata['shopId'] = $shopId;
        $pagedata['catId'] = $catId;
        $pagedata['shopFee'] = $shopFeeConfg;

        return view::make('sysshop/admin/shop/catfee.html', $pagedata);
    }

    /**
        * @brief 保存类目佣金
        *
        * @return
     */
    public function saveCatInfo()
    {
        $shopRelCatMdl = app::get('sysshop')->model('shop_rel_lv1cat');
        $post = input::get();

        foreach ($post['lv3'] as $key => $value)
        {
            $post['lv3'][$key]['lvfee'] = $post['lvfee'][$key];
            foreach ($value as $k => $v)
            {
                $post['lv3'][$key][$k]['lv2fee'] = $post['lvtwo'][$key][$k];
                foreach ($v as $ke => $val)
                {
                    if(intval($val)>100)
                    {
                        $msg = app::get('sysshop')->_('商家类目佣金比例不能大于100');
                        return $this->splash('error',null,$msg);
                    }
                    $post['lv3'][$key][$k][$ke] = $post['lvthree'][$key][$k][$ke];
                }
            }
        }

        $shopId = $post['shopid'];
        $catId = $post['catid'];
        //$data = array('fee_confg'=>serialize($post['lvthree']));
        $data = array('fee_confg'=>serialize($post['lv3']));
        $filter = array('shop_id'=>$shopId,'cat_id'=>$catId);

        if(!$shopRelCatMdl->update($data,$filter))
        {
            $this->adminlog("保存类目佣金", 0);
            $msg = app::get('sysshop')->_('修改失败');
            $url="?app=sysshop&ctl=admin_shop&act=index";
            return $this->splash('error',$url,$msg);
        }
        else
        {
            $this->adminlog("保存类目佣金", 1);
            $msg = app::get('sysshop')->_('修改成功');
            $url="?app=sysshop&ctl=admin_shop&act=index";
            return $this->splash('success',$url,$msg);
        }
    }

     /**
        * @brief ajax请求类目下的品牌
        *
        * @return
     */
    public function ajaxCatBrand()
    {
        $catid = $_POST['cat_id'];
        $brands = app::get('sysshop')->rpcCall('category.get.cat.rel.brand',array('cat_id'=>$catid));
        echo json_encode($brands);
    }


    /**
        * @brief 管理员后台编辑商家入驻申请
        *
        * @return
     */
    public function updateShopInfo($sellerId,$shopId)
    {
        $objShopType = kernel::single('sysshop_data_enterapply');
        $datalist = $objShopType->getData($sellerId);

        $shop_info = unserialize($datalist['shop_info']);
        $shop_info['establish_date'] = date('Y-m-d',$shop_info['establish_date']);
        $shop_info['license_indate'] = date('Y-m-d',$shop_info['license_indate']);
        $pagedata['shop'] = unserialize($datalist['shop']);
        $pagedata['shop_info'] = $shop_info;

        unset($datalist['shop_info'],$datalist['shop']);
        $pagedata['applydata'] = $datalist;
		//2016/2/24 lcd
		$pagedata['applydata']['sea_region']  = unserialize($datalist['sea_region']);
		$pagedata['applydata']['checked_tax']              = unserialize($datalist['tax']);
		//判断是保税和直邮//2016/2/24 lcd
		 if (in_array("2",$pagedata['applydata']['checked_tax'] , TRUE)||in_array("3",$pagedata['applydata']['checked_tax'] , TRUE))
		{
		   $pagedata['applydata']['is_tax'] =1;
		}

        $objShopType = kernel::single('sysshop_data_shoptype');

        $lv1Catlists = app::get('sysshop')->rpcCall('category.cat.get.info',array('parent_id'=>0,'fields'=>'cat_id,cat_name'));
        foreach($lv1Catlists as $key=>$value)
        {
            $catlists[$value['cat_id']] = $value['cat_name'];
        }

        $pagedata['catlist'] = $catlists;

        $shopTypelist = $objShopType->shopType();
        $pagedata['shoptypelist'] = $shopTypelist;

        $pagedata['shopId'] = $shopId;
        $pagedata['status'] = $datalist['status'];
        //品牌
        $lv1 = $pagedata['shop']['shop_cat'];
        $brandId = $pagedata['shop']['shop_brand'];
        $brandlist = app::get('sysshop')->rpcCall('category.get.cat.rel.brand',array('cat_id'=>$lv1));
        if($brandlist)
        {
            foreach($brandlist as $key=>$val)
            {
                if($val['brand_id'] == $brandId)
                {
                    $pagedata['brands'][$brandId] = $val;
                }
            }
        }
       //2016/2/24 lcd
      	 $ectoolsdata = app::get('ectools')->model('region');
         $filter = ['state' => 0];
		 $fields = "id,name";
		 $pagedata['region']=$ectoolsdata ->getList($fields, $filter);	
           //查询业务模式2016/2/24 lcd
		 $ectoolsdata = app::get('sysshop')->model('tax');
		 $pagedata['tax']=$ectoolsdata ->getList();	
        $this->contentHeaderTitle = '编辑入驻申请资料';
        return view::make('sysshop/admin/shop/apply.html', $pagedata);
    }

    public function saveApply()
    {
        $objShopType = kernel::single('sysshop_data_enterapply');
        $objShop = kernel::single('sysshop_data_shop');
        $post = input::get();
        if(mb_strlen(trim($post['shop']['shop_descript']),'utf8') > 200)
        {
            return $this->splash('error',null,"店铺描述不能超过200个字");
        }
//2016/2/24 lcd
       if(count($post['shop_info']['tax'])==1&&$post['shop_info']['tax'][1]==1){
         $post['shop_info']['ebcCode']="";
		 $post['shop_info']['ebcName']="";
		 $post['shop_info']['agentCode']="";
		 $post['shop_info']['agentName']="";
		 $post['shop_info']['shortName']="";
         }
      //验证跨境管理//2016/2/24 lcd
		if (in_array("2",$post['shop_info']['tax'], TRUE)||in_array("3",$post['shop_info']['tax'], TRUE))
		{
            
		if(mb_strlen(trim($post['shop_info']['ebcCode'])) !=10)
        {
			 return $this->splash('error',null,"海关备案编码长度为10字节");
        }	

		 if(mb_strlen(trim($post['shop_info']['ebcName'])) > 100)
        {
           return $this->splash('error',null,"海关备案名称长度不能超100字节");
        }
		if(mb_strlen(trim($post['shop_info']['agentCode'])) != 10)
        {
            return $this->splash('error',null,"申报企业海关备案编码长度为10字节");
        }
		 if(mb_strlen(trim($post['shop_info']['agentName'])) > 100)
        {
         return $this->splash('error',null,"申报企业海关备案名称长度不能超100字节");
        }

		 if(mb_strlen(trim($post['shop_info']['shortName'])) != 4)
        {
             return $this->splash('error',null,"海关英文简写长度为4字节");
        }
	   if($post['shop_info']['sea_region'] =='')
        {
             return $this->splash('error',null,"区域不能为空");
        }
		} 
//2016/2/24 lcd
			 if($post['shop_info']['tax']=='')
        {
             return $this->splash('error',null,"业务模式不能为空");
        }
		$post['shop_info']['tax'] = serialize($post['shop_info']['tax']);

         $objRegion = app::get('syslogistics')->model('region');//店铺对应的区域（lcd）
  	      foreach($post['shop_info']['sea_region'] as $val)
				{
			  	 $shopRegion = array(
                'region_id' => $val,
                'shop_id'=>$post['shop_id'],
                 );
				if(!$objRegion->getRow('region_id',array('region_id'=>$val,'shop_id'=>$post['shop_id']))){
					 $result = $objRegion->insert($shopRegion);
				}
				}    

		 //获取店铺对应的区域。//2016/2/24 lcd
		     $filter['shop_id']=$post['shop_id'];
             $region_id = $objRegion->getList($fields='*', $filter);
			 $region_id = array_column($region_id, 'region_id');
			
  	      foreach($region_id as $val)
				{
			  $shopRegion = array(
                'region_id' => $val,
                'shop_id'=>$post['shop_id'],
                 );
				if(!in_array($val, $post['shop_info']['sea_region'])){
					 $result = $objRegion->delete($shopRegion);
				}
				}    

		$post['shop_info']['sea_region'] = serialize($post['shop_info']['sea_region']);//2016/2/24 lcd
        $shop = $this->_getShop($post);
        $shopInfo = $this->_getShopInfo($post);
        $db = app::get('sysshop')->database();
        $db->beginTransaction();
        try{
            $checkResult = $this->__checkpost($post,$msg);
            $objShopType->savedata($post);
            $objShop->saveShop($shop);
            $objShop->shopInfoUpdate($shopInfo,$post['shop_id']);
            $this->adminlog("编辑申请入驻[shopid:{$post['shop_id']}]", 1);
            $db->commit();
        }
        catch (Exception $e)
        {
            $this->adminlog("编辑申请入驻[shopid:{$post['shop_id']}]", 0);
            $db->rollback();
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
        $url="?app=sysshop&ctl=admin_shop&act=index";
        $msg = app::get('sysshop')->_('编辑申请入驻成功');
        return $this->splash('success',null,$msg);

    }
    private function _getShop($data)
    {
        $shop['shop_id'] = $data['shop_id'];
        $shop['shop_descript'] = $data['shop']['shop_descript'];
        $shop['shop_type'] = $data['shop_type'];
        $shop['shop_cat'] = $data['shop_cat'];
        $shop['shopuser_name'] = $data['shopuser_name'];
        $shop['email'] = $data['shop']['email'];
        $shop['mobile'] = $data['shop']['mobile'];
        $shop['shopuser_identity'] = $data['shop']['shopuser_identity'];
        $shop['shopuser_identity_img'] = $data['shop']['shopuser_identity_img'];
        $shop['shop_area'] = $data['shop']['shop_area'];
        $shop['shop_addr'] = $data['shop']['shop_addr'];
        return $shop;
    }
    private function _getShopInfo($data)
    {

        $data['shop_info']['company_name'] = $data['company_name'];
        $data['shop_info']['establish_date'] = strtotime($data['shop_info']['establish_date']);
        $data['shop_info']['license_indate'] = strtotime($data['shop_info']['license_indate']);
        return $data['shop_info'];
    }
     /**
        * @brief  检测输入项
        *
        * @param $postdata
        *
        * @return array
     */
    private function __checkpost(&$postdata)
    {
        $objSysshopEnterapply = kernel::single('sysshop_data_enterapply');

        if(!$postdata['seller_id']) $postdata['seller_id'] = $this->sellerId;
        if(!$postdata['status']) $postdata['status'] = 'active';
        $postdata['enterlog'] = array(array(
            'plan'=>$postdata['enterapply_id'] ? '重新提交入驻申请' : '提交入驻申请',
            'times'=>time(),
            'hint'=>$postdata['enterapply_id'] ? '您修改了入驻申请并重新提交' : '您提交了入驻申请',
        ));

        $postdata['shop_info']['establish_date'] = strtotime($postdata['shop_info']['establish_date']);
        $postdata['shop_info']['license_indate'] = strtotime($postdata['shop_info']['license_indate']);
        $postdata['add_time'] = time();

        if(!$postdata['shop_type'])
        {
            $msg = app::get('sysshop')->_("店铺类型不能为空");
            throw new \LogicException($msg);
        }

        if(!$postdata['shop']['shop_cat'])
        {
            $msg = app::get('sysshop')->_("类目不能为空");
            throw new \LogicException($msg);
        }

/*        if($postdata['shop_type'] != "cat" && !$postdata['new_brand'] && !$postdata['shop']['shop_brand'])
        {
            $msg = app::get('sysshop')->_("请选择店铺品牌 或 新增店品牌");
            throw new \LogicException($msg);
        }*/

        if($postdata['new_brand'])
        {
            $postdata['shop']['postdata_brand'] = "";
        }

        $result = $objSysshopEnterapply->checkBrand($postdata,$msg);
        if(!$result)   throw new \LogicException($msg);

        if($postdata['shop_type'] == "cat")
        {
            $postdata['shop']['postdata_brand'] = "";
            $postdata['new_brand'] = "";
        }

    }


    /**
     * @brief 检测输入项
     *
     * @param $string
     * @param $type (email mobile telephone)
     *
     * @return
     */
    private function __checkTyep($string,$type)
    {
        if($type=="email" && strpos($string,'@')) return true;

        if($type=="mobile" && preg_match("/^1[34578]{1}[0-9]{9}$/",$string)) return true;

        if($type=="telephone" && preg_match("/^([0-9]{3,4}-)?[0-9]{7,8}$/",$string)) return true;

        $isIDCard2 = "/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{4}$/";
        if($type=="identity" && preg_match($isIDCard2,$string)) return true;

        return false;
    }


}


