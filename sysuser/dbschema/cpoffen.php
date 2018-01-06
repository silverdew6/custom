<?php

/**
 * ShopEx 线下优惠券
 *
 * @author     xch
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
return  array(
    'columns' => array(
        'cid' => array(
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'autoincrement' => true,
            'comment' => app::get('sysshop')->_('主键'),
            'width' => 110,
        ),
		'shopid' => array(
	        'type' => 'number',
	        'length' => 10,
	        'comment' => app::get('sysshop')->_('店铺号'),
        ),
        'user_id' => array(
	        'type' => 'number',
	        'length' => 14,
	        'required' => true,
	        'filterdefault' => 'true',
	        'comment' => app::get('sysshop')->_('店铺号'),
        ),
        'coupon_name' => array(
            'type' => 'string',
            'length' => 50,
            'comment' => app::get('sysshop')->_('优惠券名称'),
        ),
        'active_code' => array(
            'type' => 'string',
            'length' => 30,
            'comment' => app::get('sysshop')->_('活动编码'),
        ),
        'coupon_amount'=>array(
         	'type' => 'decimal',
           	'precision' => 10,
            'scale' =>2,
          	'comment' => app::get('sysitem')->_('优惠券面额'),
        ),
        'coupon_code' => array(
            'type' => 'string',
            'length' => 80,
            'comment' => app::get('sysshop')->_('优惠券码'),
        ),
		'use_starttime'=>array(
         'type'=>'number',
          'length' => 14,
          'comment' => app::get('sysshop')->_('有效使用时间'),
        ),
        'use_endtime'=>array(
         'type'=>'number',
          'length' => 14,
          'comment' => app::get('sysshop')->_('有效使用时间结束'),
        ),
		'get_time'=>array(
          'type'=>'number',
          'length' => 14,
          'comment' => app::get('sysshop')->_('领取时间'),
        ),
        'status'=>array(
          'type'=>'number',
          'length' => 1,
          'filterdefault' => 'true',
          'comment' => app::get('sysshop')->_('领取状态'), 
        ),
        'coupon_desc'=>array(
          'type'=>'string',
          'length' => 255,
          'comment' => app::get('sysshop')->_('优惠券描述') 
        ),
        'offen_status'=>array(
          'type'=>'number',
          'length' => 1,
          'comment' => app::get('sysshop')->_('优惠券核销状态') 
        ),
        'offen_date'=>array(
          'type'=>'number',
          'length' => 14,
          'comment' => app::get('sysshop')->_('核销时间') 
        ),
        'palt'=>array(
          'type'=>'string',
          'length' => 10,
          'comment' => app::get('sysshop')->_('核销来源') 
        ),
        'vaild'=>array(
          'type'=>'number',
          'length' => 1,
          'comment' => app::get('sysshop')->_('拉取状态') 
        ),
        'qrc_path'=>array(
          'type'=>'string',
          'length' => 255,
          'comment' => app::get('sysshop')->_('优惠券二维码图片') 
        ),
    ),
    'primary' => 'cid',
    'index' => array(
        'ind_user_id' => array('columns' =>array('user_id')),
        'ind_coupon_code' => array('columns' =>array('coupon_code')),
        'ind_user_active_code'=> array('columns' =>array('user_id','active_code')),
        'ind_user_coupon_code'=> array('columns' =>array('user_id','coupon_code'))
    ),
    'comment' => app::get('sysuser')->_('领取线下优惠券表'),
);

