<?php
return  array(
    'columns'=>array(
        'other_id'=>array(
            'type'=>'number',
            //'pkey' => true,
            'autoincrement' => true,
            //'extra' => 'auto_increment',
            'required' => true,
            'order' => 1,
            'label' => app::get('sysshop')->_('信息id'),
            'comment' => app::get('sysshop')->_('信息id 自增'),
        ),
        'ebpCode'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 20,
            'in_list'=>true,
            'default_in_list'=>true,
            'required'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('电商平台代码'),
            'comment' => app::get('sysshop')->_('电商平台代码'),
        ),
        'ebpName'=>array(
            //'type'=>'varchar(50)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'in_list'=>true,
            'default_in_list'=>true,
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('电商平台名称'),
            'comment' => app::get('sysshop')->_('电商平台名称'),
            'order' => 10,
        ),
    ),
    'primary' => 'other_id',
    'comment' => app::get('sysshop')->_('报关相关信息'),
);


