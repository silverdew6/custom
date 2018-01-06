<?php
class sysshop_api_message{

    //public $apiDescription = '获取指定订单的金额及总和';
    //接口传参数tid，是否为必传'valid'=>'required'
    public function getParams()
    {
        //接口传入的参数
        $return['params'] = array(
            'tid' => ['type'=>'string','valid'=>'','default'=>'', 'example'=>'','description'=>'支付单中订单号'],
            'payment_id' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'支付单号'],
        );
        return $return;
    }

    //传值为一维数组 17/08/07
    public function create($params)
    {
        //查询数据库
        //获取电子订单和支付企业信息
        //data为二维数组，1，电商企业信息；2，物流企业信息；3，支付企业；4，具有报关资质的企业
        $res_arr=[];
        $objShop = kernel::single('sysshop_xml_else');

        //判断是否为保税交易
        if(!$objShop->gettax($params)){
            $res_arr[0]=0;
            $res_arr[1]='非保税交易！';
            return $res_arr;
        }

        $data=[];                                            //报文参数
        $elseM=[];                                           //报关回执入库参数
        $else_M=[];                                          //报关回执参数组
        $mess_t=[];
        $type_m=0;

        //判断传参，根据有无$payment_id判断生成报文类型，有则生成1/3类型，无则生成2/4类型
        //返回二维数组，0：订单参数；1：支付参数；2：shop_id;3:guids;4:4种报文类型的四个重要参数;
        //返回data数组
        if(array_key_exists('payment_id', $params)){         //支付ID
            $payment_id=$params['payment_id'];
            $xml_else=$objShop->getElse($payment_id);

            if(empty($xml_else[3])){
                $res_arr[0]=0;
                $res_arr[1]='信息获取失败！';
                return $res_arr;
            }else{
                $tid=$xml_else[4]['tid'];
            }

            $data[1]=$xml_else[0];
            $data[3]=$xml_else[1];
        }else if(array_key_exists('tid', $params) && !array_key_exists('payment_id', $params)){
            $tid=$params['tid'];
            $xml_else=$objShop->getImport($tid);                                   //return $xml_else;

            if(!$xml_else){
                $res_arr[0]=0;
                $res_arr[1]='非保税交易！';
                return $res_arr;
            }

            $data[2]=$xml_else[0];
            $data[4]=$xml_else[1];
        }else{
            $res_arr[0]=0;
            $res_arr[1]='传参错误！';
            return $res_arr;
        }

        //获取商品信息
        $xml_goods=$objShop->getGoods($tid);                       //return $xml_goods;
        $shop_id=$xml_else[2];

        //判断该订单号是否已生成报文,返回已生成报文类型
        $project_m=app::get('sysshop')->model('mess');
        $res_m=$project_m->getList('status,type',['tid'=>$tid]);
        $m_type=[];
        if($res_m){
            $m_s=explode(',',$res_m[0]['status']);
            if(count($m_s)==4){
                $res_arr[0]=0;
                $res_arr[1]='该订单已申报通过';
                return $res_arr;
            }else{
                $m_type[0]=$res_m[0]['type'];
            }
        }

        //判断该订单号已生成报文类型，返回申报已通过和未通过类型
        $project_e=app::get('sysshop')->model('elsem');
        $res_e=$project_e->getList('name,type,status',['tid'=>$tid]);
        $v_type=[];            //未申报成功的type类型
        $s_type=[];            //报文类型通过
        $v_name=[];
        if($res_e){
            foreach($res_e as $ve){
                if($ve['status']!=5){
                    $v_t=$ve['type'];
                    $v_name[$v_t]=$ve['name'];
                    $v_type[]=$v_t;
                }else{
                    $s_type[]=$ve['type'];
                }
            }
        }

        $arr_file=[]; //生成html文件参数
        $del_path=[];  //要删除文件参数
        $arr_get=[];
        // return $v_type;

        //生成参数
        foreach($data as $i=>$val){
            $res_t=$this->get_millisecond();
            $time=$res_t[1];                             //修改

            $MessageType=$this->type($i);
            $head=$this->messHead($val,$MessageType,$res_t[0]);             //头文件

            $xml='CEB_'.$MessageType.'_'.$val['SenderID'].'_EPORT_'.$time;
            $xmlf=$xml.'.xml';                                              //报文名

            $str='';
            $MessageID=$val['MessageID'];

            $pathf='upload/xml/'.$shop_id.'/'.$MessageType;               //文本存放路径
            if($i==1){                                                    //电子订单
                $good=$xml_goods;                                         //商品
                $arr=$this->messGoods($good);                       //return $arr;
                $else_goods = $arr['goodsEstr'];
                $else_str=$this->elsectroniCorder($head,$else_goods,$val,$xml_else[3][0]);
                $str = $else_str;
                $type=1;
            }elseif($i==2){                                               //物流清单
                $log_str=$this->logisticsWaybill($head,$val,$xml_else[3][0]);
                $str = $log_str;
                $type=3;
            }else if($i==3){                                              //支付凭证
                $pay_str=$this->payOrder($head,$val,$xml_else[3][1]);
                $str = $pay_str;
                $type=2;
            }elseif($i==4){                                               //报关报文
                $good=$xml_goods;
                $arr=$this->messGoods($good);
                $imp_goods = $arr['goodsIstr'];
                $imp_str=$this->importList($head,$imp_goods,$val,$xml_else[3][1]);
                $str = $imp_str;
                $type=4;
            }

            $arr_file[0]=$str;
            $arr_file[1]=$xmlf;
            $arr_file[2]=$pathf;

            $arr_get[$type]=$arr_file;

            if(!in_array($type,$v_type) && !in_array($type,$s_type)){    //插入数据
                $elseM['tid'] = $tid;
                $elseM['name'] = $xmlf;
                $elseM['MessageID'] = $MessageID;
                $elseM['type'] = $type;
                $elseM['platform']='';
                $elseM['custom']='';
                $elseM['checkout']='';
                $elseM['platformS']=0;
                $elseM['customS']=0;
                $elseM['checkoutS']=0;
                $elseM['platformInfo']='';
                $elseM['customInfo']='';
                $elseM['checkoutInfo']='';
                $elseM['platform_time']=NULL;
                $elseM['custom_time']=NULL;
                $elseM['checkout_time']=NULL;
                $elseM['status']=0;
                if($type==1){
                    $elseM['typeNo']=$xml_else[4]['tid'];
                }elseif($type==2){
                    $elseM['typeNo']=$xml_else[4]['payTransactionId'];

                }elseif($type==3){
                    $elseM['typeNo']=$xml_else[4]['logisticsNo'];

                }elseif($type==4){
                    $elseM['typeNo']=$xml_else[4]['copNo'];

                }

            }else if(in_array($type,$v_type)){   //修改的数据
                $elseM['name'] = $xmlf;
                $elseM['MessageID'] = $MessageID;
                $elseM['platform']='';
                $elseM['custom']='';
                $elseM['checkout']='';
                $elseM['platformS']=0;
                $elseM['customS']=0;
                $elseM['checkoutS']=0;
                $elseM['platformInfo']='';
                $elseM['customInfo']='';
                $elseM['checkoutInfo']='';
                $elseM['platform_time']=NULL;
                $elseM['custom_time']=NULL;
                $elseM['checkout_time']=NULL;
                $elseM['status']=0;
                $elseM['ex_type']=0;

                $del_path[$type] = 'upload/xml/'.$shop_id.'/'.$MessageType.'/'.$v_name[$type];

            }
            $mess_t[]=$type;
            $else_M[]=$elseM;
        }


        //生成xml文件,非申报成功可以重新生成
        foreach($arr_get as  $kf=>$vf){
            if(!empty($vf[0]) && !in_array($kf,$s_type)){
                $get_file=$this->get_message($vf[0],$vf[1],$vf[2]);
                if(!$get_file){
                    $res_arr[0]=0;
                    $res_arr[1]='报文生成失败';
                    return $res_arr;
                }
            }
        }

        //订单报文入库
        if(in_array(1,$mess_t)){
            $type_m=1;
        }elseif(in_array(3,$mess_t)){
            $type_m=2;
        }
        if($type_m)
        {
            if(empty($m_type)){
                $messaE['tid']=$tid;
                $messaE['status'] = 0;
                $messaE['shop_id'] = $xml_else[2];
                $messaE['type'] = $type_m;
                $project_m->insert($messaE);
            }else{
                $type=$m_type[0];
                if($type!=$type_m && $type!=3){
                    $messaE['type']=3;
                    $project_m->update($messaE,['tid'=>$tid]);
                }
            }
        }
        //报文分类入库
        foreach($else_M as $ke=>$vel){
            if(array_key_exists('tid',$vel)){
                $project_e->insert($vel);
            }else{
                $type=$mess_t[$ke];
                $project_e->update($vel,['tid'=>$tid,'type'=>$type]);
            }
        }

        //删除原有文件，未申报成功时
        foreach($del_path as $kp=>$vp){
            if(in_array($kp,$v_type)){
                unlink($vp);
            }
        }
        $res_arr[0]=1;
        return $res_arr;
    }



    protected function type($type=1){
        if($type==1){
            $messageId='CEB311';
        }else if($type==2){
            $messageId='CEB511';
        }else if($type==3){
            $messageId='CEB411';
        }else if($type==4){
            $messageId='CEB621';
        }else{
            return '参数有误';
        }
        return $messageId;
    }
//生成毫秒级时间戳+四位流水数
    protected function get_millisecond()
    {
        $res=[];
        //时间精确到毫秒
        list($usec, $sec) = explode(" ", microtime());
        $msec=round($usec*1000);
        $msec = str_pad($msec,3,'0',STR_PAD_RIGHT);
        $time=date("YmdHis").$msec;
        //四位流水数字
        $str=substr(strval(rand(10000,19999)),1,4);
        $result=$time.$str;
        $res[0]=$time;
        $res[1]=$result;
        return $res;

    }
//数据写入文本
//要写入的数据，文本名，文本所在路径
    protected function  get_message($xml,$xpath,$path){
        //创建文本目录

        //$path = getcwd().'/'.$path;
        //return $path;
        if(!is_dir($path)){
            $res=mkdir(iconv("UTF-8", "GBK", $path),0777,true);
            //$res=mkdir($path,0777,true);
            if(!$res){
                return '文件夹创建失败';
            }
        }
        //文本全路径
        $pathx=$path.'/'.$xpath;
        file_put_contents($pathx,$xml,FILE_APPEND);
        if(file_exists($pathx)){
            return true;
        }else{
            return false;
        }
    }
//报文 --头311
    protected function messHead($music,$type,$time){
        $headertpl=<<<xml
    <MessageHead>
		<MessageID>%s</MessageID>
		<MessageType>%s</MessageType>
		<OrgCode>%s</OrgCode>
		<CopCode>%s</CopCode>
		<CopName>%s</CopName>
		<SenderID>%s</SenderID>
		<ReceiverID>EPORT</ReceiverID>
		<ReceiverDepartment>%s</ReceiverDepartment>
		<SendTime>%s</SendTime>
		<Version>1.0</Version>
	</MessageHead>
xml;
        $result = sprintf($headertpl,$music['MessageID'],$type,$music['OrgCode'],$music['CopCode'],$music['CopName'],$music['SenderID'],$music['ReceiverDepartment'],$time);
        return $result;
    }
//报文商品
    protected function messGoods($goodsArray){
        $goodsEtpl=<<<xml
        <OrderList>
            <gnum>%s</gnum>
			<itemNo>%s</itemNo>
			<itemName>%s</itemName>
			<itemDescribe></itemDescribe>
			<barCode/>
			<unit>%s</unit>
			<qty>%s</qty>
			<price>%s</price>
			<totalPrice>%s</totalPrice>
			<currency>142</currency>
			<country>%s</country>
			<ciqGno>%s</ciqGno>
			<gcode>%s</gcode>
			<gmodel>%s</gmodel>
			<ciqGmodel>%s</ciqGmodel>
			<brand>%s</brand>
			<note/>
			</OrderList>
xml;
        $goodsItpl=<<<xml
		<InventoryList>
          <gnum>%s</gnum>
			<itemRecordNo>%s</itemRecordNo>
			<itemNo>%s</itemNo>
			<itemName>%s</itemName>
			<gcode>%s</gcode>
			<gname>%s</gname>
			<gmodel>%s</gmodel>
			<barCode/>
			<country>%s</country>
			<currency>142</currency>
			<qty>%s</qty>
			<unit>%s</unit>
			<qty1>%s</qty1>
			<unit1>%s</unit1>
                        <qty2>%s</qty2>
			<unit2>%s</unit2>
			<price>%s</price>
			<totalPrice>%s</totalPrice>
			<ciqGno>%s</ciqGno>
			<storageId/>
			<serialNo/>
			<batch/>
			<note/>	
		</InventoryList>
xml;
        $goodsEstr='';
        $goodsIstr='';
        foreach($goodsArray as $k=>$item){
            $i=$k+1;
            $Estr=sprintf($goodsEtpl,$i,$item['itemNo'],$item['itemName'],$item['unit'],$item['qty'],$item['price'],$item['totalPrice'],$item['country'],$item['ciqGno'],$item['gcode'],$item['gmodel'],$item['ciqGmodel'],$item['brand']);
            $Istr=sprintf($goodsItpl,$i,$item['itemRecordNo'],$item['itemNo'],$item['itemName'],$item['gcode'],$item['gname'],$item['gmodel'],$item['country'],$item['qty'],$item['unit'],$item['qty1'],$item['unit1'],$item['qty2'],$item['unit2'],$item['price'],$item['totalPrice'],$item['ciqGno']);
            if($k!=0){
                $goodsEstr.=<<<xml
\r\n$Estr
xml;
                $goodsIstr.=<<<xml
\r\n$Istr
xml;

            }else{
                $goodsEstr.=$Estr;
                $goodsIstr.=$Istr;
            }
        }

        //return $goodsEstr;
        $arr=[];
        $arr['goodsEstr']=$goodsEstr;
        $arr['goodsIstr']=$goodsIstr;
        return $arr;
    }
//报文--电子订单
    protected function elsectroniCorder($header,$goods,$music,$guid){
        $elsetpl=<<<xml
<?xml version="1.0" encoding="UTF-8"?>
<CEB311Message xmlns="http://www.chinaport.gov.cn/ceb" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" guid="$guid" version="1.0">
$header
<Order>
		<OrderHead>
			<guid>%s</guid>
			<appType>%s</appType>
			<appTime>%s</appTime>
			<appStatus>%s</appStatus>
			<orderType>I</orderType>
			<orderNo>%s</orderNo>
			<ebpCode>%s</ebpCode>
			<ebpName>%s</ebpName>
			<ebcCode>%s</ebcCode>
			<ebcName>%s</ebcName>
			<goodsValue>%s</goodsValue>
			<freight>%s</freight>
			<discount>%s</discount>
			<taxTotal>%s</taxTotal>
			<acturalPaid>%s</acturalPaid>
			<currency>142</currency>
			<buyerRegNo>%s</buyerRegNo>
			<buyerName>%s</buyerName>
			<buyerIdType>1</buyerIdType>
			<buyerIdNumber>%s</buyerIdNumber>
			<payCode>%s</payCode>
			<payName>%s</payName>
			<payTransactionId>%s</payTransactionId>
			<batchNumbers>1</batchNumbers>
			<consignee>%s</consignee>
			<consigneeTelephone>%s</consigneeTelephone>
			<consigneeAddress>%s</consigneeAddress>
			<consigneeDistrict>%s</consigneeDistrict>
			<note/>
		</OrderHead>
		$goods
	</Order>
</CEB311Message>
xml;
        $time=date('YmdHis',time());
        $else_str = sprintf($elsetpl,$music['guid'],$music['appType'],$time,$music['appStatus'],$music['orderNo'],$music['ebpCode'],$music['ebpName'],$music['ebcCode'],$music['ebcName'],$music['goodsValue'],$music['freight'],$music['discount'],$music['taxTotal'],$music['acturalPaid'],$music['buyerRegNo'],$music['buyerName'],$music['buyerIdNumber'],$music['payCode'],$music['payName'],$music['payTransactionId'],$music['consignee'],$music['consigneeTelephone'],$music['consigneeAddress'],$music['consigneeDistrict']);
        return $else_str;
    }
//报文  --物流运单
    protected function logisticsWaybill($header,$music,$guid){
        $logisttpl=<<<xml
<?xml version="1.0" encoding="UTF-8"?>
<CEB511Message xmlns="http://www.chinaport.gov.cn/ceb" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" guid="$guid" version="1.0">
$header
<Logistics>
		<LogisticsHead>
			<guid>%s</guid>
			<appType>%s</appType>
			<appTime>%s</appTime>
			<appStatus>%s</appStatus>
			<logisticsCode>%s</logisticsCode>
			<logisticsName>%s</logisticsName>
			<logisticsNo>%s</logisticsNo>
			<billNo></billNo>
			<freight>%s</freight>
			<insuredFee>%s</insuredFee>
			<currency>142</currency>
			<weight>%s</weight>
			<packNo>1</packNo>
			<goodsInfo>%s</goodsInfo>
			<consignee>%s</consignee>
			<consigneeAddress>%s</consigneeAddress>
			<consigneeTelephone>%s</consigneeTelephone>
			<consigneeCountry>142</consigneeCountry>
			<consigneeProvince>%s</consigneeProvince>
			<consigneeCity>%s</consigneeCity>
			<consigneeDistrict>%s</consigneeDistrict>
			<note/>
		</LogisticsHead>
	</Logistics>
</CEB511Message>
xml;
        $time=date('YmdHis',time());
        $logist_str = sprintf($logisttpl,$music['guid'],$music['appType'],$time,$music['appStatus'],$music['logisticsCode'],$music['logisticsName'],$music['logisticsNo'],$music['freight'],$music['insuredFee'],$music['weight'],$music['goodsInfo'],$music['consignee'],$music['consigneeAddress'],$music['consigneeTelephone'],$music['consigneeProvince'],$music['consigneeCity'],$music['consigneeDistrict']);
        return $logist_str;

    }
//报文  --支付凭证
    protected function payOrder($header,$music,$guid){
        $paytpl=<<<xml
<?xml version="1.0" encoding="UTF-8"?>
<CEB411Message xmlns="http://www.chinaport.gov.cn/ceb" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" guid="$guid" version="1.0">
$header
<Payment>
		<PaymentHead>
			<guid>%s</guid>
			<appType>%s</appType>
			<appTime>%s</appTime>
			<appStatus>%s</appStatus>
			<payCode>%s</payCode>
			<payName>%s</payName>
			<payTransactionId>%s</payTransactionId>
			<orderNo>%s</orderNo>
			<ebpCode>%s</ebpCode>
			<ebpName>%s</ebpName>
			<payerIdType>1</payerIdType>
			<payerIdNumber>%s</payerIdNumber>
			<payerName>%s</payerName>
			<telephone>%s</telephone>
			<amountPaid>%s</amountPaid>
			<currency>142</currency>
			<payTime>%s</payTime>
			<note/>
		</PaymentHead>
	</Payment>
</CEB411Message>
xml;
        $time=date('YmdHis',time());
        $pay_str = sprintf($paytpl,$music['guid'],$music['appType'],$time,$music['appStatus'],$music['payCode'],$music['payName'],$music['payTransactionId'],$music['orderNo'],$music['ebpCode'],$music['ebpName'],$music['payerIdNumber'],$music['payerName'],$music['telephone'],$music['amountPaid'],$music['payTime']);
        return $pay_str;

    }
//报文   --进口清单
    protected function importList($header,$goods,$music,$guid){
        $importtpl=<<<xml
<?xml version="1.0" encoding="UTF-8"?>
<CEB621Message xmlns="http://www.chinaport.gov.cn/ceb" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" guid="$guid" version="1.0">
	$header
	<Inventory>
		<InventoryHead>
			<guid>%s</guid>
			<appType>%s</appType>
			<appTime>%s</appTime>
			<appStatus>%s</appStatus>
			<orderNo>%s</orderNo>
			<ebpCode>%s</ebpCode>
			<ebpName>%s</ebpName>
			<ebcCode>%s</ebcCode>
			<ebcName>%s</ebcName>
			<logisticsNo>%s</logisticsNo>
			<logisticsCode>%s</logisticsCode>
			<logisticsName>%s</logisticsName>
			<payNo>%s</payNo>
			<copNo>%s</copNo>
			<preEntryNo>%s</preEntryNo>
			<preNo></preNo>
			<assureCode>%s</assureCode>
			<emsNo>%s</emsNo>
			<invtNo></invtNo>
			<ieFlag>I</ieFlag>
			<declTime>%s</declTime>
			<customsCode>%s</customsCode>
			<ciqCode>%s</ciqCode>
			<portCode>%s</portCode>
			<ieDate>%s</ieDate>
			<buyerIdType>1</buyerIdType>
			<buyerIdNumber>%s</buyerIdNumber>
			<buyerName>%s</buyerName>
			<buyerTelephone>%s</buyerTelephone>
			<consigneeAddress>%s</consigneeAddress>
			<consigneeCity>%s</consigneeCity>
			<agentCode>%s</agentCode>
			<agentName>%s</agentName>
			<areaCode>%s</areaCode>
			<areaName>%s</areaName>
			<tradeMode>1210</tradeMode>
			<trafMode>%s</trafMode>
			<trafNo></trafNo>
			<shipName>%s</shipName>
			<voyageNo></voyageNo>
			<billNo></billNo>
			<loctNo></loctNo>
			<licenseNo></licenseNo>
			<country>142</country>
			<freight>%s</freight>
			<insuredFee>%s</insuredFee>
			<currency>142</currency>
			<wrapType>%s</wrapType>
			<packNo>1</packNo>
			<grossWeight>%s</grossWeight>
			<netWeight>%s</netWeight>
			<businessMode>2</businessMode>
			<signCompany>%s</signCompany>
			<signCompanyName>%s</signCompanyName>
			<note/>
		</InventoryHead>
			$goods
	</Inventory>
</CEB621Message>
xml;
        $time=date('YmdHis',time());
        $import_str = sprintf($importtpl,$music['guid'],$music['appType'],$time,$music['appStatus'],$music['orderNo'],$music['ebpCode'],$music['ebpName'],$music['ebcCode'],$music['ebcName'],$music['logisticsNo'],$music['logisticsCode'],$music['logisticsName'],$music['payNo'],$music['copNo'],$music['preEntryNo'],$music['assureCode'],$music['emsNo'],$music['declTime'],$music['customsCode'],$music['ciqCode'],$music['portCode'],$music['ieDate'],$music['buyerIdNumber'],$music['buyerName'],$music['buyerTelephone'],$music['consigneeAddress'],$music['consigneeCity'],$music['agentCode'],$music['agentName'],$music['areaCode'],$music['areaName'],$music['trafMode'],$music['shipName'],$music['freight'],$music['insuredFee'],$music['wrapType'],$music['grossWeight'],$music['netWeight'],$music['signCompany'],$music['signCompanyName']);
        return $import_str;

    }
}
