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
        'id' =>
        array (
            'type' => 'number',
            'required' => true,
            //'pkey' => true,
            'autoincrement' => true,
            'editable' => false,
            'comment' => app::get('ectools')->_('区域ID'),
        ),
        'name' =>
        array (
            //'type' => 'varchar(100)',
            'type' => 'string',
            'length' => 50,
      
            'required' => true,
            'comment' => app::get('ectools')->_('区域名称'),
        ),
        'pay' =>
        array (
                  'type' => 'string',
                  'length' => 1024,
                  'comment' => app::get('ectools')->_('支付方式'),
        ),
        'interface' =>
        array (
           'type' => 'string',
            'length' => 521,
            'comment' => app::get('ectools')->_('开启接口'),
        ),
		        'state' =>
        array (
           'type' => 'number',
            'length' => 1,
			'default' => 0,
            'comment' => app::get('ectools')->_('状态'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('ectools')->_('区域表'),
);
