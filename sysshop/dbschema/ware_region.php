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
            'comment' => app::get('sysshop')->_('区域ID'),
        ),
        'name' =>
        array (
            //'type' => 'varchar(100)',
            'type' => 'string',
            'length' => 50,
            'required' => true,
            'comment' => app::get('sysshop')->_('区域名称'),
        ),
        'shop_id' =>
        array (
           'type' => 'number',
            'length' => 10,
			'default' => 0,
            'comment' => app::get('sysshop')->_('所属店铺ID'),
        ),
        'tax' =>
        array (
           'type' => 'number',
            'length' => 3,
			'default' => 1,
            'comment' => app::get('sysshop')->_('选择的业务类型'),
        ),
        'paysetting' =>
        array (
                  'type' => 'string',
                  'length' => 1024,
                  'comment' => app::get('sysshop')->_('支付方式'),
        ),
        'shippings' =>
        array (
           'type' => 'string',
            'length' => 1024,
            'comment' => app::get('sysshop')->_('配置物流方式'),
        ),
		'state' =>
        array (
           'type' => 'number',
            'length' => 1,
			'default' => 0,
            'comment' => app::get('sysshop')->_('状态'),
        ),
        'modified_time' =>
        array (
           'type' => 'number',
            'length' => 14,
			'default' => 0,
            'comment' => app::get('sysshop')->_('创建时间'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('sysshop')->_('商家发货区域表'),
);
