<?php
class topc_ctl_member_point extends topc_ctl_member {

   public function point()
    {
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = $this->limit;
        $current = $filter['pages'] ? $filter['pages'] : 1;

        $params = array(
            'page_no' => intval($filter['pages']),
            'page_size' => intval($pageSize),
        );

        $data = app::get('topc')->rpcCall('user.pointGet',$params,'buyer');

        //总页数(数据总数除每页数量)
        $pagedata['userpoint'] = $data['datalist']['user'];
        $pagedata['pointdata'] = $data['datalist']['point'];
        if($data['totalnum'] > 0) $total = ceil($data['totalnum']/$pageSize);
        $pagedata['count'] = $data['totalnum'];
        $filter['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topc_ctl_member_point@point',$filter),
            'current'=>$current,
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['action'] = 'topc_ctl_member_point@point';

        $this->action_view = "point.html";
        return $this->output($pagedata);
    }

   public function offpoint()
    {
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = $this->limit;
        $current = $filter['pages'] ? $filter['pages'] : 1;

        $params = array(
            'page_no' => intval($filter['pages']),
            'page_size' => intval($pageSize),
        );

     //   $data = app::get('topc')->rpcCall('user.pointGet',$params,'buyer');

        $filter['user_id'] = pamAccount::getAccountId();
        $objMdlUserPoint = app::get('sysuser')->model('user_points_offline');
        $objMdlUserPointLog = app::get('sysuser')->model('user_pointlog_offline');

		       //分页
        $pageSize = $params['page_size'] ? $params['page_size'] : 40;
        $pageNo = $params['page_no'] ? $params['page_no'] : 1;
        $max = 1000000;
        if($pageSize >= 1 && $pageSize < 500 && $pageNo >=1 && $pageNo < 200 && $pageSize*$pageNo < $max)
        {
            $limit = $pageSize;
            $page = ($pageNo-1)*$limit;
        }

        $data['datalist']['user'] = $objMdlUserPoint->getRow('*',$filter);
        $orderBy = $params['orderBy'];
        if(!$params['orderBy'])
        {
            $orderBy = "modified_time desc";
        }

        $data['totalnum'] = $objMdlUserPointLog->count($filter);
        if(!$params['fields'])
        {
            $params['fields'] = "*";
        }
        $data['datalist']['point'] = $objMdlUserPointLog->getList($params['fields'],$filter,$page,$limit,$orderBy);
     


        //总页数(数据总数除每页数量)
        $pagedata['userpoint'] = $data['datalist']['user'];
        $pagedata['pointdata'] = $data['datalist']['point'];
        if($data['totalnum'] > 0) $total = ceil($data['totalnum']/$pageSize);
        $pagedata['count'] = $data['totalnum'];
        $filter['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topc_ctl_member_point@offpoint',$filter),
            'current'=>$current,
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['action'] = 'topc_ctl_member_point@offpoint';
        
        $pagedata['action'] = 'topc_ctl_member_point@point';

        $this->action_view = "offpoint.html";
        return $this->output($pagedata);
    }









   public function ajaxGetUserPoint()
   {
       $totalPrice = input::get('total_price');
       $totalPostFee = input::get('post_fee');
       $totalPrice = $totalPrice-$totalPostFee;
       $userId = userAuth::id();
       //根据会员id获取积分总值
       $points = app::get('topc')->rpcCall('user.point.get',['user_id'=>$userId]);
       $setting = app::get('topc')->rpcCall('point.setting.get');
       $pagedata['open_point_deduction'] = $setting['open.point.deduction'];
       $pagedata['point_deduction_rate'] = $setting['point.deduction.rate'];
       $pagedata['point_deduction_max'] = floor($setting['point.deduction.max']*$totalPrice*$setting['point.deduction.rate']);
       $pagedata['points'] = $points['point_count'] ? $points['point_count'] : 0;
       //print_r($pagedata);exit;
       return response::json($pagedata);exit;
   }
}

