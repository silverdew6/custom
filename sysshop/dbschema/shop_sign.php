<?php
return  array(
    'columns'=>array(
        'sign_id'=>array(
            'type'=>'number',
            //'pkey' => true,
            'autoincrement' => true,
            //'extra' => 'auto_increment',
            'required' => true,
            'order' => 1,
            'label' => app::get('sysshop')->_('承运企业信息id'),
            'comment' => app::get('sysshop')->_('承运企业信息id 自增'),
        ),
        'signCompany'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 20,
            'in_list'=>true,
            'default_in_list'=>true,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('承运企业代码'),
            'comment' => app::get('sysshop')->_('承运企业代码'),
        ),
        'signCompanyName'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('承运企业名称'),
            'comment' => app::get('sysshop')->_('承运企业名称'),
            'order' => 10,
        ),
    ),
    'primary' => 'sign_id',
    'comment' => app::get('sysshop')->_('承运企业信息'),
);


