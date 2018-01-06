<?php
return  array(
    'columns'=>array(
        'info_id'=>array(
            'type'=>'number',
            //'pkey' => true,
            'autoincrement' => true,
            //'extra' => 'auto_increment',
            'required' => true,
            'order' => 1,
            'label' => app::get('sysshop')->_('企业信息id'),
            'comment' => app::get('sysshop')->_('企业信息id 自增'),
        ),
        'seller_id'=>array(
            'type'=>'table:account@sysshop',
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>true,
            'label' => app::get('sysshop')->_('商家账号'),
            'comment' => app::get('sysshop')->_('提交申请的账号'),
            'order' => 6,
        ),
        'shop_id'=>array(
            'type'=>'table:shop',
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>true,
            'label' => app::get('sysshop')->_('商家店铺id'),
            'comment' => app::get('sysshop')->_('商家店铺id'),
            'order' => 6,
        ),
        'company_name'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('公司名称'),
            'comment' => app::get('sysshop')->_('公司名称'),
            'order' => 10,
        ),
        'license_num'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('执照注册号'),
            'comment' => app::get('sysshop')->_('营业执照注册号'),
        ),
        'license_img'=>array(
            //'type'=>'varchar(32)',
            'type' => 'string',
            'label' => app::get('sysshop')->_('营业执照副本'),
            'comment' => app::get('sysshop')->_('营业执照副本-电子版'),
        ),
        'representative'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 20,
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>false,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('法定代表人姓名'),
            'comment' => app::get('sysshop')->_('法定代表人姓名 '),
        ),
        'corporate_identity'=>array(
            //'type'=>'char(18)',
            'type' => 'string',
            'length' => 18,
            'fixed' => true,

            'required'=>false,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('法人身份证号'),
            'comment' => app::get('sysshop')->_('法人身份证号'),
        ),
        'is_mainland'=>array(
                //'type'=>'char(18)',
                'type' => 'bool',
                'required'=>true,
                'default' => 1,
                'label' => app::get('sysshop')->_('法人身份'),
                'comment' => app::get('sysshop')->_('法人身份,1代表中国大陆居民，2代表非中国大陆居民'),
        ),
        'passport_number'=>array(
                //'type'=>'char(18)',
                'type' => 'string',
                'required'=>false,
                'label' => app::get('sysshop')->_('法人护照号'),
                'comment' => app::get('sysshop')->_('法人护照号'),
        ),
        'corporate_identity_img'=>array(
            //'type'=>'varchar(32)',
            'type' => 'string',

            'label' => app::get('sysshop')->_('法人身份证号电子版'),
            'comment' => app::get('sysshop')->_('法人身份证号电子版'),
        ),
        'license_area'=>array(
            'type'=>'region',
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('营业执照所在地'),
            'comment' => app::get('sysshop')->_('营业执照所在地'),
        ),
        'license_addr'=>array(
            'type'=>'text',
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('营业执照详细地址'),
            'comment' => app::get('sysshop')->_('营业执照详细地址 '),
        ),
        'establish_date'=>array(
            'type'=>'time',
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>false,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('成立日期'),
            'comment' => app::get('sysshop')->_('成立日期'),
        ),
        'license_indate'=>array(
            'type'=>'time',
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>false,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('营业执照有效期'),
            'comment' => app::get('sysshop')->_('营业执照有效期'),
        ),
        'enroll_capital'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 20,

            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>false,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('注册资本'),
            'comment' => app::get('sysshop')->_('注册资本'),
        ),
        'scope'=>array(
            //'type'=>'varchar(200)',
            'type' => 'string',
            'length' => 200,

            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>false,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('经营范围'),
            'comment' => app::get('sysshop')->_('经营范围 '),
        ),
        'shop_url'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,

            'in_list'=>true,
            'default_in_list'=>false,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('公司官网'),
            'comment' => app::get('sysshop')->_('公司官网'),
        ),
        'company_area'=>array(
            'type'=>'region',
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('公司所在地'),
            'comment' => app::get('sysshop')->_('公司所在地'),
        ),
        'company_addr'=>array(
            'type'=>'text',
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('公司地址'),
            'comment' => app::get('sysshop')->_('公司地址'),
        ),
        'company_phone'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 20,

            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('公司电话'),
            'comment' => app::get('sysshop')->_('公司电话'),
        ),
        'company_contacts'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 20,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('公司联系人'),
            'comment' => app::get('sysshop')->_('公司联系人'),
        ),
        'company_cmobile'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('公司联系人手机号'),
            'comment' => app::get('sysshop')->_('公司联系人手机号'),
        ),
        'tissue_code'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('组织机构代码'),
            'comment' => app::get('sysshop')->_('组织机构代码'),
        ),
        'tissue_code_img'=>array(
            //'type'=>'varchar(32)',
            'type' => 'string',
            'label' => app::get('sysshop')->_('组织机构代码副本'),
            'comment' => app::get('sysshop')->_('组织机构代码副本-电子版'),
        ),
        'tax_code'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 20,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('税务登记号'),
            'comment' => app::get('sysshop')->_('税务登记号'),
        ),
        'tax_code_img'=>array(
            //'type'=>'varchar(32)',
            'type' => 'string',
            'label' => app::get('sysshop')->_('税务登记号副本'),
            'comment' => app::get('sysshop')->_('税务登记号副本-电子版'),
        ),
        'bank_user_name'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('银行开户公司名'),
            'comment' => app::get('sysshop')->_('银行开户公司名'),
        ),
        'bank_name'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('开户银行'),
            'comment' => app::get('sysshop')->_('开户银行'),
        ),
        'cnaps_code'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('CNAPS CODE'),
            'comment' => app::get('sysshop')->_('支行联行号'),
        ),
        'bankID'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('银行账号'),
            'comment' => app::get('sysshop')->_('银行账号'),
        ),
        'bank_area'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('开户银行所在地'),
            'comment' => app::get('sysshop')->_('开户银行所在地'),
        ),
		 'sea_region'=>array(
            'type' => 'string',
            'length' => 512,      
            'comment' => app::get('sysshop')->_('区域(保税仓)'),
        ),
			'tax'=>array(
            'type' => 'string',
            'length' => 255,      
            'comment' => app::get('sysshop')->_('业务模式(完税，保税，直邮)'),
        ),
			'ebcCode'=>array(
            'type' => 'string',
            'length' => 10,      
            'comment' => app::get('sysshop')->_('电商海关备案编码'),
        ),
		 'ebcName'=>array(
         'type' => 'string',
         'length' => 100,      
         'comment' => app::get('sysshop')->_('电商海关备案名称'),
        ),
		 'agentCode'=>array(
         'type' => 'string',
         'length' => 10,      
         'comment' => app::get('sysshop')->_('申报的行邮税号'),
        ),
		 'agentName'=>array(
         'type' => 'string',
         'length' => 100,      
         'comment' => app::get('sysshop')->_('申报企业海关备案名称'),
        ),
		 'shortName'=>array(
         'type' => 'string',
         'length' => 4,      
         'comment' => app::get('sysshop')->_('海关英文简写(四位)'),
        )
    ),
    'primary' => 'info_id',
    'index' => array(
        'ind_seller_id' => ['columns' => ['seller_id']],
        'ind_shop_id' => ['columns' => ['shop_id']],
    ),
    'comment' => app::get('sysshop')->_('企业信息表'),

);


