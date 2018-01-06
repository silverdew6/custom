<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

return array (
    'columns' =>
    array (
        'ebp_id' =>
        array (
            'type' => 'number',
            'required' => true,
            //'pkey' => true,
            'autoincrement' => true,
            'editable' => false,
            'comment' => app::get('ectools')->_('接口ID'),
        ),
        'name' =>
        array (
            //'type' => 'varchar(100)',
            'type' => 'string',
            'length' => 50,
            'required' => true,
            'comment' => app::get('ectools')->_('接口名称'),
        ),
        'ebpName' =>
            array (
                  'type' => 'string',
                  'length' => 100,
                  'comment' => app::get('ectools')->_('海关备案名称'),
            ),  
        'ebpCode' =>
        array (
           'type' => 'string',
            'length' => 10,
            'comment' => app::get('ectools')->_('海关备案代码'),
        ),
		'ebpshorthand' =>
        array (
           'type' => 'string',
            'length' => 4,
            'comment' => app::get('ectools')->_('海关简写'),
        ),
		'key' =>
            array (
                  'type' => 'string',
                  'length' => 50,
                  'comment' => app::get('ectools')->_('接口英文名称'),
            ),  
		'ebptype' =>
        array (
           'type' => 'number',
            'length' => 1,
            'comment' => app::get('ectools')->_('状态,1为关闭，0为开启'),
        ),
    ),
    'primary' => 'ebp_id',
    'comment' => app::get('ectools')->_('海关接口表'),
);
