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
        'code_id' => array(
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'autoincrement' => true,
            'comment' => app::get('sysitem')->_('主键'),
            'width' => 110,
        ),
		'code_ts' => array(
        'type' => 'string',
        'length' => 16,
         'comment' => app::get('sysitem')->_('编号'),
        ),
		'begin_date' => array(
        'type' => 'time',
         'comment' => app::get('sysitem')->_('开始时间'),
        ),
        'end_date' => array(
            'type' => 'time',
            'comment' => app::get('sysitem')->_('结束时间'),
        ),
		'g_name'=>array(
         'type'=>'string',
          'length' => 255,
          'comment' => app::get('sysitem')->_('海关商品名称'),
        ),
		'control_mark'=>array(
         'type'=>'string',
          'length' => 500,
          'comment' => app::get('sysitem')->_('监管证件范围'),
        ),
		 'tax_rate'=>array(
            'type' => 'decimal',
            'precision' => 19,
            'scale' => 5,
          'comment' => app::get('sysitem')->_('增值税'),
        ),
		'reg_rate'=>array(
            'type' => 'decimal',
            'precision' => 19,
            'scale' => 5,
          'comment' => app::get('sysitem')->_('消费税'),
        ),
		'high_rate'=>array(
            'type' => 'decimal',
            'precision' => 19,
            'scale' => 5,
          'comment' => app::get('sysitem')->_('税'),
        ),
	'low_rate'=>array(
            'type' => 'decimal',
            'precision' => 19,
            'scale' => 5,
          'comment' => app::get('sysitem')->_('税'),
        ),
	'out_rate'=>array(
            'type' => 'decimal',
            'precision' => 19,
            'scale' => 5,
          'comment' => app::get('sysitem')->_('出口税率'),
        ),
	'tariff_mark'=>array(
         'type'=>'string',
          'length' => 500,
          'comment' => app::get('sysitem')->_(''),
        ),
	'other_type'=>array(
         'type'=>'string',
          'length' => 50,
          'comment' => app::get('sysitem')->_(''),
        ),

	'unit_1'=>array(
         'type'=>'string',
          'length' => 3,
          'comment' => app::get('sysitem')->_('法定单位'),
        ),
		 'unit_2'=>array(
         'type'=>'string',
          'length' => 3,
          'comment' => app::get('sysitem')->_('第二单位'),
        ),
		'note_s'=>array(
         'type'=>'string',
          'length' => 500,
          'comment' => app::get('sysitem')->_('备注'),
        ),
				'lsjm_flag'=>array(
         'type'=>'string',
          'length' => 500,
          'comment' => app::get('sysitem')->_(''),
        ),
	'create_time'=>array(
     'type'=>'time',
      'comment' => app::get('sysitem')->_('创建时间'),
        ),
    ),
    'primary' => 'code_id',
    'comment' => app::get('sysitem')->_('海关商品编码表'),
);

