<?php
class sysopen_finder_log{
    public $column_edit = '编辑';         //后台右边栏目目录名,可随意
    public $column_edit_keys = 1;
    public $column_edit_width = 60;

    public function column_edit(&$colList, $list)
    {
        foreach($list as $k=>$row)
        {
            $url='?app=sysopen&ctl=admin_log&act=edit&log_id=' . $row['log_id'];
            $target='dialog::{title:\''.app::get('sysshop')->_('物流企业编辑').'\',width:360, height:340}';
            $title=app::get('sysopen')->_('编辑');;
            $colList[$k] = '<a href="'.$url.'" target="'.$target.'">' . $title . '</a>';
        }
    }
}

