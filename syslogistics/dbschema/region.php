<?php

return  array(
    'columns' => array(
        'id' => array(
            'type' => 'number',
            'required' => true,
            //'pkey' => true,
            'autoincrement' => true,
            'label' => app::get('syslogistics')->_('模块ID'),
            'width' => 110,
        ),
        'shop_id' => array(
            'type'=>'table:shop@sysshop',
            'label' => app::get('syslogistics')->_('店铺名称'),
            'required' => true,
            'in_list' => true,
            'default_in_list'=>true,
        ),

        'region_id' => array(
            'type' => 'number',
            'label' => app::get('syslogistics')->_('区域'),
            'required' => true,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'status' =>
        array (
            'type' =>
            array (
                'off' => app::get('syslogistics')->_('关闭'),
                'on' => app::get('syslogistics')->_('启用'),
            ),
            'default' => 'on',
            'comment' => app::get('syslogistics')->_('是否开启'),
        ),
        'fee_conf' =>
        array(
            'type' => 'text',
            'required' => false,
            'default' => '',
            'editable' => false,
            'comment' => app::get('syslogistics')->_('运费模板id和名称'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('syslogistics')->_('快递模板配置表'),
);

