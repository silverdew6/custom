<?php
class sysopen_finder_pay{

    public $column_edit = '编辑';         //后台右边栏目目录名,可随意
    public $column_edit_keys = 1;
    public $column_edit_width = 60;

    public function column_edit(&$colList, $list)
    {
        foreach($list as $k=>$row)
        {
            $url='?app=sysopen&ctl=admin_pay&act=edit&pay_id=' . $row['pay_id'];
            $target='dialog::{title:\''.app::get('sysshop')->_('支付企业编辑').'\',width:360, height:340}';
            $title=app::get('sysopen')->_('编辑');;
            $colList[$k] = '<a href="'.$url.'" target="'.$target.'">' . $title . '</a>';
        }
    }

}

