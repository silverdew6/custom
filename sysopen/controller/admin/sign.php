
<?php

/**
 * ShopEx licence
 * @author ajx
 * @copyright  Copyright (c) 2005-2014 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class sysopen_ctl_admin_sign extends desktop_controller{

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
        return $this->finder('sysshop_mdl_shop_sign',array(
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'title' => app::get('sysopen')->_('承运企业列表'),
            'use_buildin_tagedit'=>true,
            'actions' => array(
                array(
                    'label'=>app::get('sysopen')->_('添加承运企业'),
                    'href'=>'?app=sysopen&ctl=admin_sign&act=create',
                    'target'=>'dialog::{title:\''.app::get('sysopen')->_('添加承运企业').'\',  width:240,height:120}',
                ),
            ),
        ));
    }

    public function create(){
        return $this->page('sysopen/admin/sign.html');
    }


    public function edit(){
        header("cache-control:no-store,no-cache,must-revalidate");
        $filter = array(
            'sign_id'=>$_GET["sign_id"],
        );//var_dump($filter);exit;
        $dlycorpMdl = app::get('sysshop')->model('shop_sign');
        $dlycorpRow = $dlycorpMdl->getRow("*",$filter);
        $pagedata['shop_sign'] = $dlycorpRow;
        return $this->page('sysopen/admin/sign.html', $pagedata);
    }

    public function toEdit(){
        $this->begin('?app=sysopen&ctl=admin_sign&act=index');
        $arr = $_POST['sign'];
        //var_dump($arr);exit;
        //$this->begin('?app=sysopen&ctl=admin_pay&act=index');
        $oItem = app::get('sysshop')->model('shop_sign');

        if($arr['sign_id']){
            $result=$oItem->save($arr);
            $this->adminlog('修改企业信息',$result ? 1 : 0);
        }else{
            $result=$oItem->insert($arr);
            $this->adminlog('添加企业信息',$result ? 1 : 0);
        }
        $this->end($result);
    }
}

