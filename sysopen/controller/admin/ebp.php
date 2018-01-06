
<?php

/**
 * ShopEx licence
 * @author ajx
 * @copyright  Copyright (c) 2005-2014 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class sysopen_ctl_admin_ebp extends desktop_controller{

    /*public $workground = 'syslogistics.workground.logistics';*/


    /**
     * 支付企业列表
     * @var string $key
     * @var int $offset
     * @access public
     * @return int
     */
    public function index()
    {
        return $this->finder('sysshop_mdl_shop_other',array(
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'title' => app::get('sysopen')->_('电商平台列表'),
            'use_buildin_tagedit'=>true,
            'actions' => array(
                array(
                    'label'=>app::get('sysopen')->_('添加电商平台'),
                    'href'=>'?app=sysopen&ctl=admin_ebp&act=create',
                    'target'=>'dialog::{title:\''.app::get('sysopen')->_('添加电商平台').'\',  width:240,height:120}',
                ),
            ),
        ));
    }

    public function create(){
        return $this->page('sysopen/admin/ebp.html');
    }


    public function edit(){
        header("cache-control:no-store,no-cache,must-revalidate");
        $filter = array(
            'other_id'=>$_GET["other_id"],
        );//var_dump($filter);exit;
        $dlycorpMdl = app::get('sysshop')->model('shop_other');
        $dlycorpRow = $dlycorpMdl->getRow("*",$filter);
        $pagedata['shop_other'] = $dlycorpRow;
        return $this->page('sysopen/admin/ebp.html', $pagedata);
    }

    public function toEdit(){
        $this->begin('?app=sysopen&ctl=admin_ebp&act=index');
        $arr = $_POST['other'];
        //var_dump($arr);exit;
        //$this->begin('?app=sysopen&ctl=admin_pay&act=index');
        $oItem = app::get('sysshop')->model('shop_other');

        if($arr['other_id']){
            $result=$oItem->save($arr);
            $this->adminlog('修改企业信息',$result ? 1 : 0);
        }else{
            $result=$oItem->insert($arr);
            $this->adminlog('添加企业信息',$result ? 1 : 0);
        }
        $this->end($result);
    }
}

