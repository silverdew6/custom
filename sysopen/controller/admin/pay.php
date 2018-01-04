
<?php

/**
 * ShopEx licence
 * @author ajx
 * @copyright  Copyright (c) 2005-2014 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class sysopen_ctl_admin_pay extends desktop_controller{

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
        return $this->finder('sysshop_mdl_shop_pay',array(
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'title' => app::get('sysopen')->_('支付企业列表'),
            'use_buildin_tagedit'=>true,
            'actions' => array(
                array(
                    'label'=>app::get('sysopen')->_('添加支付企业'),
                    'href'=>'?app=sysopen&ctl=admin_pay&act=create',
                    'target'=>'dialog::{title:\''.app::get('sysopen')->_('添加支付企业').'\',  width:500,height:320}',
                ),
                array(
                    'label'=>app::get('sysopen')->_('初始化支付企业'),
                    'href'=>'?app=sysopen&ctl=admin_pay&act=init',
                    'target'=>'dialog::{title:\''.app::get('sysopen')->_('初始化支付企业').'\',  width:400,height:200}',
                ),
            ),
        ));
    }

    public function create(){
        return $this->page('sysopen/admin/pay.html');
    }


    function edit(){
        header("cache-control:no-store,no-cache,must-revalidate");
        $filter = array(
            'pay_id'=>$_GET["pay_id"],
        );//var_dump($filter);exit;
        $dlycorpMdl = app::get('sysshop')->model('shop_pay');
        $dlycorpRow = $dlycorpMdl->getRow("*",$filter);
        $pagedata['shop_pay'] = $dlycorpRow;
        /*$pagedata['corpcode'] = $this->_corpCode();*/
        return $this->page('sysopen/admin/pay.html', $pagedata);
    }

    function toEdit(){
        $this->begin('?app=sysopen&ctl=admin_pay&act=index');
        $arr = $_POST['pay'];
        //var_dump($arr);exit;
        //$this->begin('?app=sysopen&ctl=admin_pay&act=index');
        $oItem = app::get('sysshop')->model('shop_pay');

        if($arr['pay_id']){
            $result=$oItem->save($arr);
            $this->adminlog('修改企业信息',$result ? 1 : 0);
        }else{
            $result=$oItem->insert($arr);
            $this->adminlog('添加企业信息',$result ? 1 : 0);
        }
        $this->end($result);
    }
}

