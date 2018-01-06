<?php
class sysuser_mdl_user_addrs extends dbeav_model
{
	public $addrLimit = 20;

	/**
	*会员收货地址保存
	* @param $data
	* @return true or false
	*/
	public function saveAddrs($data)
	{
		$filter = array('user_id' => $data['user_id']);
		if($data['def_addr'])
		{
			$arrUpdate = array('def_addr'=>0);
			$this->update($arrUpdate, $filter);
		}

		$cnt = $this->count($filter);

		//$postData['card_id']  验证身份证 2016/3/20   身份证可选
		$cardID = isset($data['card_id']) ? trim($data['card_id']) :"";
		
       if($cardID && !$this->check_identity($data['card_id']))
        {
			throw new \LogicException(app::get('sysuser')->_('身份证格式不正确'));
			return false;
        }

		if((!$data['addr_id'] && $cnt < $this->addrLimit) || $data['addr_id'])
		{

			return $this->save($data);
		}
		else
		{
			throw new \LogicException(app::get('sysuser')->_('最多只能添加20个地址，请先删除一条地址之后再添加'));
			return false;
		}

	}


	/**2016/3/20 lcd
 * 验证18位身份证（计算方式在百度百科有）
 * @param  string $id 身份证
 * return boolean
 */

function check_identity($id='')
{
    $set = array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
    $ver = array('1','0','x','9','8','7','6','5','4','3','2');
    $arr = str_split($id);
    $sum = 0;
    for ($i = 0; $i < 17; $i++)
    {
        if (!is_numeric($arr[$i]))
        {
            return false;
        }
        $sum += $arr[$i] * $set[$i];
    }
    $mod = $sum % 11;
    if (strcasecmp($ver[$mod],$arr[17]) != 0)
    {
        return false;
    }
    return true;
}
}
