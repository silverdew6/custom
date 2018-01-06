<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_shop_dlytmpl extends topshop_controller {

    /**
     * @brief 运费模板列表显示
     */
    public function index()
    {

        $params['fields'] = "template_id,name,modifie_time,status,fee_conf,corp_id";
        $params['shop_id'] = $this->shopId;
        $pagedata = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$params,'seller');

        $this->contentHeaderTitle = app::get('topshop')->_('快递运费模板列表');
        return $this->page('topshop/shop/dlytmpl.html', $pagedata);
    }


	    public function region()
    {

             $filter['shop_id'] = $this->shopId;
			 $filter['status'] ='on';
			 $params['fields'] = "template_id,name";
          //获取有效的物流模板。
			 $objMdldlytmpl = app::get('syslogistics')->model('dlytmpl');
             $dlytmpl = $objMdldlytmpl->getList($params, $filter);
             $pagedata['dlytmpl']=$dlytmpl;
			 //获取店铺对应的区域。
			 $objRegion = app::get('syslogistics')->model('region');
             $region = $objRegion->getList($fields='*', $filter);

               $objMdlRegion = app::get('ectools')->model('region');
				foreach($region as &$val)
				{
					$name=   $objMdlRegion->getRow('name',array('id'=>$val['region_id']));
					$val['name']= $name['name'];
					$val['fee_conf']= unserialize($val['fee_conf']);
				}    
             $pagedata['data']=$region;
            $this->contentHeaderTitle = app::get('topshop')->_('区域配置');
            return $this->page('topshop/shop/region.html', $pagedata);
    }

    /**
     * @brief 新增模板和编辑模板页面显示
     *
     */

    public function saveregion()
    {
        $params = input::get(); 
        if( !$params['sea_region'] )
        {
            $msg = "快递公司必选";
            return $this->splash("error","",$msg,true);
        }

        $params['fee_conf'] = $params['sea_region'];
        $objRegion = app::get('syslogistics')->model('region');
        $filter['id'] = $params['id'];
        if( $objRegion->update($params,$filter) )
        {
        $msg = app::get('topshop')->_('保存成功');
        $url = url::action('topshop_ctl_shop_dlytmpl@region');
        return $this->splash('success',$url,$msg,true);
		 }
     }




    public function editView()
    {
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_shop_dlytmpl@index'),'title' => app::get('topshop')->_('快递运费模板列表')],
            ['title' => app::get('topshop')->_('新增运费模板')],
        );
        $this->contentHeaderTitle = app::get('topshop')->_('新增运费模板');

        $template_id = input::get('template_id');
        if( $template_id )
        {
            $params['template_id'] = $template_id;
            $params['shop_id'] = $this->shopId;
            $params['fields'] = "template_id,name,modifie_time,status,fee_conf,corp_id,order_sort,protect,protect_rate,minprice";
            $data = app::get('topshop')->rpcCall('logistics.dlytmpl.get',$params);
            $data['corp_id'] = explode(',',$data['corp_id']);
            $pagedata['tmplData'] = $data;
            $this->contentHeaderTitle = app::get('topshop')->_('编辑运费模板');
        }

        //快递公司代码
        $params['fields'] = "corp_id,corp_name";
        $corpData = app::get('topshop')->rpcCall('logistics.dlycorp.get.list',$params);
        $pagedata['corpData'] = $corpData['data'];

        return $this->page('topshop/shop/editdlytmpl.html', $pagedata);
    }

    public function savetmpl()
    {
        $params = input::get();
        $params['shop_id'] = $this->shopId;
        $isExists = $this->isExists();
        if($isExists)
        {
            $msg = "此模板名称已存在，请换一个重试";
            return $this->splash("error","",$msg,true);
        }

        if( !$params['corp_id'] )
        {
            $msg = "快递公司必填";
            return $this->splash("error","",$msg,true);
        }

        $params['corp_id'] = implode(',',$params['corp_id']);
        if( $params['template_id'] )
        {
            try
            {
                if($params['fee_conf'])
                {
                    $params['fee_conf'] = json_encode($params['fee_conf']);
                }
                app::get('topshop')->rpcCall('logistics.dlytmpl.update',$params,'seller');
            }
            catch(Exception $e)
            {
                $msg = $e->getMessage();
                return $this->splash('error','',$msg,true);
            }
        }
        else
        {
            try
            {
                if($params['fee_conf'])
                {
                    $params['fee_conf'] = json_encode($params['fee_conf']);
                }
                app::get('topshop')->rpcCall('logistics.dlytmpl.add',$params, 'seller');
            }
            catch(Exception $e)
            {
                $msg = $e->getMessage();
                return $this->splash('error','',$msg,true);
            }
        }

        $msg = app::get('topshop')->_('保存成功');
        $url = url::action('topshop_ctl_shop_dlytmpl@index');
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * @brief ajax请求判断运费模板名称是否存在
     */
    public function isExists()
    {
        $template = app::get('topshop')->rpcCall('logistics.dlytmpl.get',array('name'=>input::get('name'),'shop_id'=>$this->shopId,'fields'=>'template_id'));
        $template_id = $template['template_id'];

        if( $template_id && (!input::get('template_id') || input::get('template_id') != $template_id) )
        {
            $status = true;//已存在
        }
        else
        {
            $status = false;//不存在
        }
        return $status;
    }

    public function remove()
    {
        $filter['template_id'] = input::get('template_id');
        $filter['shop_id'] = $this->shopId;
        try
        {
            app::get('topshop')->rpcCall('logistics.dlytmpl.delete',$filter,'seller');
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }
        $url = url::action('topshop_ctl_shop_dlytmpl@index');
        return $this->splash('success',$url,$msg,true);
    }
}

