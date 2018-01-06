
<?php

/**
 * ShopEx licence
 * @author ajx
 * @copyright  Copyright (c) 2005-2014 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class sysopen_ctl_admin_log extends desktop_controller{

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
        return $this->finder('sysshop_mdl_shop_log',array(
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'title' => app::get('sysopen')->_('物流企业列表'),
            'use_buildin_tagedit'=>true,
            'actions' => array(
                array(
                    'label'=>app::get('sysopen')->_('添加物流企业'),
                    'href'=>'?app=sysopen&ctl=admin_log&act=create',
                    'target'=>'dialog::{title:\''.app::get('sysopen')->_('添加物流企业').'\',  width:500,height:320}',
                ),
            ),
        ));
    }

    public function create(){
        return $this->page('sysopen/admin/log.html');
    }


    function edit(){
        header("cache-control:no-store,no-cache,must-revalidate");
        $filter = array(
            'log_id'=>$_GET["log_id"],
        );//var_dump($_GET);exit;
        $dlycorpMdl = app::get('sysshop')->model('shop_log');
        $dlycorpRow = $dlycorpMdl->getRow("*",$filter);
        $pagedata['shop_log'] = $dlycorpRow;
        /*$pagedata['corpcode'] = $this->_corpCode();*/
        return $this->page('sysopen/admin/log.html', $pagedata);
    }

    function toEdit(){
        $this->begin('?app=sysopen&ctl=admin_log&act=index');
        $arr = $_POST['log'];
        //var_dump($arr);exit;
        //$this->begin('?app=sysopen&ctl=admin_pay&act=index');
        $oItem = app::get('sysshop')->model('shop_log');

        if($arr['log_id']){
            $result=$oItem->save($arr);
            $this->adminlog('修改物流企业信息',$result ? 1 : 0);
        }else{
            $result=$oItem->insert($arr);
            $this->adminlog('添加物流企业信息',$result ? 1 : 0);
        }
        $this->end($result);
    }
}

