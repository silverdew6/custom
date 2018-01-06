<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ectools_ctl_payment_sea extends desktop_controller{

    var $workground = 'ectools_ctl_payment_ebpinterface';

    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    function index(){

		$pagedata['ebpinterface'] = app::get('ectools')->model('ebpinterface')->getList('*');
        return $this->page('ectools/payments/cfgs/ebpinterface.html', $pagedata);

    }
	//添加海关接口
 function sea_add()
 {
                        $datapost = input::get();
        if($_POST){
					     $this->begin('?app=ectools&ctl=payment_sea&act=index');  
						 $ectoolsdata = app::get('ectools')->model('ebpinterface');
						 $add['name']             =trim($datapost['name']);
						 $add['ebpCode']        =trim($datapost['ebpCode']);
						 $add['ebpName']      =trim($datapost['ebpName']);
						 $add['ebpshorthand']=trim($datapost['ebpshorthand']);
						 $add['ebptype']=trim($datapost['ebptype']);
  //验证名称
						 if(strlen($add['name'])>50){
							return $this->end(false,app::get('ectools')->_('字符长度不能超过50字节'));
											   }
						  $data=$ectoolsdata ->getRow('ebp_id',['name' =>$add['name']]);	
						  if($data){	   
									 return $this->end(false,app::get('ectools')->_('接口名称已经存在'));
										}
//验证海关编码
						 if(strlen($add['ebpCode'])!=10){
							return $this->end(false,app::get('ectools')->_('代码字符长度只能为10字节'));
															   }
						  $data=$ectoolsdata ->getRow('ebp_id',['ebpCode' =>$add['ebpCode']]);	
						  if($data){	   
									 return $this->end(false,app::get('ectools')->_('电商平台代码已经存在'));
										}
//验证电商平台名称
						 if(strlen($add['ebpName'])>100){
						return $this->end(false,app::get('ectools')->_('电商平台名称不能超过100字节'));
														   }
					  $data=$ectoolsdata ->getRow('ebp_id',['ebpName' =>$add['ebpName']]);	
					  if($data){	   
								 return $this->end(false,app::get('ectools')->_('电商平台名称已经存在'));
									}

//验证电商平台简写
					 if(strlen($add['ebpshorthand'])>4){
                    return $this->end(false,app::get('ectools')->_('电商平台名称不能超过4字节'));
                                                                 }
				    $data=$ectoolsdata ->getRow('ebp_id',['ebpshorthand' =>$add['ebpshorthand']]);	
		            if($data){	   
						     return $this->end(false,app::get('ectools')->_('电商平台简写已经存在'));
						        }
                $ectoolsdata->insert($add);
               $this->end(true, app::get('desktop')->_('保存成功！'));

		}
  
   return $this->page('ectools/payments/cfgs/sea_add.html', $pagedata);
 }
//编辑海关接口
function editinterface()
{
	     $datapost = input::get();
         $ectoolsdata = app::get('ectools')->model('ebpinterface');
		 $data['ebpinterface']=$ectoolsdata ->getRow('*',['ebp_id' =>$datapost['ebp_id']]);	
		    $this->begin('?app=ectools&ctl=payment_sea&act=index');  
		   
		 //编辑接口
	    if($datapost['edit']){
				         $filter['ebp_id']             =trim($datapost['ebp_id']);
				         $edit['name']                 =trim($datapost['name']);
						 $edit['ebpCode']            =trim($datapost['ebpCode']);
						 $edit['ebpName']           =trim($datapost['ebpName']);
						 $edit['ebpshorthand']    =trim($datapost['ebpshorthand']);
						 $edit['ebptype']             =trim($datapost['ebptype']);
       //验证名称				 
						$edit['name']=urlencode($edit['name']);//将关键字编码
						$edit['name']=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99)+/",'',$edit['name']);
						$edit['name']=urldecode($edit['name']);//将过滤后的关键字解码
						//验证海关编码

						 if(strlen($edit['name'])>50){
							return $this->end(false,app::get('ectools')->_('字符长度不能超过50字节'));
											   }
						 if(strlen($edit['ebpCode'])!=10){
							return $this->end(false,app::get('ectools')->_('代码字符长度只能10字节'));
															   }
//验证电商平台名称

						 if(strlen($edit['ebpName'])>100){
						return $this->end(false,app::get('ectools')->_('电商平台名称不能超过100字节'));
														   }
//验证电商平台简写
					 if(strlen($edit['ebpshorthand'])!=4){
                    return $this->end(false,app::get('ectools')->_('电商平台简写只能是4字节'));
                                                                 }

				   if($ectoolsdata->update($edit,$filter)){
                 $this->end(true, app::get('desktop')->_('保存成功！'));
					}else{
					  $this->end(false, app::get('desktop')->_('保存失败！'));
					}

	       }

        return view::make('ectools/payments/cfgs/editinterface.html',$data);
}


//区域首页
    function region(){
	
		$pagedata['region'] = app::get('ectools')->model('region')->getList('*');

		foreach ( $pagedata['region'] as &$val ) {
            $val ['pay']=unserialize($val ['pay']);
	        $paycount= count($val ['pay']);
		    $cname='';
			for($i=0;$i<$paycount;$i++){
            $strPaymnet = $this->app->getConf($val ['pay'][$i]);
			$arrPaymnet = unserialize($strPaymnet);
			//获取支付名称
			$payName = $arrPaymnet['setting']['pay_name'] ? $arrPaymnet['setting']['pay_name'] : $object->name; 
			$cname=$cname."  ".$payName;
			}
            $val ['pay']=$cname;
            $interface=unserialize($val ['interface']);
			$val ['interface']=$interface[0];
		    //查询出来对应的接口名称，在封装成一个数组。
		     $ectoolsdata = app::get('ectools')->model('ebpinterface');
		     $interface=$ectoolsdata ->getRow('name',['ebp_id' =>$interface[0]]);	
			$val ['interface']=$interface['name'];

		}
     return $this->page('ectools/payments/cfgs/region.html', $pagedata);

    }
    // 添加区域
    public function toSetOffline()
    {
		//获取开启的所有支付列表。
	
        $objPayment = kernel::single('ectools_data_payment');
        $pagedata['paymen']=$objPayment->getPayments('CNY');

         $ectoolsdata = app::get('ectools')->model('ebpinterface');
         $filter = ['ebptype' => 0];
		 $fields = "ebp_id,name";
		 $pagedata['ebpinterface']=$ectoolsdata ->getList($fields, $filter);	
        return view::make('ectools/payments/cfgs/sea_offline.html',$pagedata);
    }
//处理编辑业务区域
    public function doSetOffline()
    {
        $datapost = input::get();	
        if($datapost){
               $this->begin('?app=ectools&ctl=payment_sea&act=region');  
                $name=trim($datapost['name']);
				$ectoolsdata = app::get('ectools')->model('region');
                if($name==''){
                    return $this->end(false,app::get('ectools')->_('区域名称不能为空'));
                                       }
				 if(strlen($name)>50){
							return $this->end(false,app::get('ectools')->_('字符长度不能超过50字节'));
							 }
		   $f['name']=$name;
            $id=   $ectoolsdata->getRow('id',$f);
			if(($id['id']!=$datapost['region_id'])&&($id['id']>0)){
			   return $this->end(false,app::get('ectools')->_('区域名称已经被使用'));
			}
          
                if($datapost['region_pay']==''){
                    return $this->end(false,app::get('ectools')->_('支付方式不能为空'));
                                       }
                if($datapost['region_interface']==''){
                    return $this->end(false,app::get('ectools')->_('海关接口不能为空'));
                                       }
                  $appdate['name']  = $name;
                  $appdate['pay']  =  serialize($datapost['region_pay']);
				  $appdate['interface']  =  $datapost['region_interface'];
				  $appdate['state']  =  $datapost['state'];
			       $filter['id']          =trim($datapost['region_id']);
              if($ectoolsdata->update($appdate,$filter)){
				if($appdate['state']==1){//2016/4/15  如果区域被疲敝，那么所有的店铺的该区域就不能使用，必须删除
                $shopinfo = app::get('sysshop')->model('shop_info');
				$id[$filter['id']]=$filter['id'];
               $serregion= $shopinfo->getList('sea_region,info_id');
			   foreach($serregion as $v){
				if(!empty($v['sea_region'])){
		        $sea_region  =unserialize($v['sea_region']);
		        $result=array_diff($sea_region,$id);
		    $update_region['sea_region'] = serialize($result);//修改店铺的
			$serregion= $shopinfo->update($update_region,$v['info_id']);
				}
			}
      $shopinfo_enterapply = app::get('sysshop')->model('enterapply');
      $enterapplyinfo= $shopinfo_enterapply->getList('sea_region,enterapply_id');
	 foreach($enterapplyinfo as $vv){
		    if(!empty($vv['sea_region'])){
		    $sea_region_enterapply  =unserialize($vv['sea_region']);
		    $result_enterapply=array_diff($sea_region_enterapply,$id);
		    $update_region_enterapply['sea_region'] = serialize($result_enterapply);//修改店铺注册信息
			$shopinfo_enterapply->update($update_region_enterapply,$vv['enterapply_id']);
				}
	        }

				}
				   $this->end(true, app::get('desktop')->_('保存成功！'));
                  }else{
				      return $this->end(false,app::get('ectools')->_('保存失败'));
				  }
		}
    }

		function editSetOffline()
		{
	     $datapost = input::get();
         $ectoolsdata = app::get('ectools')->model('region');
		 $data['region']=$ectoolsdata ->getRow('*',['id' =>$datapost['region_id']]);	
         $data['region']['pay'] =  unserialize($data['region']['pay']);
		 $interface=  unserialize($data['region']['interface']);
		 $data['region']['ebp'] =$interface[0];
         $objPayment = kernel::single('ectools_data_payment');
         $data['region']['paymen']=$objPayment->getPayments('CNY');
         $ectoolsdata = app::get('ectools')->model('ebpinterface');
         $filter = ['ebptype' => 0];
		 $fields = "ebp_id,name";
		 $data['ebpinterface']=$ectoolsdata ->getList($fields, $filter);	
        return view::make('ectools/payments/cfgs/sea_editSet.html',$data);
		}

    public function addSetOffline()
    {

        $datapost = input::get();	
		 $ectoolsdata = app::get('ectools')->model('region');
               $this->begin('?app=ectools&ctl=payment_sea&act=region');  
                $name=trim($datapost['name']);
                if($name==''){
                    return $this->end(false,app::get('ectools')->_('区域名称不能为空'));
                                       }
					$f['name']=$name;
                   $id=   $ectoolsdata->getRow('id',$f);
			    if($id['id']){
			   return $this->end(false,app::get('ectools')->_('区域名称已经被使用'));
			     }
				 if(strlen($name)>50){
							return $this->end(false,app::get('ectools')->_('字符长度不能超过50字节'));
							 }

                if($datapost['region_pay']==''){
                    return $this->end(false,app::get('ectools')->_('支付方式不能为空'));
                                       }
                if($datapost['region_interface']==''){
                    return $this->end(false,app::get('ectools')->_('海关接口不能为空'));
                                       }
                  $appdate['name']  = $name;
                  $appdate['pay']  =  serialize($datapost['region_pay']);
				  $appdate['interface']  =  $datapost['region_interface'];
				  $appdate['state']  =  $datapost['state'];
                 if($ectoolsdata->insert($appdate)){
				   $this->end(true, app::get('desktop')->_('保存成功！'));
                  }else{
				      return $this->end(false,app::get('ectools')->_('保存失败'));
				  }
    }



}
