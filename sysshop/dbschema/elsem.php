<?php
return  array(
    'columns'=>array(
        'ord_id'=>array(
            'type'=>'number',
            //'pkey' => true,
            'autoincrement' => true,
            //'extra' => 'auto_increment',
            'required' => true,
            'order' => 1,
            'label' => app::get('sysshop')->_('报文回执id'),
            'comment' => app::get('sysshop')->_('报文回执id 自增'),
        ),
		'tid' => array(
            'type'=>'table:trade@systrade',
            'label' => app::get('sysshop')->_('订单号'),
            'required' => true,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'name'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 100,
            'required'=>true,
			'in_list'=>true,           //在后台显示时会用到
			'default_in_list'=>true,    //在后台显示时会用到
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('报文名'),
            'comment' => app::get('sysshop')->_('报文名'),
        ),
		'MessageID'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
			'in_list'=>true,           //在后台显示时会用到
			'default_in_list'=>true,    //在后台显示时会用到
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('guid36 位'),
            'comment' => app::get('sysshop')->_('guid36 位'),
        ),
		'type'=>array(
            //'type'=>'char(18)',
            'type' => 'bool',
            'required'=>true,
            'default' => 0,
            'label' => app::get('sysshop')->_('申报类型'),
            'comment' => app::get('sysshop')->_('申报类型,1-订单，2-支付流水，3-运单，4-企业内部编码，5-其他'),
        ),
        'typeNo'=>array(
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'in_list'=>true,           //在后台显示时会用到
            'default_in_list'=>true,    //在后台显示时会用到
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('类型编码'),
            'comment' => app::get('sysshop')->_('类型编码'),
        ),
        'platform'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 10,
            'default'=>'',
            'required'=>true,
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('平台'),
            'comment' => app::get('sysshop')->_('平台'),
        ),
		'custom'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 10,
            'default'=>'',
            'required'=>true,
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('海关'),
            'comment' => app::get('sysshop')->_('海关'),
        ),
		'checkout'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 10,
            'default'=>'',
            'required'=>true,
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('检验检疫'),
            'comment' => app::get('sysshop')->_('检验检疫'),
        ),
		'platformS'=>array(
            //'type'=>'char(18)',
            'type' => 'bool',
            'required'=>true,
            'default' => 1,
            'label' => app::get('sysshop')->_('平台回执状态'),
            'comment' => app::get('sysshop')->_('平台回执状态,1-成功 0-失败。默认为0'),
        ),
		'customS'=>array(
            //'type'=>'char(18)',
            'type' => 'bool',
            'required'=>true,
            'default' => 1,
            'label' => app::get('sysshop')->_('海关回执状态'),
            'comment' => app::get('sysshop')->_('海关回执状态,1-成功 0-无效。默认为0'),
        ),
		'checkoutS'=>array(
            //'type'=>'char(18)',
            'type' => 'bool',
            'required'=>true,
            'default' => 1,
            'label' => app::get('sysshop')->_('检验检疫回执状态'),
            'comment' => app::get('sysshop')->_('检验检疫回执状态,1-暂存，2-申报，默认为2'),
        ),
		'platformInfo'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 50,
            'default'=>'',
            'required'=>true,
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('平台回复'),
            'comment' => app::get('sysshop')->_('平台回复'),
        ),
		'customInfo'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 50,
            'default'=>'',
            'required'=>true,
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('海关回复'),
            'comment' => app::get('sysshop')->_('海关回复'),
        ),
        'checkoutInfo'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'default'=>'',
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('检验检疫回复'),
            'comment' => app::get('sysshop')->_('检验检疫回复'),
            'order' => 10,
        ),
        'platform_time' => array(
            'type' => 'time',
            'in_list' => true,
            'default_in_list' => true,
            'width' => '100',
            'order' => 17,
            'label' => app::get('sysshop')->_('平台回复时间'),
            'comment' => app::get('sysshop')->_('平台回复时间'),
        ),
		'custom_time' => array(
            'type' => 'time',
            'in_list' => true,
            'default_in_list' => true,
            'width' => '100',
            'order' => 17,
            'label' => app::get('sysshop')->_('海关回复时间'),
            'comment' => app::get('sysshop')->_('海关回复时间'),
        ),
		'checkout_time' => array(
            'type' => 'time',
            'in_list' => true,
            'default_in_list' => true,
            'width' => '100',
            'order' => 17,
            'label' => app::get('sysshop')->_('检验检疫回复时间'),
            'comment' => app::get('sysshop')->_('检验检疫回复时间'),
        ),
        'status'=>array(
            //'type'=>'char(18)',
            'type' => 'bool',
            'required'=>true,
            'default' => 0,
            'label' => app::get('sysshop')->_('报文回执状态'),
            'comment' => app::get('sysshop')->_('报文回执状态,为5时表示全通过,默认为0'),
        ),
        'ex_type'=>array(
            //'type'=>'char(18)',
            'type' => 'bool',
            'required'=>true,
            'default' => 0,
            'label' => app::get('sysshop')->_('报文导出状态'),
            'comment' => app::get('sysshop')->_('检验检疫回执状态,0-未导出，1-已导出'),
        ),
    ),
    'primary' => 'ord_id',
    'comment' => app::get('sysshop')->_('订单回执信息'),
);


