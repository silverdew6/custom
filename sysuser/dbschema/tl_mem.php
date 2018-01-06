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
        'user_id' =>
        array (
            'type' => 'table:account@sysuser',
            //'pkey' => true,
            'label' => app::get('sysuser')->_('会员用户名'),
        ),
        'memid' =>
        array (
            'type' => 'string',
            'length' => 15,
            'label' => app::get('sysuser')->_('TL_ID'),
            'width' => 75,
            'editable' => false,
            'filtertype' => 'normal',
            'in_list' => false,
        ),
        'memstr' =>
            array (
                'type' => 'string',
                'length' => 30,
                'label' => app::get('sysuser')->_('TL_字符串'),
                'width' => 75,
                'editable' => false,
                'filtertype' => 'normal',
                'in_list' => false,
            ),
    ),
    'primary' => 'user_id',
    'comment' => app::get('sysuser')->_('TL-会员编号'),
);
