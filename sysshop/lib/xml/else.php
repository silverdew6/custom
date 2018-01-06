<?php

/**
 * @brief 店铺相关
 */
class sysshop_xml_else
{

    /**
     * @brief 电子订单，支付企业信息
     *
     * @param $enterapplyId int
     *
     * @return  bool
     */
    public function getElse($payment_id)
    {
        $pay=[];
        $else=[];
        $xml_arr=[];
        $guids=[];
        $else_m=[];        //报文入库所需参数

        //$payment_id="15112914362833814881";
        //ectolls_trade_paybill
        $object1=app::get('ectools')->model('trade_paybill');
        $row1='tid,payed_time';
        $res1=$object1->getList($row1,['payment_id'=>$payment_id]);//二维数组

        //systrade_trade
        $tid=$res1['0']['tid'];
        $object_tra=app::get('systrade')->model('trade');
        $row_tra='shop_id,user_id,payment,total_fee,post_fee,receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,receiver_mobile,tax,card_id,zonghe_ratemoney,use_coupon_money';
        $res_tra=$object_tra->getList($row_tra,['tid'=>$tid]);//二维数组

        //判断是否为保税交易
        if($res_tra[0]['tax']!=2){
            return false;
        }

        //默认购买人为收货人
        $pay['telephone']=trim($res_tra[0]['receiver_mobile']);
        //在认证列表中查询身份证信息
        $pay['payerName']=trim($res_tra[0]['receiver_name']);//支付人姓名
        $object_c=app::get('sysuser')->model('card');
        $card=$object_c->getRow('card_id',['name'=>$pay['payerName']]);
        if($card['card_id']){
            $pay['payerIdNumber']=$card['card_id'];//--user_id 支付人证件号码
        }else{
            if($res_tra[0]['card_id'] != ''){
                $pay['payerIdNumber'] = trim($res_tra[0]['card_id']);
            }

        }
        $else['buyerName']=$pay['payerName'];// --user_id  sysuer_user  username订购人的真实姓名
        $else['buyerIdNumber']=$pay['payerIdNumber'];//???--user_id  sysuer_user  usercard 订购人的身份证号

        $pay['MessageID']=$this->getGuid();
        $guids[1]=$pay['MessageID'];
        $else['MessageID']=$this->getGuid();
        $guids[0]=$else['MessageID'];
        $pay['guid']=$this->getGuid();
        $else['guid']=$this->getGuid();

        $pay['orderNo']=$res1['0']['tid'];//订单编号
        $else['orderNo']=$res1['0']['tid'];//--tid  订单编号
        $time1=$res1['0']['payed_time'];
        $pay['payTime']=date('YmdHis',$time1);//--payed_time  支付时间

        //ectools_payments
        $object2=app::get('ectools')->model('payments');
        $row2='cur_money,trade_no';
        $res2=$object2->getList($row2,['payment_id'=>$payment_id]);//二维数组
        $pay['amountPaid']=$res2[0]['cur_money'];;//--cur_money  支付金额

        $else['acturalPaid']=$res_tra[0]['payment'];//--payment  商品价格+运杂费+代扣税款-非现金抵扣金额，与支付凭证的支付金额一致
        $else['goodsValue']=$res_tra[0]['total_fee'];//--total_fee  商品实际成交价，含非现金抵扣金额
        if(empty($res_tra[0]['post_fee'])){
            $else['freight']=0;//--post_fee  不包含在商品价格中的运杂费，无则填 写“0”
        }else{
            $else['freight']=$res_tra[0]['post_fee'];//--post_fee  不包含在商品价格中的运杂费，无则填 写“0”
        }
        $else['consignee']=$res_tra[0]['receiver_name'];//--receiver_name  收货人姓名，必须与电子运单的收货人姓名一致。
        if(empty($res_tra[0]['receiver_district'])){
            $else['consigneeAddress']=$res_tra[0]['receiver_state'].$res_tra[0]['receiver_city'].$res_tra[0]['receiver_address'];//--receiver_address收货地址
        }else{
            $row_na='parent';
            $project_na=app::get('sysshop')->model('shop_nation');
                $res_nab=$project_na->getList($row_na,['district'=>$res_tra[0]['receiver_district']]);
                if($res_nab[0]['parent']){
                    //区->市
                    $res_nac=$project_na->getList('city,parent',['id'=>$res_nab[0]['parent']]);
                    //市->省
                    $res_nap=$project_na->getList('province',['id'=>$res_nac[0]['parent']]);
                    $else['consigneeAddress']=$res_nap[0]['province'].$res_nac[0]['city'].$res_tra[0]['receiver_district'].$res_tra[0]['receiver_address'];//--receiver_address收货地址
            }
        }

        $else['consigneeTelephone']=$res_tra[0]['receiver_mobile'];//--receiver_mobile  or  receiver_phone收货人联系电话，必须与电子运单的收货人电话一致。
        if(empty($res_tra[0]['zonghe_ratemoney'])){
            $else['taxTotal']=0;//--zonghe_ratemoney  企业预先代扣的税款金额，无则填“0”
        }else{
            $else['taxTotal']=$res_tra[0]['zonghe_ratemoney'];//--zonghe_ratemoney  企业预先代扣的税款金额，无则填“0”
        }
       if(empty($res_tra[0]['use_coupon_money'])){
           $else['discount']=0;//--use_coupon_money  使用积分、虚拟货币、代金券等非现金支付金额，无则填写“0”
       }else{
           $else['discount']=$res_tra[0]['use_coupon_money'];//--use_coupon_money  使用积分、虚拟货币、代金券等非现金支付金额，无则填写“0”
       }

        $else['buyerRegNo']=$res_tra[0]['user_id'];//????--user_id  订购人的交易平台注册号

        //sysshop_shop_nation
        if(!empty($res_tra[0]['receiver_district'])){
            $row_na='code';
            $project_na=app::get('sysshop')->model('shop_nation');
                $res_na=$project_na->getList($row_na,['district'=>$res_tra[0]['receiver_district']]);
            $else['consigneeDistrict']=$res_na[0]['code'];//???--receiver_zip 邮编 收货人行政区划代码
        }

        //电子信息
//shop_id  -->sysshop_shop_infor
        $shop_id=$res_tra[0]['shop_id'];
        $object_infor=app::get('sysshop')->model('shop_infor');
        $res_infor=$object_infor->getList('*',['shop_id'=>$shop_id]);//二维数

        //头信息数组
        $else['OrgCode']=$res_infor[0]['OrgCode'];//企业组织机构代码或统 一社会信息代码
        $else['CopCode']=$res_infor[0]['CopCode'];//企业海关注册代码
        $else['CopName']=$res_infor[0]['CopName'];//报文传输的企业海关注册名称
        $else['SenderID']=$res_infor[0]['SenderID'];//企业客户端 ID 号
        $else['ReceiverDepartment']=$res_infor[0]['ReceiverDepartment'];//填写本报文发送的监管单位

        $else['appType']=$res_infor[0]['appType'];//企业报送类型
        $else['appStatus']=$res_infor[0]['appStatus'];//业务状态
        $else['ebcCode']=$res_infor[0]['ebcCode'];//电商企业海关注册登记编号
        $else['ebcName']=$res_infor[0]['ebcName'];//电商企业的海关注册登记名称

        $else['payTransactionId']=$res_infor[0]['ebcCode'].'ICBC'.$res2[0]['trade_no'];//--trade_no   支付企业唯一的支付流水号
        $pay['payTransactionId']=$res_infor[0]['ebcCode'].'ICBC'.$res2[0]['trade_no'];//?  --trade_no  支付交易编号

        //sysshop_shop_other
        $project_o=app::get('sysshop')->model('shop_other');
        $res_o=$project_o->getList('*');
        $else['ebpCode']=$res_o[0]['ebpCode'];//电商平台海关注册登记编号
        $else['ebpName']=$res_o[0]['ebpName'];//电商平台海关注册登记名称
        $pay['ebpCode']=$res_o[0]['ebpCode'];//电商平台代码
        $pay['ebpName']=$res_o[0]['ebpName'];//电商平台名称



        $pay_id=$res_infor[0]['payID'];
        //sysshop_shop_pay
        $object_pay=app::get('sysshop')->model('shop_pay');
        $res_pay=$object_pay->getList('*',['pay_id'=>$pay_id]);//二维数组

        $pay['OrgCode']=$res_pay[0]['OrgCode'];//企业组织机构代码或统 一社会信息代码
        $pay['CopCode']=$res_pay[0]['CopCode'];//企业海关注册代码
        $pay['CopName']=$res_pay[0]['CopName'];//报文传输的企业海关注册名称
        $pay['SenderID']=$res_pay[0]['SenderID'];//企业客户端 ID 号
        $pay['ReceiverDepartment']=$res_pay[0]['ReceiverDepartment'];//填写本报文发送的监管单位

        $pay['appType']=$res_pay[0]['appType'];//报送类型
        $pay['appStatus']=$res_pay[0]['appStatus'];//业务状态
        $pay['payCode']=$res_pay[0]['payCode'];//支付企业代码
        $pay['payName']=$res_pay[0]['payName'];//支付企业名称

        $else['payCode']=$res_pay[0]['payCode'];//支付企业的海关注册登记编号
        $else['payName']=$res_pay[0]['payName'];//支付企业在海关注册登记的企业名称。

        $else_m['tid']=$tid;
        $else_m['payTransactionId']=$res_infor[0]['ebcCode'].'ICBC'.$res2[0]['trade_no'];
        //物流单号
        $row_del='logi_no';
        $project_del=app::get('syslogistics')->model('delivery');
        $res_del=$project_del->getList($row_del,['tid'=>$tid]);
        $else_m['logisticsNo']=$res_del['logi_no'];
        $else_m['copNo']=$payment_id;

        $xml_arr[0]=$else;       //返回订单数组
        $xml_arr[1]=$pay;        //返回支付数组
        $xml_arr[2]= $res_tra[0]['shop_id'];//返回店铺信息
        $xml_arr[3]=$guids;
        $xml_arr[4]=$else_m;
        return $xml_arr;
    }

    /**
     * @brief 物流信息 报关信息
     *
     * @param $tid int
     *
     * @return  arr
     */
    public function getImport($tid){
        //物流信息511
        $log=[];
        //进口信息
        $import=[];
        $arr=[];
        $guids=[];
        $else_m=[];    //报文信息

        //systrade_trade
        $object_tra=app::get('systrade')->model('trade');
        $row_tra='receiver_name,receiver_mobile,tax,card_id';
        $res_tra=$object_tra->getRow($row_tra,['tid'=>$tid]);//二维数组
        //判断是否为保税交易
        if($res_tra['tax']!=2){
            return false;
        }

        $log['MessageID']=$this->getGuid();
        $guids[0]=$log['MessageID'];
        $import['MessageID']=$this->getGuid();
        $guids[1]=$import['MessageID'];
        $log['guid']=$this->getGuid();
        $import['guid']=$this->getGuid();


        //默认购买人为收货人
        $import['buyerTelephone']=trim($res_tra['receiver_mobile']);
        //在认证列表中查询身份证信息
        $import['buyerName']=trim($res_tra['receiver_name']);//支付人姓名
        $object_c=app::get('sysuser')->model('card');
        $card=$object_c->getRow('card_id',['name'=>$import['buyerName']]);
        if($card['card_id']){
            $import['buyerIdNumber']=$card['card_id'];//--user_id 支付人证件号码
        }else{
            if($res_tra['card_id'] != ''){
                $import['buyerIdNumber'] = trim($res_tra['card_id']);
            }
        }

        //syslogistics_delivery
        $row_del='delivery_id,tid,user_id,shop_id,post_fee,is_protect,logi_no,receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_mobile';
        $project_del=app::get('syslogistics')->model('delivery');
        $res_del=$project_del->getList($row_del,['tid'=>$tid]);

        $log['freight']=$res_del[0]['post_fee'];//--post_fee  运费
        $import['freight']=$res_del[0]['post_fee'];//运费
        if(empty($res_del[0]['is_protect'])){
            $log['insuredFee']='0';
            $import['insuredFee']='0';//保价费
        }else{
            $log['insuredFee']=$res_del[0]['is_protect'];//--is_protect  是否保价  保价费
            $import['insuredFee']=$res_del[0]['is_protect'];//保价费
        }
        $log['logisticsNo']=$res_del[0]['logi_no'];//--logi_no  物流运单编号
        $import['logisticsNo']=$res_del[0]['logi_no'];//运单号
        $log['consignee']=$res_del[0]['receiver_name'];//--receiver_name 收货人名称

        $log['consigneeTelephone']=$res_del[0]['receiver_mobile'];//--receiver_mobile  收件人电话
        $row_na='code,parent';
        $project_na=app::get('sysshop')->model('shop_nation');
        //省
        if(!empty($res_del[0]['receiver_state'])){
            $res_nap=$project_na->getList('code,province',['province'=>$res_del[0]['receiver_state']]);
            if($res_nap[0]){
                $log['consigneeProvince']=$res_nap[0]['code'];//--receiver_state sysshop_shop_nation 收货人地址（省），按行政区划代码填写收货人地址所在省级行政区划代码
            }
        }
        //市
        if(!empty($res_del[0]['receiver_city'])){
            $res_nac=$project_na->getList('code,city,parent',['city'=>$res_del[0]['receiver_city']]);
            if($res_nac[0]){
                $import['consigneeCity']=$res_nac[0]['code'];//收件人所在城市行政区划代码
                $log['consigneeCity']=$res_nac[0]['code'];//--receiver_district收货人地址（区）
            }
        }
        //区
        if(!empty($res_del[0]['receiver_district'])){
            $res_nad=$project_na->getList('code,district,parent',['district'=>$res_del[0]['receiver_district']]);
            if($res_nad[0]){
                $log['consigneeDistrict']=$res_nad[0]['code'];//--receiver_district收货人地址（区）
                //有区无市
                if(!isset($log['consigneeCity']) && isset($log['consigneeDistrict'])){
                    $res_nac=$project_na->getList('code,city,parent',['id'=>$res_nad[0]['parent']]);
                    $log['consigneeCity']=$res_nac[0]['code'];
                    $import['consigneeCity']=$res_nac[0]['code'];//收件人所在城市行政区划代码
                }
            }
        }
        //有市无省
        if(!isset($log['consigneeProvince']) && isset($log['consigneeCity'])){
            $res_nap=$project_na->getList('code,province',['id'=>$res_nac[0]['parent']]);
            $log['consigneeProvince']=$res_nap[0]['code'];
        }
        $log['consigneeAddress']=$res_nap[0]['province'].$res_nac[0]['city'].$res_nad[0]['district'].$res_del[0]['receiver_address'];//--receicer_address  收件人地址
        $import['consigneeAddress']=$res_nap[0]['province'].$res_nac[0]['city'].$res_nad[0]['district'].$res_del[0]['receiver_address'];//收件人地址

        //syslogistics_delivery_detail
        $row_det='oid,number,sku_title';
        $project_det=app::get('syslogistics')->model('delivery_detail');
        $res_det=$project_det->getList($row_det,['delivery_id'=>$res_del[0]['delivery_id']]);
        $num=0;
        $weight=0;
        $nweight=0;
        $str='';

        foreach($res_det as $kk=>$val){
            $num+=$val['number'];
                //求item_id
                $row_or='item_id,total_weight';
                $project_or=app::get('systrade')->model('order');
                $res_or=$project_or->getList($row_or,['oid'=>$val['oid']]);
                $item_id=$res_or[0]['item_id'];
               //商品毛重 净重
            //sysitem_item,17/07/07
            $project_it=app::get('sysitem')->model('item');
            $res_it=$project_it->getList('gname,grossWeight,netWeight',['item_id'=>$item_id]);
            $weight+=$res_it[0]['grossWeight']*$val['number'];
            $nweight+=$res_it[0]['netWeight']*$val['number'];
            $str.=$res_it[0]['gname'].':'.$val['number'].'  ';
        }

        $log['goodsInfo']=$str;
        $log['weight']=$weight;//--total_weight  //？毛重（Kg）
        $import['grossWeight']=$weight;//毛重（Kg）
        $import['netWeight']=$nweight;//净重（Kg）

        //sysshop_shop_infor
        $row_infor='ebcCode,ebcName,logisticsID,declarationID,signID';
        $project_infor=app::get('sysshop')->model('shop_infor');
        $res_infor=$project_infor->getList($row_infor,['shop_id'=>$res_del[0]['shop_id']]);
        $import['ebcCode']=$res_infor[0]['ebcCode'];//电商企业代码
        $import['ebcName']=$res_infor[0]['ebcName'];//电商企业名称

        //sysshop_shop_log
        $project_log=app::get('sysshop')->model('shop_log');
        $res_log=$project_log->getList('*',['log_id'=>$res_infor[0]['logisticsID']]);

        $log['OrgCode']=$res_log[0]['OrgCode'];//企业组织机构代码或统 一社会信息代码
        $log['CopCode']=$res_log[0]['CopCode'];//企业海关注册代码
        $log['CopName']=$res_log[0]['CopName'];//报文传输的企业海关注册名称
        $log['SenderID']=$res_log[0]['SenderID'];//企业客户端 ID 号
        $log['ReceiverDepartment']=$res_log[0]['ReceiverDepartment'];//填写本报文发送的监管单位
        $log['appType']=$res_log[0]['appType'];//报送类型
        $log['appStatus']=$res_log[0]['appStatus'];//业务状态
        $log['logisticsCode']=$res_log[0]['logisticsCode'];//物流企业代码
        $log['logisticsName']=$res_log[0]['logisticsName'];//物流企业名称
        $import['logisticsCode']=$res_log[0]['logisticsCode'];//物流企业代码
        $import['logisticsName']=$res_log[0]['logisticsName'];//物流企业名称

        $import['orderNo']=$tid;//订单编号
        //支付单号
        //ectools_trade_paybill
        $project_pay=app::get('ectools')->model('trade_paybill');
        $res_pay=$project_pay->getList('payment_id',['tid'=>$tid]);
        $import['copNo']=$res_pay[0]['payment_id'];

        //$import['copNo']=$tid;//企业内部编号
        $import['declTime']=date('Ymd',time());//申报日期
        $import['shipName']="“-”";//运输工具名称

        //ectools_trade_paybill
        $row_pb='payment_id';
        $project_pb=app::get('ectools')->model('trade_paybill');
        $res_pb=$project_pb->getList($row_pb,['tid'=>$tid]);
        //ectools_payments
        $row_p='trade_no';
        $project_p=app::get('ectools')->model('payments');
        $res_p=$project_p->getList($row_p,['payment_id'=>$res_pb[0]['payment_id']]);
        $import['payNo']=$res_infor[0]['ebcCode'].'ICBC'.$res_p[0]['trade_no'];//支付交易编号

        //sysshop_shop_import
        $project_import=app::get('sysshop')->model('shop_import');
        $res_import=$project_import->getList('*',['import_id'=>$res_infor[0]['declarationID']]);

        $import['OrgCode']=$res_import[0]['OrgCode'];//企业组织机构代码或统 一社会信息代码
        $import['CopCode']=$res_import[0]['CopCode'];//企业海关注册代码
        $import['CopName']=$res_import[0]['CopName'];//报文传输的企业海关注册名称
        $import['SenderID']=$res_import[0]['SenderID'];//企业客户端 ID 号
        $import['ReceiverDepartment']=$res_import[0]['ReceiverDepartment'];//填写本报文发送的监管单位
        $import['appType']=$res_import[0]['appType'];//报送类型
        $import['appStatus']=$res_import[0]['appStatus'];//业务状态
        $import['agentCode']=$res_import[0]['agentCode'];//申报企业代码,海关注册登记编号
        $import['agentName']=$res_import[0]['agentName'];//申报单位名称
        $import['assureCode']=$res_import[0]['assureCode'];//担保企业编号
        $import['emsNo']=$res_import[0]['emsNo'];//电商账册编号
        $import['areaCode']=$res_import[0]['areaCode'];//区内企业代码
        $import['areaName']=$res_import[0]['areaName'];//区内企业名称
        $import['customsCode']=$res_import[0]['customsCode'];//主管海关代码
        $import['ciqCode']=$res_import[0]['ciqCode'];//主管检验检疫机构代码
        $import['portCode']=$res_import[0]['portCode'];//口岸海关代码

        //sysshop_shop_other
        $project_o=app::get('sysshop')->model('shop_other');
        $res_o=$project_o->getList('*');
        $import['ebpCode']=$res_o[0]['ebpCode'];//电商平台代码
        $import['ebpName']=$res_o[0]['ebpName'];//电商平台名称

        //???sysshop_shop_sign
        $project_s=app::get('sysshop')->model('shop_sign');
        $res_s=$project_s->getList('*',['sign_id'=>$res_infor[0]['signID']]);
        $import['signCompany']=$res_s[0]['signCompany'];//承运企业代码
        $import['signCompanyName']=$res_s[0]['signCompanyName'];//承运企业名称
        //var_dump($weight);exit;
        //$log['goodsInfo']=$res_det[0]['sku_title'];//sku_title  主要货物信息

        $time=date('YmdHis',time());
        $str=substr(strval(rand(100000,999999)),1,5);
        $import['preEntryNo']='IQJMJK'.$time.$str;//???企业申报单号

        //写死的
        $import['trafMode']='7';//运输方式代码
        $import['wrapType']='2';//包装种类
        $import['ieDate']=date('Ymd',time());

        $else_m['tid']=$tid;
        //获取支付流水号
        $object_p=app::get('ectools')->model('payments');
        $row_p='trade_no';
        $res2=$object_p->getList($row_p,['payment_id'=>$res_pay[0]['payment_id']]);//二维数组
        $else_m['payTransactionId']=$res2[0]['trade_no'];;//--cur_money  支付金额
        $else_m['logisticsNo']=$res_del[0]['logi_no'];
        $else_m['copNo']=$res_pay[0]['payment_id'];

        $arr[0]=$log;
        $arr[1]=$import;
        $arr[2]=$res_del[0]['shop_id'];
        $arr[3]=$guids;
        $arr[4]=$else_m;
        return $arr;

    }


    //判断是否为保税
    public function gettax($params){
        if(array_key_exists('payment_id', $params)){
            $object1=app::get('ectools')->model('trade_paybill');
            $row1='tid';
            $res1=$object1->getList($row1,['payment_id'=>$params['payment_id']]);//二维数组
            $tid= $tid=$res1[0]['tid'];
        }else{
            $tid=$params['tid'];
        }
        //systrade_trade
        $object_tra=app::get('systrade')->model('trade');
        $row_tra='tax';
        $res_tra=$object_tra->getRow($row_tra,['tid'=>$tid]);//二维数组

        //判断是否为保税交易
        if($res_tra['tax']==2){
            return true;
        }

    }

    /**
     * @brief 获取商品信息
     *
     * @param $shopId 店铺编号
     * @param $status 更改的店铺状态
     *
     * @return  bool
     */
    public function getGoods($tid)
    {
        //$tid='1512082051180339';
        //商品信息,sysitem_item,sysshop_area,可能无country信息
        $goods=[];
        //systrade_order
        $object_o=app::get('systrade')->model('order');
        $row_o='oid,item_id,sku_id,price,num,total_fee';
        $res_o=$object_o->getList($row_o,['tid'=>$tid]);//二维数组

        foreach($res_o as $kk=>$val){
            $goods[$kk]['itemNo']=$val['sku_id'];//??? 电商平台自定义的商品货号（SKU）
            $goods[$kk]['qty']=$val['num'];//--store  商品实际数量。
            $goods[$kk]['qty2']=$val['num'];//--store  商品实际数量。
            $goods[$kk]['price']=$val['price'];//--price  单价。赠品单价填写为“0”
            $goods[$kk]['totalPrice']=$val['total_fee'];//总价，单价乘以数量

            //var_dump($val['item_id']);exit;
            //sysyitem_item
            $object_it=app::get('sysitem')->model('item');
           // $row_it='brand_id,area_id,ciqGno,ciqGmodel,gcode,gname,gmodel,unit,qty1,unit1,itemRecordNo,unit2';
            $row_it='brand_id,bn,area_id,ciqGno,ciqGmodel,gcode,gname,gmodel,unit,netWeight';
            $res_it=$object_it->getList('*',['item_id'=>$val['item_id']]);//二维数组

            $goods[$kk]['itemNo']=$res_it[0]['bn'];//??? 电商平台自定义的商品货号（SKU）
            $goods[$kk]['ciqGno']=$res_it[0]['ciqGno'];//--ciqGno  检验检疫商品备案号，保税进口必填。
            $goods[$kk]['ciqGmodel']=$res_it[0]['ciqGmodel'];//--ciqGmodel  检验检疫规格型号，保税进口必填。
            $goods[$kk]['gcode']=$res_it[0]['gcode'];//--gcode  商品编码，符合《中华人民共和国海关进出品税则》内列明的 10 位税号
            $goods[$kk]['itemName']=$res_it[0]['gname'];//--gname  销售商品的中文名称
            $goods[$kk]['gname']=$res_it[0]['gname'];//--gname 商品名称,商品名称应据实填报，与电子订单一致。
            $goods[$kk]['gmodel']=$res_it[0]['gmodel'];//--gmodel  海关规格型号，包括：品牌、规格、型号等
            /*$goods[$kk]['unit']=035;  //千克为035 计量单位，填写海关标准的参数代码，参照《JGS-20海关业务代码集
            $goods[$kk]['unit2']=035; //千克为035*/
            //$goods[$kk]['unit1']='035'; //千克035,件011
            $goods[$kk]['unit']=$res_it[0]['unit'];//--unit  计量单位，填写海关标准的参数代码，参照《JGS-20海关业务代码集》- 计量单位代码。
            $goods[$kk]['unit2']=$res_it[0]['unit2'];//？？？--unit  计量单位，填写海关标准的参数代码，参照《JGS-20海关业务代码集》- 计量单位代码。
            $goods[$kk]['qty1'] = $val['num']*$res_it[0]['netWeight'];//--qty1  法定数量,按照商品编码规则对应的法定计量单位的实际数量填写。
            $goods[$kk]['unit1']=$res_it[0]['unit1'];//035 千克--unit1  法定单位,海关标准的参数代码	《JGS-20  海关业务代 码集》-  计量单位代码
            $goods[$kk]['itemRecordNo']=$res_it[0]['bn'];//--itemRecordNo  账册备案料号,保税进口必填。

            //syscategory_brand
            $object_ba=app::get('syscategory')->model('brand');
            $row_ba='brand_name';
            $res_ba=$object_ba->getList($row_ba,['brand_id'=>$res_it[0]['brand_id']]);//二维数组

            if(empty($res_ba[0]['brand_name'])){
                $goods[$kk]['brand']='无';//--brand_id --brand_name 品牌，没有填“无”
            }else{
                $brand=explode('/',$res_ba[0]['brand_name']);
                $goods[$kk]['brand']=$brand[0];//--brand_id --brand_name 品牌，没有填“无”
            }
            //var_dump($res_it[0]['area_id']);exit;
            //sysshop_area
            if(!empty($res_it[0]['area_id'])){
                $object_ar=app::get('sysshop')->model('area');
                $row_ar='cus_code';
                $res_ar=$object_ar->getList($row_ar,['area_id'=>$res_it[0]['area_id']]);//二维数组
                $goods[$kk]['country']=$res_ar[0]['cus_code'];//--area_id  --cus_code  原产国，填写海关标准的参数代码，参照《JGS-20海关业务代码集》-国家（地区）代码表。
            }

        }
        return $goods;
    }
    /**
     * @brief  guid36 位
     *
     * @param $shopId 店铺编号
     * @param $status 更改的店铺状态
     *
     * @return  bool
     */

     protected function getGuid(){
        if(function_exists('com_create_guid')){
            return com_create_guid();//window下
        }else{//非windows下
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 andup.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);//字符 "-"
            /*$uuid = chr(123)//字符 "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);//字符 "}"*/
            $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }

}
