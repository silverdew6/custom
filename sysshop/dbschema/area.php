<?php

/**
 * ShopEx LuckyMall
 *
 * @author     ajx
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
/*'country_id' => array(
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'autoincrement' => true,
            'comment' => app::get('ctaxrate')->_('主键'),
            'width' => 110,
        ),*/
return  array(
    'columns' => array(
        'area_id' => array(
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'autoincrement' => true,
            'comment' => app::get('sysshop')->_('主键'),
            'width' => 110,
        ),
		'cus_code' => array(
        'type' => 'number',
        'length' => 8,
         'comment' => app::get('sysshop')->_('编号'),
        ),
        'cn_first' => array(
            'type' => 'string',
            'length' => 5,
            'comment' => app::get('sysshop')->_('第一个字母'),
        ),
        'cn_name' => array(
            'type' => 'string',
            'length' => 50,
            'comment' => app::get('sysshop')->_('原产中文'),
        ),
		'en_name'=>array(
         'type'=>'string',
          'length' => 50,
          'comment' => app::get('sysshop')->_('原产英文'),
        ),
		 'area_img'=>array(
         'type'=>'string',
           // 'required' => true,
          'length' => 255,
          'comment' => app::get('sysshop')->_('原产地国旗'),
        ),
    ),
    'primary' => 'area_id',
    'comment' => app::get('sysshop')->_('原产地信息表'),
);

