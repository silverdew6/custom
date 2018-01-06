<?php
class sysopen_finder_ebp{
    public $column_edit = '编辑';         //后台右边栏目目录名,可随意
    public $column_edit_keys = 1;
    public $column_edit_width = 60;

    public function column_edit(&$colList, $list)
    {
        foreach($list as $k=>$row)
        {
            $url='?app=sysopen&ctl=admin_ebp&act=edit&other_id=' . $row['other_id'];
            $target='dialog::{title:\''.app::get('sysshop')->_('电商平台编辑').'\',width:240, height:150}';
            $title=app::get('sysopen')->_('编辑');;
            $colList[$k] = '<a href="'.$url.'" target="'.$target.'">' . $title . '</a>';
        }
    }
}

