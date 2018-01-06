<?php
class topshop_ctl_shop_information extends topshop_controller{

    public function index()
    {
        //获取商铺ID
        $shop_id = shopAuth::getShopId();
        //查询数据库
        //api获取店铺基本信息
        //lib根据条件查询店铺信息
        //model对数据库进行操作
        $getProject = app::get('topshop')->rpcCall('shop.get.infor',array('shop_id'=>$shop_id));
        $shopdata = $getProject[0];
        $this->contentHeaderTitle = app::get('topshop')->_('企业信息');
        $pageData['infor']=$getProject[0];
        $pageData['pay']=$getProject[1];
        $pageData['log']=$getProject[2];
        $pageData['import']=$getProject[3];
        $pageData['sign']=$getProject[4];

       // var_dump($getProject);exit;
        return $this->page('topshop/shop/information.html',$pageData);
    }

    public function save()
    {
        //接收数据
        $postData = input::get();
        //获取商铺ID
        $shop_id = shopAuth::getShopId();
        if(empty($postData['shop_id'])){
            $postData['shop_id']=$shop_id;
        }
        //在此处对数据进行表单验证更安全

         try
         {
             $result = app::get('topshop')->rpcCall('shop.save.infor',$postData);

             if( $result )
             {
                 $msg = app::get('topshop')->_('更改成功');
                 $result = 'success';
             }
             else
             {
                 $msg = app::get('topshop')->_('更改失败');
                 $result = 'error';
             }
         }
         catch(Exception $e)
         {
             $msg = $e->getMessage();
             $result = 'error';
         }
         $url = url::action('topshop_ctl_shop_information@index');
         return $this->splash($result,$url,$msg,true);

    }

}


