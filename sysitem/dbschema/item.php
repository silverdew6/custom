<?php
return  array(
    'columns'=> array(
        'item_id' => array(
            'type' => 'number',
            //'pkey' => true,
            'autoincrement' => true,
            'required' => true,
            'comment' => app::get('sysitem')->_('item_id'),
        ),
        'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'label' => app::get('sysitem')->_('所属店铺'),
            'comment' => app::get('sysitem')->_('店铺id'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 11,
            //'orderby' => true,
        ),
        'cat_id' => array(
            'type' => 'table:cat@syscategory',
            'required' => true,
            'label' => app::get('sysitem')->_('商品类目名称'),
            'comment' => app::get('sysitem')->_('商品类目ID'),
            'finder_filter_name'=>'cat_name',//后台高级筛使用
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'order' => 13,
        ),
        'brand_id' => array(
            'type' => 'table:brand@syscategory',
            'required' => true,
            'finder_filter_name'=>'brand_name',
            'label' => app::get('sysitem')->_('商品品牌'),
            'comment' => app::get('sysitem')->_('品牌'),
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'order' => 14,
        ),
        'shop_cat_id' => array(
            //'type' => 'varchar(255)',
            'type' => 'string',
            'required' => true,
            'label' => app::get('sysitem')->_('商家自定义分类id'),
            'comment' => app::get('sysitem')->_('商家自定义分类id'),
        ),
        'title' => array(
            //'type' => 'varchar(60)',
            'type' => 'string',
            'length' => 90,
            'required' => true,
            'default' => '',
            'label' => app::get('sysitem')->_('商品标题'),
            'comment' => app::get('sysitem')->_('商品标题'),
            'is_title' => true,
            'searchtype' => 'has',
            'filtertype' => 'custom',
            'filterdefault' => true,
            'order' => 12,
        ),
        'sub_title' => array(
            //'type' => 'varchar(200)',
            'type' => 'string',
            'length' => 200,
            'label' => app::get('sysitem')->_('商品子标题'),
            'comment' => app::get('sysitem')->_('商品子标题'),
            'filtertype' => 'normal',
            'filterdefault' => true,
        ),
        'bn' => array(
            //'type'=>'varchar(45)',
            'type' => 'string',
            'length' => 45,
            'required' => true,
            'label' => app::get('sysitem')->_('商品编号'),
            'comment' => app::get('sysitem')->_('bn'),
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'yes',
            'filterdefault' => true,
            'order' => 10,
            'orderby' => true,
        ),
        'price' => array(
            'type' => 'money',
            'required' => true,
            'label' => app::get('sysitem')->_('商品价格'),
            'comment' => app::get('sysitem')->_('商品价格'),
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'number',
            'filterdefault' => true,
            'order' => 15,
        ),
        'cost_price' => array(
            'type' => 'money',
            'label' => app::get('sysitem')->_('商品成本价格'),
            'comment' => app::get('sysitem')->_('商品成本价格'),
            'in_list' => true,
            'default_in_list' => false,
            'filtertype' => 'number',
            'filterdefault' => true,
            'order' => 16,
        ),
        'mkt_price' => array(
            'type' => 'money',
            'label' => app::get('sysitem')->_('商品市场价格'),
            'comment' => app::get('sysitem')->_('商品市场价格'),
            'in_list' => true,
            'default_in_list' => false,
            'filtertype' => 'number',
            'filterdefault' => true,
            'order' => 16,
        ),
        'weight' => array(
            //'type' => 'decimal(20,3)',
            'type' => 'decimal',
            'precision' => 20,
            'scale' => 3,

            'required' => true,
            'default' => 0,
            'label' => app::get('sysitem')->_('商品重量'),
            'comment' => app::get('sysitem')->_('商品重量'),
            'in_list' => true,
            'default_in_list' => false,
        ),
        'image_default_id' => array(
            //'type' => 'varchar(32)',
            'type' => 'string',
            // 'required' => true,
            'comment' => app::get('sysitem')->_('商品默认图'),
        ),
        'list_image' => array(
            'type' => 'text',
            // 'required' => true,
            'comment' => app::get('sysitem')->_('商品图片'),
        ),
        // 'approve_status' => array(
        //     'type' => array(
        //         'onsale' => app::get('sysitem')->_('出售中'),
        //         'instock' => app::get('sysitem')->_('库中'),
        //     ),
        //     'required' => true,
        //     'default' => 'instock',
        //     'label' => app::get('sysitem')->_('商品状态'),
        //     'comment' => app::get('sysitem')->_('商品状态'),
        //     'in_list' => true,
        //     'default_in_list' => false,
        //     'filtertype' => 'yes',
        //     'filterdefault' => true,
        //     'order'=>19,
        // ),
        // 'list_time' => array(
        //     'type' => 'time',
        //     'label' => app::get('sysitem')->_('上架时间'),
        //     'comment' => app::get('sysitem')->_('上架时间'),
        //     'in_list' => true,
        //     'default_in_list' => false,
        //     'order'=>20,
        // ),
        // 'delist_time' => array(
        //     'type' => 'time',
        //     'label' => app::get('sysitem')->_('下架时间'),
        //     'comment' => app::get('sysitem')->_('下架时间'),
        //     'in_list' => true,
        //     'default_in_list' => false,
        //     'order'=>21,
        // ),
       // 'store' => array(
       //     'type' => 'number',
       //     'required' => true,
       //     'default' => 0,
       //     'label' => app::get('sysitem')->_('商品数量'),
       //     'comment' => app::get('sysitem')->_('商品数量'),
       //     'in_list' => true,
       //     'default_in_list' => true,
       //     'filtertype' => 'number',
       //     'filterdefault' => true,
       //     'order' => 18,
       // ),
       // 'freez' => array(
       //     'type'=>'number',
       //     'label' => app::get('sysitem')->_('预扣库存'),
       //     'comment' => app::get('sysitem')->_('预扣库存'),
       //     'in_list' => true,
       //     'default_in_list' => true,
       // ),
        'order_sort' => array(
            'type' => 'number',
            'default' => 0,
            'label' => app::get('sysitem')->_('排序'),
            'comment' => app::get('sysitem')->_('排序'),
            'in_list' => true,
            'default_in_list' => false,
            'order'=>22,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => true,
            'label' => app::get('sysitem')->_('更新时间'),
            'comment' => app::get('sysitem')->_('商品最后更新时间'),
            'in_list' => true,
            'default_in_list' => false,
            'order' => 17,
        ),
        'has_discount' => array(
            'type' => 'bool',
            'default' => 0,
            'label' => app::get('sysitem')->_('是否支持会员打折'),
            'comment' => app::get('sysitem')->_('是否支持会员打折'),
            'in_list' => true,
            'default_in_list' => false,
        ),
        'is_virtual' => array(
            'type' => 'bool',
            'default' => 0,
            'label' => app::get('sysitem')->_('是否是虚拟商品'),
            'comment' => app::get('sysitem')->_('是否是虚拟商品'),
            'in_list' => true,
            'default_in_list' => false,
        ),
        'is_timing' => array(
            'type' => 'bool',
            'default' => 0,
            'label' => app::get('sysitem')->_('是否定时上下架'),
            'comment' => app::get('sysitem')->_('是否定时上下架'),
            'in_list' => true,
            'default_in_list' => false,
        ),
        'violation' => array(
            'type' => 'bool',
            'default' => 0,
            'label' => app::get('sysitem')->_('是否违规'),
            'comment' => app::get('sysitem')->_('是否违规'),
            'in_list' => true,
            'default_in_list' => false,
        ),
        'is_selfshop' => array(
            'type' => 'bool',
            'default' => 0,
            'label' => app::get('sysitem')->_('是否自营'),
            'comment' => app::get('sysitem')->_('是否自营'),
            'in_list' => true,
            'default_in_list' => false,
        ),
        'nospec' => array(
            'type' => 'bool',
            'default' => 0,
            'required' => true,
            'label' => app::get('sysitem')->_('单品/多规格'),
            'filtertype' => 'yes',
            'filterdefault' => true,
            'in_list' => true,
        ),
        'spec_desc' => array(
            'type' => 'serialize',
            'label' => app::get('sysitem')->_('销售属性序列化'),
            'width' => 110,
            'hidden' => true,
            'editable' => false,
            'comment' => app::get('sysitem')->_('销售属性序列化'),
        ),
        'props_name' => array(
            'type' => 'text',
            'comment' => app::get('sysitem')->_('商品属性'),
        ),
        'params' => array(
            'type' => 'serialize',
            'editable' => false,
            'comment' => app::get('sysitem')->_('商品参数序列化'),
        ),
        'sub_stock' => array(
            'type' => 'bool',
            'default' => 1, //默认下单减库存
            'label' => app::get('sysitem')->_('是否支持下单减库存'),
            'comment' => app::get('sysitem')->_('是否支持下单减库存'),
            'in_list' => true,
            'default_in_list' => false,
        ),
        'outer_id' => array(
            //'type' => 'varchar(32)',
            'type' => 'string',
            'length' => 32,
            'comment' => app::get('sysitem')->_('商家外部编码'),
        ),
        'is_offline' => array(
            'type' =>'bool',
            'default' => 0,
            'comment' => app::get('sysitem')->_('是否是线下商品'),
        ),
        'barcode' => array(
            //'type' => 'varchar(128)',
            'type' => 'string',
            'length' => 128,
            'label' => app::get('sysitem')->_('条形码'),
            'comment' => app::get('sysitem')->_('商品级别的条形码'),
            'in_list' => true,
            'default_in_list' => false,
        ),
        // 'sold_quantity' => array(
        //     'type' => 'number',
        //     'label' => app::get('sysitem')->_('销量'),
        //     'comment' => app::get('sysitem')->_('商品销量'),
        //     'in_list' => true,
        //     'default_in_list' => false,
        // ),
        'disabled' => array(
            'type' => 'bool',
            'default' => 0,
            'required' => true,
            'comment' => app::get('sysitem')->_('disabled'),
        ),
        'use_platform' => array(
            'type' => array(
                0 => app::get('sysitem')->_('全部'),
                1 => app::get('sysitem')->_('pc端'),
                2 => app::get('sysitem')->_('wap端'),
            ),
            'default' => 0,
            'comment' => app::get('sysitem')->_('使用平台'),
        ),
		'sea_region'=>array(
         'type'=>'number',
          'length' => 8,
          'comment' => app::get('sysshop')->_('海关业务区域'),
        ),
		    'tax' => array(
            'type' => array(
                1 => app::get('sysitem')->_('完税'),
                2 => app::get('sysitem')->_('保税'),
                3 => app::get('sysitem')->_('直邮'),
            ),
            'default' =>1,
            'comment' => app::get('sysitem')->_('商业模式'),
        ),
		'area_id'=>array(
         'type'=>'number',
           // 'required' => true,
          'length' => 8,
          'comment' => app::get('sysshop')->_('原产地'),
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
        'postyun_code'=>array(
          'type' => 'string',
          'length' => 20,
          'comment' => app::get('sysitem')->_('行邮税号'),
        ),
		 'postyun_rate'=>array(
         'type' => 'decimal',
            'precision' => 19,
            'scale' => 5,
          'comment' => app::get('sysitem')->_('行邮税率'),
        ),
		'gno'=>array(
         'type'=>'string',
           // 'required' => true,
          'length' => 18,
          'comment' => app::get('sysshop')->_('海关商品备案号'),
        ),
		'ciqGno'=>array(
         'type'=>'string',
           // 'required' => true,
          'length' => 18,
          'comment' => app::get('sysshop')->_('检疫商品备案号'),
        ),
		'ciqGmodel'=>array(
         'type'=>'string',
           // 'required' => true,
          'length' => 250,
          'comment' => app::get('sysshop')->_('检疫规格型号'),
        ),
		'gcode'=>array(
          'type'=>'string',
          'default' => '',
          'length' => 15,
          'comment' => app::get('sysshop')->_('海关商品编码'),
        ),
		'gname'=>array(
         'type'=>'string',
          'length' => 250,
          'comment' => app::get('sysshop')->_('海关商品名称'),
        ),
		'gmodel'=>array(
         'type'=>'string',
          'length' => 250,
          'comment' => app::get('sysshop')->_('海关规格型号'),
        ),
		'unit'=>array(
         'type'=>'string',
          'length' => 3,
          'comment' => app::get('sysshop')->_('计量单位'),
        ),
        'grossWeight'=>array(
            'type' => 'decimal',
            'precision' => 19,
            'scale' => 5,
            'comment' => app::get('sysitem')->_('毛重'),
        ),
        'netWeight'=>array(
            'type' => 'decimal',
            'precision' => 19,
            'scale' => 5,
            'comment' => app::get('sysitem')->_('净重'),
        ),
        'unit1' => array(
            'type' => 'string',
            'label' => app::get('sysitem')->_('法定单位'),
            'comment' => app::get('sysitem')->_('法定单位'),
            'in_list' => true,
            'default_in_list' => false,
            'order'=>22,
        ),
        'unit2' => array(
            'type' => 'string',
            'label' => app::get('sysitem')->_('第二计量单位'),
            'comment' => app::get('sysitem')->_('第二计量单位'),
            'in_list' => true,
            'default_in_list' => false,
            'order'=>22,
        ),
        'itemRecordNo' => array(
            'type'=>'string',
            'length' => 50,
            'comment' => app::get('sysshop')->_('商品条码'),
        ),
		/*'gcode'=>array(
         'type'=>'string',
          'length' => 10,
          'comment' => app::get('sysshop')->_('海关商品编码'),
        ),*/
    ),
    'primary' => 'item_id',
    'index' => array (
        'ind_title' => ['columns' => ['title']],
        'ind_bn' => ['columns' => ['bn']],
    ),
    'comment' => app::get('sysitem')->_('商品表'),
);

