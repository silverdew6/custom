<?php
return  array(
    'columns'=>array(
        'rec_id'=>array(
            'type'=>'number',
            //'pkey' => true,
            'autoincrement' => true,
            //'extra' => 'auto_increment',
            'required' => true,
            'order' => 1,
            'label' => app::get('sysshop')->_('回执id'),
            'comment' => app::get('sysshop')->_('回执id 自增'),
        ),
        'tid' => array(
            'type'=>'table:trade@systrade',
            'label' => app::get('sysshop')->_('订单号'),
            'required' => true,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'shop_id'=>array(
            'type'=>'table:shop@sysshop',
            'label' => app::get('sysshop')->_('商铺号'),
            'required' => true,
            'in_list' => true,
            'default_in_list'=>true,
        ),

        /*'payTransactionId'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'in_list'=>true,           //在后台显示时会用到
            'default_in_list'=>true,    //在后台显示时会用到
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('支付流水号'),
            'comment' => app::get('sysshop')->_('支付流水号'),
        ),
        'logisticsNo'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'in_list'=>true,           //在后台显示时会用到
            'default_in_list'=>true,    //在后台显示时会用到
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('运单号'),
            'comment' => app::get('sysshop')->_('运单号'),
        ),
        'copNo'=>array(
            //'type'=>'varchar(20)',
            'type' => 'string',
            'length' => 50,
            'required'=>true,
            'in_list'=>true,           //在后台显示时会用到
            'default_in_list'=>true,    //在后台显示时会用到
            'filtertype' => false,
            'filterdefault' => 'true',
            'label' => app::get('sysshop')->_('企业内部编码'),
            'comment' => app::get('sysshop')->_('企业内部编码'),
        ),*/
        'type'=>array(
            //'type'=>'char(18)',
            'type' => 'bool',
            'required'=>true,
            'label' => app::get('sysshop')->_('报文类型'),
            'comment' => app::get('sysshop')->_('报文类型,1-电子，2-运单，3-全,默认为0'),
        ),
        'status'=>array(
            //'type'=>'char(18)',
            'type' => 'string',
            'length' => 20,
            'required'=>true,
            'label' => app::get('sysshop')->_('回执状态'),
            'comment' => app::get('sysshop')->_('回执状态,为1,2,3,4时为通过'),
        ),
    ),
    'primary' => 'rec_id',
    'comment' => app::get('sysshop')->_('回执信息'),
);



