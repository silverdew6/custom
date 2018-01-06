<?php
class sysuser_data_user_erppoints{


    /**
     * @brief 积分改变，专门处理ERP线下积分
     *
     * @param $params
     *
     * @return
     */
    public function changePoint($params)
    {
        if(!$params['user_id'])
        {
            throw new Exception('会员参数错误');
        }
        if(!$params['modify_point']) 
        {
            throw new Exception('会员积分参数错误');
        }

        $db = app::get('sysuser')->database();
        $db->beginTransaction();
        try{
            $data['user_id'] = $params['user_id'];
            $data['remark'] = $params['modify_remark'] ? $params['modify_remark'] : "平台修改";
            $data['point'] = abs($params['modify_point']);
            $data['modified_time'] = time();
            if($params['modify_point'] >= 0)
            {
                $data['behavior_type'] = "obtain";
                $data['behavior'] = $params['behavior'] ? $params['behavior'] : "平台手动增加积分";
                $result = $this->add($data['user_id'],$data['point']);
            }
            elseif($params['modify_point'] < 0)
            {
                $data['behavior_type'] = "consume";
                $data['behavior'] = $params['behavior'] ? $params['behavior'] : "平台手动扣减积分";
                $result = $this->deduct($data['user_id'],$data['point']);
            }
            if(!$result)
            {
                throw new Exception('会员积分值更改失败');
            }
            $objMdlUserPointsLog = app::get('sysuser')->model('user_pointlog_offline');
            $result = $objMdlUserPointsLog->save($data);
            if(!$result)
            {
                throw new Exception('会员积分值明细记录失败');
            }
            $db->commit();
            return true;
        }catch(\LogicException $e){
            $db->rollback();
            throw new Exception($e->getMessage());
            return false;
        }
    }

    /**
     * @brief 积分增加
     *
     * @param $userId sysuser_user_points_offline
     * @param $data user_points_offline
     *
     * @return
     */
    public function add($userId,$data)
    {
        $db = app::get('sysuser')->database();
        $list = $db->executeQuery('SELECT user_id FROM sysuser_user_points_offline WHERE user_id=?',[$userId])->fetch();
        if($list)
        {
            $result = $db->executeUpdate('UPDATE sysuser_user_points_offline SET point_count = point_count + ? WHERE user_id = ?', [$data, $userId]);
        }
        else
        {
            $result = $db->executeUpdate('insert into sysuser_user_points_offline(user_id,point_count) value (?,?)',[$userId,$data]);
        }
        if(!$result)
        {
            return false;
        }
        return true;
    }

    /**
     * @brief 积分消耗
     *
     * @param $userId
     * @param $data
     *
     * @return
     */
    public function deduct($userId,$data)
    {
        $db = app::get('sysuser')->database();
        $list = $db->executeQuery('SELECT user_id,expired_point,point_count FROM sysuser_user_points_offline WHERE user_id=?',[$userId])->fetch();
        if($list)
        {
            if($list['expired_point'] > 0)
            {
                $expired = ($list['expired_point'] < $data) ? $list['expired_point'] : $data;
                $result = $db->executeUpdate('UPDATE sysuser_user_points_offline SET expired_point = expired_point - ?,point_count = point_count - ? WHERE user_id = ? AND point_count - ? >= 0 AND expired_point - ? >= 0', [$expired,$data, $userId, $data,$expired]);
            }
            else
            {
                $result = $db->executeUpdate('UPDATE sysuser_user_points_offline SET point_count = point_count - ? WHERE user_id = ? AND point_count - ? >= 0', [$data, $userId, $data]);
            }

            if(!$result)
            {
                return false;
            }
            return true;
        }
        else
        {
            throw new Exception('该用户没有积分，不能减积分');
        }
    }




}
