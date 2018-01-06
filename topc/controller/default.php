<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topc_ctl_default extends topc_controller
{
    public function index()
    {
       $GLOBALS['runtime']['path'][] = array('title'=>app::get('topc')->_('首页'),'link'=>kernel::base_url(1));
         $this->setLayoutFlag('index');
	$goo1=array("item_id"=>6333,"item_name"=>"倍乐思烘焙扁桃仁夹心牛奶巧克力120g","oldprice"=>36.5,"price"=>5.0,"default_image"=> "http://www.myjmall.com/images/df/02/ff/1c015efc1b81a5ba9ffc4c0c78287c4e5fbdd5f9.jpg_l.jpg");
	$goo1=array("item_id"=>6322,"item_name"=>"普拉锐碳酸饮料（西西里经典）","oldprice"=>14.90,"price"=>5.5,"default_image"=> "http://myjmall.com/images/83/f1/0b/b3fa9392f21b12ca5878d43166493c143f860bc7.jpg_l.jpg");

	$pagedata["flashdealList"]=array($goo1,$goo2);
        return $this->page("topc/def_index.html",$pagedata);
    }
}
