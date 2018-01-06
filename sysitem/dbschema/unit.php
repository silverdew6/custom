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
            'autoincrement' => true,
            'editable' => false,
            'comment' => app::get('sysitem')->_('海关申报单位ID'),
        ),
        'code' =>
        array (
            'type' => 'string',
            'length' => 8,
            'required' => true,
            'comment' => app::get('sysitem')->_('编号'),
        ),
        'name' =>
        array (
                  'type' => 'string',
                  'length' => 255,
                  'comment' => app::get('sysitem')->_('单位名称式'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('sysitem')->_('海关单位表'),
);
