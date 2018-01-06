<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

return  array(
    'columns' => array(
        'cart_tax_id' => array(
            'type' => 'number',
            //'pkey' => true,
            'autoincrement' => true,
            'required' => true,
        ),
        'user_id' => array(
            'type' => 'number',
            'comment' => app::get('systrade')->_('用户id'),
            'label' => app::get('systrade')->_('用户id'),
        ),
        'tax_id' => array(
            'type' => 'number',
            'comment' => app::get('systrade')->_('业务模式和区域合并的id'),
            'label' => app::get('systrade')->_('业务模式和区域合并的id'),
        ),
		  'select_id' => array(
            'type' => 'number',
            'comment' => app::get('systrade')->_('复合id是否被选中'),
            'label' => app::get('systrade')->_('复合id是否被选中'),
        ),
			'disabled_id' => array(
            'type' => 'number',
            'comment' => app::get('systrade')->_('复合id是否被屏蔽'),
            'label' => app::get('systrade')->_('复合id是否被被屏蔽'),
        ),
    ),
    'primary' => 'cart_tax_id',
    'unbackup' => true,
    'comment' => app::get('systrade')->_('购物车区域和业务模式选中'),
);

