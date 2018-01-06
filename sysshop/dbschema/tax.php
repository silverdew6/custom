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
            'comment' => app::get('ectools')->_('业务ID'),
        ),
        'name' =>
        array (
            //'type' => 'varchar(100)',
            'type' => 'string',
            'length' => 50,
            'required' => true,
            'comment' => app::get('ectools')->_('业务名称'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('ectools')->_('业务模式'),
);
