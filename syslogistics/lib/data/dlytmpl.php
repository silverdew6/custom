<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2014 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class syslogistics_data_dlytmpl {

    public function __construct($app)
    {
        $this->app = $app;
        $this->objMdldlytmpl = app::get('syslogistics')->model('dlytmpl');
    }

    /**
     * @brief 存储店铺快递运费模板数据
     *
     * @param array $data 店铺快递运模板费数据
     *
     * @return bool
     */
    public function addDlyTmpl($data,$shopId)
    {
        $this->__check($data, $shopId);
        $saveData = $this->__preData($data,$shopId);
        if( !$this->objMdldlytmpl->insert($saveData) )
        {
            $msg = app::get('syslogistics')->_('保存失败');
            throw new \LogicException($msg);
        }
        return true;
    }

    /**
     * @brief 更新快递运费模板数据
     *
     * @param array $data
     *
     * @return bool
     */
    public function updateDlyTmpl($data,$shopId)
    {
        $this->__check($data, $shopId);
        $saveData = $this->__preData($data,$shopId);
        $filter['template_id'] = $saveData['template_id'];
        $filter['shop_id'] = $shopId;
        if( !$this->objMdldlytmpl->update($saveData,$filter) )
        {
            $msg = app::get('syslogistics')->_('保存失败');
            throw new \LogicException($msg);
        }
        return true;
    }

    /**
     * @brief 判断快递模板名称是否存在
     *
     * @param string $tmplname
     *
     * @return bool|int  存在返回template_id | false 不存在
     */
    public function isExistsName($tmplname,$shopId)
    {
        $data = $this->objMdldlytmpl->getRow('template_id',array('name'=>$tmplname,'shop_id'=>$shopId));
        return $data ? $data['template_id'] : false;
    }

    private function __preData($data,$shopId)
    {
        if( $data['template_id'] )
        {
            $return['template_id'] = intval($data['template_id']);
        }
        $return['shop_id'] = $shopId;
        $return['name'] = trim($data['name']);
        $return['order_sort'] = intval($data['order_sort']);
        $return['corp_id'] = $data['corp_id'];
        if( $data['protect'] )
        {
            $return['protect'] = $data['protect'] ;
            $return['protect_rate'] = $data['protect_rate'];
            $return['minprice'] = $data['minprice'];
        }
        else
        {
            $return['protect'] = 0;
            $return['protect_rate'] = 0;
            $return['minprice'] = 0;
        }
        $return['valuation'] = $data['valuation'] ? $data['valuation'] : '1';
        $return['status'] = $data['status'] == 'off' ? 'off' : 'on';
        $return['create_time'] = $data['create_time'] ? $data['create_time'] : time();
        $return['modifie_time'] = time();
        $return['fee_conf'] = serialize($data['fee_conf']);
        return $return;
    }

    private function __check($data, $shopId)
    {
        if( empty($data['name']) || mb_strlen(trim($data['name']),'utf8') > 20 )
        {
            $msg = app::get('syslogistics')->_('运费模板名称不能为空，且不可以超过20个字');
            throw new \LogicException($msg);
        }

        //修改的该模板ID是否存在
        $template_id = $this->isExistsName($data['name'], $shopId);
        if( $template_id && (!$data['template_id'] || $data['template_id'] != $template_id) )
        {
            $msg = app::get('syslogistics')->_('该运费模板名称已存在');
            throw new \LogicException($msg);
        }

        if( !is_numeric($data['order_sort']) )
        {
            $msg = app::get('syslogistics')->_('排序只能为数字');
            throw new \LogicException($msg);
        }

        $areaArr = array();
        foreach( $data['fee_conf'] as $key=>$row )
        {
            if( !$row['area'] ) continue;
            $area = explode(',', $row['area']);
            foreach( $area as $areaId )
            {
                $areaName = area::getAreaNameById($areaId);
                if( !$areaName )
                {
                    $msg = app::get('syslogistics')->_("参数错误，选择的地区不存在");
                    throw new \LogicException($msg);
                }

                if( in_array($areaId, $areaArr) )
                {
                    $msg = app::get('syslogistics')->_("地区({$areaName})配置重复");
                    throw new \LogicException($msg);
                }
                else
                {
                    $areaArr[] = $areaId;
                }
            }
        }

        return true;
    }

    /**
     * @brief 获取运费模板数据
     *
     * @param string $fields
     * @param array $filter
     *
     * @return array
     */
    public function fetchDlyTmpl($fields='*', $filter)
    {

		if($filter['shop_cart']){
		$filter['select_tax']=$filter['shop_cart'][0]['tax'];  //手机端，默认第一个店铺的业务模式，如果是多个店铺，那么肯定是完税的，后面的就不处理了
		}
		if($filter['select_tax']==2||$filter['select_tax']==3){  //不是完税的情况
				if($filter['port']=='wap'){
					//处理手机端
		$filteres['shop_id']=$filter['shop_cart'][0]['shop_id'];//保税和直邮只能在一个店铺里面买商品，所以默认下标为0；
		$filteres['region_id']=$filter['shop_cart'][0]['sea_region'];  //区域
        $objMdlsyslogistics = app::get('syslogistics')->model('region');
		$fee_conf= $objMdlsyslogistics->getRow('fee_conf',$filteres);
		$filterer['template_id']= unserialize($fee_conf['fee_conf']);
		$filterer['status']= $filter['status'];
        $tmpl = $this->objMdldlytmpl->getList($fields, $filterer);//保税和直邮的情况
			     }else{
					 //电脑端
		$filteres['shop_id']=$filter['shop_id'][0];//保税和直邮只能在一个店铺里面买商品，所以默认下标为0；
		$filteres['region_id']=$filter['select_region'];  //区域
        $objMdlsyslogistics = app::get('syslogistics')->model('region');
		$fee_conf= $objMdlsyslogistics->getRow('fee_conf',$filteres);
		$filterer['template_id']= unserialize($fee_conf['fee_conf']);
		$filterer['status']= $filter['status'];
        $tmpl = $this->objMdldlytmpl->getList($fields, $filterer);//保税和直邮的情况
				 }
		}else{
			if($filter['port']){  //注销手机的传值
			unset($filter['port']);
			unset($filter['shop_cart']);
			}
       $tmpl = $this->objMdldlytmpl->getList($fields, $filter);//完税的情况，所有区域可以选择
		}
  
        if($tmpl)
        {
            if( isset($tmpl[0]['fee_conf']))
            {
                foreach($tmpl as $key=>$val)
                {
                    $tmpl[$key]['fee_conf'] = unserialize($val['fee_conf']);
                }
            }
            $data['data'] = $tmpl;
            $data['total_found'] = $this->objMdldlytmpl->count($filter);
            return $data;
        }
        else
        {
            return false;
        }

    }

    /**
     * @brief 删除对应ID的快递运费模板
     *
     * @param int|array  $templateId
     *
     * @return boole
     */
    public function remove($filter)
    {
        return $this->objMdldlytmpl->delete($filter);
    }

    public function getRow($row,$filter)
    {
        $objMdlDlyTmpl = app::get('syslogistics')->model('dlytmpl');
        $data = $objMdlDlyTmpl->getRow($row,$filter);
        if($data['fee_conf'])
        {
            $data['fee_conf'] = unserialize($data['fee_conf']);
        }
        return $data;
    }

    /**
     * 根据运费模板ID 和传入的重量，地区参数计算运费
     *
     * @param int $templateId 运费模板ID
     * @param int $weight 重量
     * @param string $areaIds 地区ID
     *
     * @return int 返回运费值
     */
    public function countFee($templateId, $weight, $areaIds)
    {
        if( !area::checkArea($areaIds) ) return false;

        $filter = array(
            'template_id' => explode(',',$templateId),
            'status' => 'on',
        );
        $templateData = $this->objMdldlytmpl->getList("*", $filter);
        if( empty($templateData) ) return false;

        foreach($templateData as $template)
        {
            $fee_conf = unserialize($template['fee_conf']);
            $areaIdsArr = explode(',',$areaIds);
            foreach( $fee_conf as $data )
            {
                if( empty($data['area']) )
                {
                    $defaultConf = $data;
                }
                else
                {
                    $areaSetting = explode(',',$data['area']);
                    $intersect = array_intersect($areaSetting,$areaIdsArr);
                    if( $intersect )
                    {
                        $feeConf = $data;
                        break;
                    }
                }
            }
            $config = $feeConf ? $feeConf : $defaultConf;
            $fee[$template['template_id']] = $this->__count($config, $weight);
        }
        return $fee;
    }

    /**
     * 根据配置参数和重量计算出重量
     *
     * @param array $config 运费模板运费配置
     * @param int   $weight 重量kg
     *
     * @return int
     */
    private function __count($config, $weight)
    {
        if( $weight <= $config['start_standard'] ) return $config['start_fee'];

        if( $config['add_standard'] > 0 )
        {
            $addWeight = ceil(bcsub($weight, $config['start_standard'], 2));
            $nums = bcdiv($addWeight, $config['add_standard'], 2);
        }

        $fee = bcadd($config['start_fee'], bcmul($nums,$config['add_fee'],2) , 2);

        return $fee;
    }

}

