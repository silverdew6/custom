<?php
class topshop_ctl_trade_list extends topshop_controller{
    public $limit = 10;

    public function index()
    {
        $orderStatusList = array(
            '0' => '全部',
            '1' => '待支付',
            '2' => '待发货',
            '3' => '待收货',
            '4' => '已收货',
            '5' => '已取消',
        );
        if($this->shopInfo['shop_type'] == "self")
        {
            $orderStatusList[6] = "货到付款";
            $orderStatusList[7] = "自提订单";
        }
        $status = (int)input::get('status');
        $status = in_array($status, array_keys($orderStatusList)) ? $status : 0;

        $pagedata['status'] = $orderStatusList;
        $pagedata['filter']['status'] = $status;
        $pagedata['shop_type'] = $this->shopInfo['shop_type'];
        $this->contentHeaderTitle = app::get('topshop')->_('订单列表');
        //var_dump($pagedata);exit;

        return $this->page('topshop/trade/list.html', $pagedata);
    }

    public function search()
    {
        $pagedata['progress'] = array(
                '0' => app::get('topshop')->_('待处理'),
                '1' => app::get('topshop')->_('待回寄'),
                '2' => app::get('topshop')->_('待确认收货'),
                '4' => app::get('topshop')->_('商家已处理'),//换货的时候可以直接在商家处理结束
                '3' => app::get('topshop')->_('商家已驳回'),
                '5' => app::get('topshop')->_('待平台处理'),
                '7' => app::get('topshop')->_('平台已处理'),//退款，退货则需要平台确实退款
                '6' => app::get('topshop')->_('平台已驳回'),
        );

        $tradeStatus = array(
            'WAIT_BUYER_PAY' => '未付款',
            'WAIT_SELLER_SEND_GOODS' => '已付款，请发货',
            'WAIT_BUYER_CONFIRM_GOODS' => '已发货，等待确认',
            'TRADE_FINISHED' => '已完成',
            'TRADE_CLOSED' => '已关闭',
            'TRADE_CLOSED_BY_SYSTEM' => '已关闭',
        );
        $this->contentHeaderTitle = app::get('topshop')->_('订单查询');
        $postFilter = input::get();
        $filter = $this->_checkParams($postFilter);
        $limit = $this->limit;
        $status = $filter['status'];
        if(is_array($filter['status']))
        {
            $status = implode(',',$filter['status']);
        }

        $page = $filter['pages'] ? $filter['pages'] : 1;
        $params = array(
            'status' => $status,
            'tid' => $filter['tid'],
            'create_time_start' =>$filter['created_time_start'],
            'create_time_end' =>$filter['created_time_end'],
            'receiver_mobile' =>$filter['receiver_mobile'],
            'receiver_phone' =>$filter['receiver_phone'],
            'receiver_name' =>$filter['receiver_name'],
            'user_name' =>$filter['user_name'],
            'pay_type' =>$filter['pay_type'],
            'dlytmpl_id' =>$filter['dlytmpl_id'],
            'page_no' => intval($page),
            'page_size' =>intval($limit),
            'order_by' =>'created_time desc',
            'fields' =>'order.spec_nature_info,tid,shop_id,user_id,status,payment,points_fee,total_fee,post_fee,payed_fee,receiver_name,trade_memo,shop_memo,created_time,receiver_mobile,discount_fee,adjust_fee,order.title,order.price,order.num,order.pic_path,order.tid,order.oid,order.item_id,need_invoice,invoice_name,invoice_type,invoice_main,pay_type,cancel_status,tax,sea_region,card_id',
        );

        //显示订单售后状态
        $params['is_aftersale'] = true;
        $params['shop_id'] = $this->shopId;
        $tradeList = app::get('topshop')->rpcCall('trade.get.list',$params,'seller');
        $count = $tradeList['count'];
        $tradeList = $tradeList['list'];

        $usersId = array_column($tradeList, 'user_id');
        if( $usersId )
        {
            $username = app::get('topshop')->rpcCall('user.get.account.name', ['user_id' => implode(',', $usersId)], 'seller');
        }

        foreach((array)$tradeList as $key=>$value)
        {
            $tradeList[$key]['status_depict'] = $tradeStatus[$value['status']];
            $tradeList[$key]['user_login_name'] = $username[$value['user_id']];
        }
        $pagedata['orderlist'] =$tradeList;
        $pagedata['count'] =$count;
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        $pagedata['pagers'] = $this->__pager($postFilter,$page,$count);
        return view::make('topshop/trade/item.html', $pagedata);
    }

    private function __pager($postFilter,$page,$count)
    {
        $postFilter['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_trade_list@search',$postFilter),
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;

    }

    private function _checkParams($filter)
    {
        $statusLUT = array(
            '1' => 'WAIT_BUYER_PAY',
            '2' => 'WAIT_SELLER_SEND_GOODS',
            '3' => 'WAIT_BUYER_CONFIRM_GOODS',
            '4' => 'TRADE_FINISHED',
            '5' => array('TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'),
        );
        foreach($filter as $key=>$value)
        {
            if(!$value) unset($filter[$key]);
            if($key == 'create_time')
            {
                $times = array_filter(explode('-',$value));
                if($times)
                {
                    $filter['created_time_start'] = strtotime($times['0']);
                    $filter['created_time_end'] = strtotime($times['1'])+86400;
                    unset($filter['create_time']);
                }
            }

            if($key=='status' && $value)
            {
                if($value <= 5)
                {
                    $filter['status'] = $statusLUT[$value];
                }
                else
                {
                    if($value == 6)
                    {
                        $filter['pay_type'] = 'offline';
                    }
                    if($value == 7)
                    {
                        $filter['dlytmpl_id'] = '0';
                    }
                    unset($filter['status']);
                }
            }
        }
        return $filter;
    }

    public function ajaxCloseTrade()
    {
        $pagedata['tid'] = input::get('tid');
        $pagedata['reason'] = config::get('tradeCancelReason');

        return view::make('topshop/trade/cancel.html', $pagedata);
    }

    public function closeTrade()
    {
        $reasonSetting = config::get('tradeCancelReason');
        $reasonPost = input::get('cancel_reason');
        if($reasonPost == "other")
        {
            $cancelReason = input::get('other_reason');
            if(!$cancelReason)
            {
                return $this->splash('error',"",'其他取消原因必填',true);
            }
        }
        else
        {
            $cancelReason = $reasonSetting['shopuser'][$reasonPost];
        }

        $params['tid'] = input::get('tid');
        $params['shop_id'] = $this->shopId;
        $params['cancel_reason'] = $cancelReason;
        $url = url::action('topshop_ctl_trade_list@index');
        try
        {
            app::get('topshop')->rpcCall('trade.cancel',$params);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',"",$msg,true);
        }
        $msg = '取消成功';
        return $this->splash('succecc',$url,$msg,true);
    }

    /**
     * 修改订单价格页面
     * @return
     */
    public function modifyPrice()
    {
        $tids = input::get('tid');
        $params['tid'] = $tids;
        $params['fields'] = "total_fee,post_fee,payment,points_fee,tid,receiver_state,receiver_city,receiver_district,receiver_address,orders.pic_path,orders.title,orders.item_id,orders.spec_nature_info,orders.price,orders.num,orders.total_fee,orders.discount_fee,orders.part_mjz_discount,orders.oid,orders.adjust_fee";
        $pagedata['trade_detail'] = app::get('topshop')->rpcCall('trade.get',$params,'seller');
        return view::make('topshop/trade/modify_price.html', $pagedata);
    }

    /**
     * 修改订单价格
     * @return
     */
    public function updatePrice()
    {
        $url = url::action('topshop_ctl_trade_list@index');
        $params = input::get('trade');
        $params['order'] = json_encode($params['order']);
        $handle_user = $_SESSION["account"]["shop"]; //操作人员信息
        if(isset($handle_user) && !empty($handle_user)){
        	$params["oauth_json"]= json_encode(array("account_id"=> $handle_user["id"],"auth_type"=>"shop","account_name"=> $handle_user["account"]));
        }
        //return $this->splash('error',"",print_r($params,false),true);
        try
        {
            app::get('topshop')->rpcCall('trade.update.price',$params);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',"",$msg,true);
        }
        $msg = '修改成功';
        return $this->splash('succecc',$url,$msg,true);
    }

    //收钱并收货页面
    public function ajaxFinishTrade()
    {
        $params['tid'] = input::get('tid');
        $params['fields'] = "user_id,tid,shop_id,status,payment,points_fee,post_fee,pay_type,receiver_state,receiver_city,receiver_district,receiver_address,receiver_name,receiver_mobile";
        $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params,'seller');
        $pagedata['tradeInfo'] = $tradeInfo;
        $pagedata['logi'] = app::get('topc')->rpcCall('delivery.get',array('tid'=>$params['tid']));
        return view::make('topshop/trade/finish.html', $pagedata);
    }


    //收钱并收货
    public function finishTrade()
    {
        $params = input::get('trade');
        $params['seller_id'] = $this->sellerId;
        $params['seller_name'] = $this->sellerName;
        $params['memo'] = "商家处理货到付款订单";
        try
        {
            app::get('topshop')->rpcCall('trade.moneyAndGoods.receipt',$params);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',"",$msg,true);
        }
        $msg = "处理完成";
        $url = url::action('topshop_ctl_trade_list@index');
        return $this->splash('succecc',$url,$msg,true);
    }
}

