<?php
class topshop_ctl_trade_cancel extends topshop_controller {

    public $limit = 10;

    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('订单取消列表');
        $apiParams['shop_id'] = $this->shopId;
        if( input::get('tid') )
        {
            $apiParams['tid'] = input::get('tid');
        }
        $pageNo =  intval(input::get('pages',1));
        $apiParams['fields'] = '*';
        $apiParams['page_no']  = $pageNo;
        $apiParams['page_size'] = intval($this->limit);

        $data = app::get('topshop')->rpcCall('trade.cancel.list.get', $apiParams);

        if( $count = $data['total'] )
        {
            $pagedata['list'] = $data['list'];
            $pagedata['pagers'] = $this->__pager($data['total'], $pageNo);
            //$pagedata['pagers'] = $this->__pager( $postFilter,$apiParams['page_no'],$count );
        }
        return $this->page('topshop/trade/cancel/list.html', $pagedata);
    }
    /**
     * 分页处理
     *
     * @param $total  总条数
     * @param $current 当前页
     */
    private function __pager($totalNumber, $current,$totalPage = 0)
    {
        $filter = input::get();
        //处理翻页数据
        $current = $current ? $current : 1;
        $current = ($totalPage >0 && $totalPage < $current) ? $totalPage : $current;
        $filter['pages'] = time();
        $filter['page_no'] = $current;
        if( $totalNumber > 0 ) $totalPage = ceil($totalNumber/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_trade_cancel@ajaxSearch',$filter),
            'current'=>$current,
            'total'=>$totalPage,
            'use_app'=>'topshop',
            'token'=>time(),
        );

        return $pagers;
    }
    public function ajaxSearch()
    {
        switch( input::get('progress') )
        {
            case '0':
                $apiParams['refunds_status'] = 'WAIT_CHECK';
                break;
            case '1':
                $apiParams['refunds_status'] = 'WAIT_REFUND';
                break;
            case '2':
                $apiParams['refunds_status'] = 'SUCCESS';
                break;
            case '3':
                $apiParams['refunds_status'] = 'SHOP_CHECK_FAILS';
                break;
        }
        if( input::get('tid') ) {
            $apiParams['tid'] = input::get('tid');
        }

        if( input::get('created_time') ) {
            $times = array_filter(explode('-',input::get('created_time')));
            if($times)
            {
                $apiParams['created_time_start'] = strtotime($times['0']);
                $apiParams['created_time_end'] = strtotime($times['1'])+86400;
            }
        }
		$currentP =  intval(input::get('pages',1));
        $apiParams['shop_id'] = $this->shopId;
        $apiParams['fields'] = '*';
        $apiParams['page_no']  = $currentP;
        $apiParams['page_size'] = intval($this->limit);
        try
        {
            $data = app::get('topshop')->rpcCall('trade.cancel.list.get', $apiParams);
        }
        catch( Exception $e)
        {
        }

        if( $data['total'] )
        {
            $pagedata['list'] = $data['list'];
            $pagedata['pagers'] = $this->__pager($data['total'], $currentP );
        }

        return view::make('topshop/trade/cancel/item.html', $pagedata);
    }

    public function detail()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('订单取消详情');

        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_trade_cancel@index'),'title' => app::get('topshop')->_('订单取消列表')],
            ['title' => app::get('topshop')->_('订单取消详情')],
        );

        $cancelId = input::get('cancel_id');
        try{
            $data = app::get('topc')->rpcCall('trade.cancel.get',['shop_id'=>$this->shopId,'cancel_id'=>$cancelId]);
        }catch(Exception $e){
    	    return $this->page('topshop/trade/cancel/detail.html',$pagedata);
        }

        $pagedata['data'] = $data;

        //获取取消订单的订单数据
        $tid = $data['tid'];
        $params['tid'] = $tid;
        $params['fields'] = "user_id,tid,status,payment,points_fee,ziti_addr,ziti_memo,dlytmpl_id,post_fee,pay_type,payed_fee,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,trade_memo,shop_memo,receiver_name,receiver_mobile,orders.price,orders.num,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,pay_time,consign_time,end_time,shop_id,orders.bn,cancel_reason,orders.refund_fee";
        $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params,'seller');
        $pagedata['trade'] = $tradeInfo;

        $userName = app::get('topshop')->rpcCall('user.get.account.name', ['user_id' => $tradeInfo['user_id']], 'seller');
        $pagedata['userName'] = $userName[$tradeInfo['user_id']];

        if($tradeInfo['dlytmpl_id'] == 0 && $tradeInfo['ziti_addr'])
        {
            $pagedata['ziti'] = "true";
        }

        if( $tradeInfo['status'] == 'WAIT_BUYER_CONFIRM_GOODS' || $tradeInfo['status'] == 'TRADE_FINISHED' )
        {
            $pagedata['logi'] = app::get('topshop')->rpcCall('delivery.get',array('tid'=>$tradeInfo['tid']));
        }

    	return $this->page('topshop/trade/cancel/detail.html',$pagedata);
    }

    //商家审核是否同意取消订单
    public function shopCheckCancel()
    {
        $params['cancel_id'] = input::get('cancel_id');
        $params['shop_id'] = $this->shopId;

        if( input::get('check_result','false') == 'false' )
        {
             $validator = validator::make(
                [ trim(input::get('shop_reject_reason'))],
                [ 'required|max:50'],
                ['拒绝理由必填|拒绝理由最多为50个字符!']
            );
            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();

                foreach( $messages as $error )
                {
                    return $this->splash('error',null,$error[0]);
                }
            }
            $params['status'] = 'reject';
            $params['reason'] = input::get('shop_reject_reason');
        }
        else
        {
            $params['status'] = 'agree';
        }

        try{
            app::get('topshop')->rpcCall('trade.cancel.shop.check',$params);
        }
        catch( LogicException $e ){
            return $this->splash('error',null, $e->getMessage(), true);
        }

        $url = url::action('topshop_ctl_trade_cancel@detail',['cancel_id'=>$params['cancel_id']]);

        return $this->splash('success',$url, '审核提交成功', true);
    }
}

