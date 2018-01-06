<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

return array(
    'columns' => array(
        'c_id' => array(
            //'type' => 'int(10)',
            'type' => 'number',
            'required' => true,
            //'pkey' => true,
            'autoincrement' => true,
            'editable' => false,
            'comment' => app::get('sysuser')->_('身份证列表ID'),
        ),
        'name' => array(
            'is_title' => true,
            'type' => 'string',
            'length' => 50,
            'editable' => false,
            'comment' => app::get('sysuser')->_('姓名'),
        ),
		'card_id' => array(
        'type' => 'string',
        'length' => 20,
        'comment' => app::get('sysuser')->_('身份证ID'),
        ),
    ),
    'primary' => 'c_id',
    'index' => array(
        'ind_card_id' => ['columns' => ['card_id']],
    ),
    'comment' => app::get('sysuser')->_('会员地址表'),
);
