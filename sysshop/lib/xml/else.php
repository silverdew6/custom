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
        $pay                        = [];
        $else                       = [];
        $xml_arr                    = [];
        $guids                      = [];
        $else_m                     = [];        //报文入库所需参数

        $object1                            = app::get('ectools')->model('trade_paybill');                  //ectolls_trade_paybill
        $object_tra                         = app::get('systrade')->model('trade');                         //systrade_trade
        $object_c                           = app::get('sysuser')->model('card');
        $object2                            = app::get('ectools')->model('payments');                       //ectools_payments
        $object_infor                       = app::get('sysshop')->model('shop_infor');                     //shop_id  -->sysshop_shop_infor
        $project_o                          = app::get('sysshop')->model('shop_other');                     //sysshop_shop_other
        $object_pay                         = app::get('sysshop')->model('shop_pay');                       //sysshop_shop_pay
        $project_del                        = app::get('syslogistics')->model('delivery');                  //物流单号

        $row1                               = 'tid,payed_time';
        $row_tra                            = 'shop_id,user_id,payment,total_fee,post_fee,receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,receiver_mobile,tax,card_id,zonghe_ratemoney,use_coupon_money';
        $row2                               = 'cur_money,trade_no';
        $row_del                            = 'logi_no';

        $res1                               = $object1->getList($row1,['payment_id'=>$payment_id]);//二维数组
        $tid                                = $res1['0']['tid'];
        $res_tra                            = $object_tra->getList($row_tra,['tid'=>$tid]);//二维数组
        $payerName                          = trim($res_tra[0]['receiver_name']);//支付人姓名
        $card                               = $object_c->getRow('card_id',['name'=>$payerName]);
        $res2                               = $object2->getList($row2,['payment_id'=>$payment_id]);//二维数组
        $shop_id                            = $res_tra[0]['shop_id'];
        $res_infor                          = $object_infor->getList('*',['shop_id'=>$shop_id]);//二维数
        $res_o                              = $project_o->getList('*');
        $pay_id                             = $res_infor[0]['payID'];
        $res_pay                            = $object_pay->getList('*',['pay_id'=>$pay_id]);//二维数组
        $res_del                            = $project_del->getList($row_del,['tid'=>$tid]);

        //判断是否为保税交易
        if($res_tra[0]['tax']!=2){
            return false;
        }



        if($card['card_id']){
            $payerIdNumber           = $card['card_id'];//--user_id 支付人证件号码
        }else{
            if($res_tra[0]['card_id'] != ''){
                $payerIdNumber       = trim($res_tra[0]['card_id']);
            }
        }
        $payTransactionId           = $res2[0]['trade_no'];
        $ebpCode                    = $res_o[0]['ebpCode'];//电商平台代码
        $ebpName                    = $res_o[0]['ebpName'];//电商平台名称
        $receiver_mobile            = $res_tra[0]['receiver_mobile'];


        //头信息数组
        $else['MessageID']                  = $this->getGuid();

        $else['OrgCode']                    = $res_infor[0]['OrgCode'];                 //企业组织机构代码或统 一社会信息代码
        $else['CopCode']                    = $res_infor[0]['CopCode'];                 //企业海关注册代码
        $else['CopName']                    = $res_infor[0]['CopName'];                 //报文传输的企业海关注册名称
        $else['SenderID']                   = $res_infor[0]['SenderID'];                //企业客户端 ID 号
        $else['ReceiverDepartment']         = $res_infor[0]['ReceiverDepartment'];      //填写本报文发送的监管单位

        $else['guid']                       = $this->getGuid();
        $else['appType']                    = $res_infor[0]['appType'];                 //企业报送类型

        $else['appStatus']                  = $res_infor[0]['appStatus'];               //业务状态
        $else['orderNo']                    = $tid;                                     //--tid  订单编号
        $else['ebpCode']                    = $ebpCode;                     //电商平台海关注册登记编号
        $else['ebpName']                    = $ebpName;                     //电商平台海关注册登记名称
        $else['ebcCode']                    = $res_infor[0]['ebcCode'];                 //电商企业海关注册登记编号
        $else['ebcName']                    = $res_infor[0]['ebcName'];                 //电商企业的海关注册登记名称
        $else['goodsValue']                 = $res_tra[0]['total_fee'];                 //--total_fee  商品实际成交价，含非现金抵扣金额
        if(empty($res_tra[0]['post_fee'])){
            $else['freight']                = 0;                                        //--post_fee  不包含在商品价格中的运杂费，无则填 写“0”
        }else{
            $else['freight']                = $res_tra[0]['post_fee'];                  //--post_fee  不包含在商品价格中的运杂费，无则填 写“0”
        }
        if(empty($res_tra[0]['use_coupon_money'])){
            $else['discount']               = 0;                                        //--use_coupon_money  使用积分、虚拟货币、代金券等非现金支付金额，无则填写“0”
        }else{
            $else['discount']               = $res_tra[0]['use_coupon_money'];          //--use_coupon_money  使用积分、虚拟货币、代金券等非现金支付金额，无则填写“0”
        }
        if(empty($res_tra[0]['zonghe_ratemoney'])){
            $else['taxTotal']               = 0;                                        //--zonghe_ratemoney  企业预先代扣的税款金额，无则填“0”
        }else{
            $else['taxTotal']               = $res_tra[0]['zonghe_ratemoney'];          //--zonghe_ratemoney  企业预先代扣的税款金额，无则填“0”
        }
        $else['acturalPaid']                = $res_tra[0]['payment'];                   //--payment  商品价格+运杂费+代扣税款-非现金抵扣金额，与支付凭证的支付金额一致
        $else['buyerRegNo']                 = $res_tra[0]['user_id'];                    //????--user_id  订购人的交易平台注册号
        $else['buyerName']                  = $payerName;                              // --user_id  sysuer_user  username订购人的真实姓名
        $else['buyerIdNumber']              = $payerIdNumber;                           //???--user_id  sysuer_user  usercard 订购人的身份证号
        $else['payCode']                    = $res_pay[0]['payCode'];                   //支付企业的海关注册登记编号
        $else['payName']                    = $res_pay[0]['payName'];                       //支付企业在海关注册登记的企业名称。
        $else['payTransactionId']           = $payTransactionId;                         //--trade_no   支付企业唯一的支付流水号
        $else['consignee']                  = $res_tra[0]['receiver_name'];                 //--receiver_name  收货人姓名，必须与电子运单的收货人姓名一致。
        $else['consigneeTelephone']         = $receiver_mobile;               //--receiver_mobile  or  receiver_phone收货人联系电话，必须与电子运单的收货人电话一致。
        if(empty($res_tra[0]['receiver_district'])){
            $else['consigneeAddress']       = $res_tra[0]['receiver_state'].$res_tra[0]['receiver_city'].$res_tra[0]['receiver_address'];//--receiver_address收货地址
        }else{
            $row_na             =   'parent';
            $project_na         = app::get('sysshop')->model('shop_nation');
                $res_nab        = $project_na->getList($row_na,['district'=>$res_tra[0]['receiver_district']]);
                if($res_nab[0]['parent']){
                    $res_nac    = $project_na->getList('city,parent',['id'=>$res_nab[0]['parent']]);        //区->市
                    $res_nap    = $project_na->getList('province',['id'=>$res_nac[0]['parent']]);                    //市->省
                    $else['consigneeAddress']= $res_nap[0]['province'].$res_nac[0]['city'].$res_tra[0]['receiver_district'].$res_tra[0]['receiver_address'];//--receiver_address收货地址
            }
        }
        if(!empty($res_tra[0]['receiver_district'])){
            $row_na             = 'code';
            $project_na         = app::get('sysshop')->model('shop_nation');
            $res_na             = $project_na->getList($row_na,['district'=>$res_tra[0]['receiver_district']]);
            $else['consigneeDistrict']      = $res_na[0]['code'];//???--receiver_zip 邮编 收货人行政区划代码
        }


        $pay['MessageID']                   = $this->getGuid();

        $pay['OrgCode']                     = $res_pay[0]['OrgCode'];//企业组织机构代码或统 一社会信息代码
        $pay['CopCode']                     = $res_pay[0]['CopCode'];//企业海关注册代码
        $pay['CopName']                     = $res_pay[0]['CopName'];//报文传输的企业海关注册名称
        $pay['SenderID']                    = $res_pay[0]['SenderID'];//企业客户端 ID 号
        $pay['ReceiverDepartment']          = $res_pay[0]['ReceiverDepartment'];//填写本报文发送的监管单位
        $pay['guid']                        = $this->getGuid();
        $pay['appType']                     = $res_pay[0]['appType'];//报送类型
        $pay['appStatus']                   = $res_pay[0]['appStatus'];//业务状态
        $pay['payCode']                     = $res_pay[0]['payCode'];//支付企业代码
        $pay['payName']                     = $res_pay[0]['payName'];//支付企业名称
        $pay['payTransactionId']            = $payTransactionId;//?  --trade_no  支付交易编号
        $pay['orderNo']                     = $tid;//订单编号
        $pay['ebpCode']                     = $ebpCode;//电商平台代码
        $pay['ebpName']                     = $ebpName;//电商平台名称
        $pay['payerIdNumber']               = $payerIdNumber;
        $pay['payerName']                   = $payerName;//支付人姓名
        $pay['telephone']                   = $receiver_mobile;                         //默认购买人为收货人
        $pay['amountPaid']                  = $res2[0]['cur_money'];;//--cur_money  支付金额
        $time1                              = $res1['0']['payed_time'];
        $pay['payTime']                     = date('YmdHis',$time1);//--payed_time  支付时间


        $else_m['tid']                      = $tid;
        $else_m['payTransactionId']         = $payTransactionId;
        $else_m['logisticsNo']              = $res_del['logi_no'];
        $else_m['copNo']                    = $payment_id;

        $guids[0]                           = $else['MessageID'];
        $guids[1]                           = $pay['MessageID'];

        $xml_arr[0]                         = $else;       //返回订单数组
        $xml_arr[1]                         = $pay;        //返回支付数组
        $xml_arr[2]                         = $shop_id ;//返回店铺信息
        $xml_arr[3]                         = $guids;
        $xml_arr[4]                         = $else_m;
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
        $log            = [];                                            //物流信息511
        $import         = [];                                               //进口信息
        $arr            = [];
        $guids          = [];
        $else_m         = [];    //报文信息

        $object_tra                     = app::get('systrade')->model('trade');             //systrade_trade
        $object_c                       = app::get('sysuser')->model('card');
        $project_del                    = app::get('syslogistics')->model('delivery');                //syslogistics_delivery
        $project_na                     = app::get('sysshop')->model('shop_nation');
        $project_det                    = app::get('syslogistics')->model('delivery_detail');                //syslogistics_delivery_detail
        $project_infor                  = app::get('sysshop')->model('shop_infor');
        $project_log                    = app::get('sysshop')->model('shop_log');            //sysshop_shop_log
        $project_pay                    = app::get('ectools')->model('trade_paybill');           //ectools_trade_paybill
        $project_pb                     = app::get('ectools')->model('trade_paybill');   //ectools_trade_paybill
        $project_p                      = app::get('ectools')->model('payments'); //ectools_payments
        $project_o                      = app::get('sysshop')->model('shop_other');//sysshop_shop_other
        $project_s                      = app::get('sysshop')->model('shop_sign');//???sysshop_shop_sign
        $object_p                       = app::get('ectools')->model('payments');//获取支付流水号
        $project_import                 = app::get('sysshop')->model('shop_import');//sysshop_shop_import

        $row_tra                        = 'receiver_name,receiver_mobile,tax,card_id';
        $row_del                        = 'delivery_id,tid,user_id,shop_id,post_fee,is_protect,logi_no,receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_mobile';
        $row_det                        = 'oid,number,sku_title';
        $row_infor                      = 'ebcCode,ebcName,logisticsID,declarationID,signID';
        $row_pb                         = 'payment_id';
        $row_p                          = 'trade_no';
//        $row_na='code,parent';

        $res_tra                        = $object_tra->getRow($row_tra,['tid'=>$tid]);//二维数组
        $receiver_name      = trim($res_tra['receiver_name']);
        $card                           = $object_c->getRow('card_id',['name'=>$receiver_name]);
        $res_del                        = $project_del->getList($row_del,['tid'=>$tid]);
        $res_det                        = $project_det->getList($row_det,['delivery_id'=>$res_del[0]['delivery_id']]);
        $shop_id            = $res_del[0]['shop_id'];
        $res_infor                      = $project_infor->getList($row_infor,['shop_id'=>$shop_id]);
        $res_log                        = $project_log->getList('*',['log_id'=>$res_infor[0]['logisticsID']]);
        $res_pay                        = $project_pay->getList('payment_id',['tid'=>$tid]);
        $res_pb                         = $project_pb->getList($row_pb,['tid'=>$tid]);
        $res_p                          = $project_p->getList($row_p,['payment_id'=>$res_pb[0]['payment_id']]);
        $res_o                          = $project_o->getList('*');
        $res_s                          = $project_s->getList('*',['sign_id'=>$res_infor[0]['signID']]);
        $payment_id         = $res_pay[0]['payment_id'];
        $res2                           = $object_p->getList($row_p,['payment_id'=>$payment_id]);//二维数组
        $res_import                     = $project_import->getList('*',['import_id'=>$res_infor[0]['declarationID']]);

        //判断是否为保税交易
        if($res_tra['tax']!=2){
            return false;
        }

        $num                            = 0;
        $weight                         = 0;
        $nweight                        = 0;
        $str                            = '';
        foreach($res_det as $kk=>$val){
            $num += $val['number'];
            $row_or                     = 'item_id,total_weight';
            $project_or                 = app::get('systrade')->model('order');
            $res_or                     = $project_or->getList($row_or,['oid'=>$val['oid']]);
            $item_id                    = $res_or[0]['item_id'];
            $project_it                 = app::get('sysitem')->model('item');//sysitem_item,17/07/07
            $res_it                     = $project_it->getList('gname,grossWeight,netWeight',['item_id'=>$item_id]);
            $weight += $res_it[0]['grossWeight']*$val['number'];
            $nweight += $res_it[0]['netWeight']*$val['number'];
            $str.=$res_it[0]['gname'].':'.$val['number'].'  ';
        }
        //省
        if(!empty($res_del[0]['receiver_state'])){
            $res_nap                    = $project_na->getList('code,province',['province'=>$res_del[0]['receiver_state']]);
            if($res_nap[0]){
                $consigneeProvince      = $res_nap[0]['code'];//--receiver_state sysshop_shop_nation 收货人地址（省），按行政区划代码填写收货人地址所在省级行政区划代码
            }
        }
        //市
        if(!empty($res_del[0]['receiver_city'])){
            $res_nac                    = $project_na->getList('code,city,parent',['city'=>$res_del[0]['receiver_city']]);
            if($res_nac[0]){
                $consigneeCity          = $res_nac[0]['code'];
            }
        }
        //区
        if(!empty($res_del[0]['receiver_district'])){
            $res_nad                    = $project_na->getList('code,district,parent',['district'=>$res_del[0]['receiver_district']]);
            if($res_nad[0]){
                $consigneeDistrict      = $res_nad[0]['code'];//--receiver_district收货人地址（区）
                //有区无市
                if(!isset($consigneeCity) && isset($consigneeDistrict)){
                    $res_nac            = $project_na->getList('code,city,parent',['id'=>$res_nad[0]['parent']]);
                    $consigneeCity      = $res_nac[0]['code'];
                }
            }
        }
        //有市无省
        if(!isset($consigneeProvince) && isset($consigneeCity)){
            $res_nap                    = $project_na->getList('code,province',['id'=>$res_nac[0]['parent']]);
            $consigneeProvince          = $res_nap[0]['code'];
        }
        $consigneeAddress               = $res_nap[0]['province'].$res_nac[0]['city'].$res_nad[0]['district'].$res_del[0]['receiver_address'];
        if(empty($res_del[0]['is_protect'])){
            $insuredFee                 = '0';//保价费
        }else{
            $insuredFee                 = $res_del[0]['is_protect'];//--is_protect  是否保价  保价费
        }
        if($card['card_id']){
            $buyerIdNumber              = $card['card_id'];//--user_id 支付人证件号码
        }else{
            if($res_tra['card_id'] != ''){
                $buyerIdNumber          = trim($res_tra['card_id']);
            }
        }
        $logisticsCode                  = $res_log[0]['logisticsCode'];//物流企业代码
        $logisticsName                  = $res_log[0]['logisticsName'];//物流企业名称
        $logisticsNo                    = $res_del[0]['logi_no'];//--logi_no  物流运单编号
        $freight                        = $res_del[0]['post_fee'];//--post_fee  运费
        $time                           = date('YmdHis',time());
        $str1                           = substr(strval(rand(100000,999999)),1,5);
        $preEntryNo                     = 'IQJMJK'.$time.$str1;//???企业申报单号
        //物流
        $log['MessageID']           = $this->getGuid();

        $log['OrgCode']             = $res_log[0]['OrgCode'];//企业组织机构代码或统 一社会信息代码
        $log['CopCode']             = $res_log[0]['CopCode'];//企业海关注册代码
        $log['CopName']             = $res_log[0]['CopName'];//报文传输的企业海关注册名称
        $log['SenderID']            = $res_log[0]['SenderID'];//企业客户端 ID 号
        $log['ReceiverDepartment']  = $res_log[0]['ReceiverDepartment'];//填写本报文发送的监管单位

        $log['guid']                = $this->getGuid();
        $log['appType']             = $res_log[0]['appType'];//报送类型

        $log['appStatus']           = $res_log[0]['appStatus'];//业务状态
        $log['logisticsCode']       = $logisticsCode;//物流企业代码
        $log['logisticsName']       = $logisticsName;//物流企业名称
        $log['logisticsNo']         = $logisticsNo;//--logi_no  物流运单编号
        $log['freight']             = $freight;//--post_fee  运费
        $log['insuredFee']          = $insuredFee;
        $log['weight']              = $weight;//--total_weight  //？毛重（Kg）
        $log['goodsInfo']           = $str;
        $log['consignee']           = $res_del[0]['receiver_name'];//--receiver_name 收货人名称
        $log['consigneeAddress']    = $consigneeAddress;//--receicer_address  收件人地址
        $log['consigneeTelephone']  = $res_del[0]['receiver_mobile'];//--receiver_mobile  收件人电话
        $log['consigneeProvince']   = $consigneeProvince;
        $log['consigneeCity']       = $consigneeCity;//--receiver_district收货人地址（区）
        $log['consigneeDistrict']   = $consigneeDistrict;//--receiver_district收货人地址（区）
        //进口
        $import['MessageID']        = $this->getGuid();

        $import['OrgCode']          = $res_import[0]['OrgCode'];//企业组织机构代码或统 一社会信息代码
        $import['CopCode']          = $res_import[0]['CopCode'];//企业海关注册代码
        $import['CopName']          = $res_import[0]['CopName'];//报文传输的企业海关注册名称
        $import['SenderID']         = $res_import[0]['SenderID'];//企业客户端 ID 号
        $import['ReceiverDepartment']=$res_import[0]['ReceiverDepartment'];//填写本报文发送的监管单位

        $import['guid']             = $this->getGuid();
        $import['appType']          = $res_import[0]['appType'];//报送类型

        $import['appStatus']        = $res_import[0]['appStatus'];//业务状态
        $import['orderNo']          = $tid;//订单编号
        $import['ebpCode']          = $res_o[0]['ebpCode'];//电商平台代码
        $import['ebpName']          = $res_o[0]['ebpName'];//电商平台名称
        $import['ebcCode']          = $res_infor[0]['ebcCode'];//电商企业代码
        $import['ebcName']          = $res_infor[0]['ebcName'];//电商企业名称
        $import['logisticsNo']      = $logisticsNo;//运单号
        $import['logisticsCode']    = $logisticsCode;//物流企业代码
        $import['logisticsName']    = $logisticsName;//物流企业名称
//        $import['payNo']=$res_infor[0]['ebcCode'].'ICBC'.$res_p[0]['trade_no'];//支付交易编号
        $import['payNo']            = $res_p[0]['trade_no'];
        $import['copNo']            = $tid;//$payment_id;
        $import['preEntryNo']       = $preEntryNo;//???企业申报单号
        $import['assureCode']       = $res_import[0]['assureCode'];//担保企业编号
        $import['emsNo']            = $res_import[0]['emsNo'];//电商账册编号
        //$import['copNo']=$tid;//企业内部编号
        $import['declTime']         = date('Ymd',time());//申报日期
        $import['customsCode']      = $res_import[0]['customsCode'];//主管海关代码
        $import['ciqCode']          = $res_import[0]['ciqCode'];//主管检验检疫机构代码
        $import['portCode']         = $res_import[0]['portCode'];//口岸海关代码
        $import['ieDate']           = date('Ymd',time());
        $import['buyerIdNumber']    = $buyerIdNumber;
        $import['buyerName']        = $receiver_name;//支付人姓名
        $import['buyerTelephone']   = trim($res_tra['receiver_mobile']);
        $import['consigneeAddress'] = $consigneeAddress;//收件人地址
        $import['consigneeCity']    = $consigneeCity;//收件人所在城市行政区划代码
        $import['agentCode']        = $res_import[0]['agentCode'];//申报企业代码,海关注册登记编号
        $import['agentName']        = $res_import[0]['agentName'];//申报单位名称
        $import['areaCode']         = $res_import[0]['areaCode'];//区内企业代码
        $import['areaName']         = $res_import[0]['areaName'];//区内企业名称
        $import['trafMode']         = '7';//运输方式代码
        $import['shipName']         = "“-”";//运输工具名称
        $import['freight']          = $freight;//运费
        $import['insuredFee']       = $log['insuredFee'];//保价费
        $import['wrapType']         = '2';//包装种类
        $import['grossWeight']      = $weight;//毛重（Kg）
        $import['netWeight']        = $nweight;//净重（Kg）
        $import['signCompany']      = $res_s[0]['signCompany'];//承运企业代码
        $import['signCompanyName']  = $res_s[0]['signCompanyName'];//承运企业名称

        $guids[0]               = $log['MessageID'];
        $guids[1]               = $import['MessageID'];

        $else_m['tid']              = $tid;
        $else_m['payTransactionId'] = $res2[0]['trade_no'];//--cur_money  支付金额
        $else_m['logisticsNo']      = $logisticsNo;
        $else_m['copNo']            = $payment_id;

        $arr[0]                     = $log;
        $arr[1]                     = $import;
        $arr[2]                     = $shop_id;
        $arr[3]                     = $guids;
        $arr[4]                     = $else_m;
        return $arr;

    }

    //判断是否为保税
    public function gettax($params){
        if(array_key_exists('payment_id', $params)){
            $object1            = app::get('ectools')->model('trade_paybill');
            $row1               = 'tid';
            $res1               = $object1->getList($row1,['payment_id'=>$params['payment_id']]);//二维数组
            $tid                = $res1[0]['tid'];
        }else{
            $tid                = $params['tid'];
        }
        $object_tra             = app::get('systrade')->model('trade');
        $row_tra                = 'tax';
        $res_tra                = $object_tra->getRow($row_tra,['tid'=>$tid]);//二维数组

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
        $goods                  = [];

        $object_it              = app::get('sysitem')->model('item');
        $object_o               = app::get('systrade')->model('order');
        $object_ba              = app::get('syscategory')->model('brand');
        $object_ar              = app::get('sysshop')->model('area');

        $row_o                  = 'oid,item_id,sku_id,price,num,total_fee';
        $row_ba                 = 'brand_name';
        $row_ar                 = 'cus_code';

        $res_o=$object_o->getList($row_o,['tid'=>$tid]);//二维数组

        foreach($res_o as $kk=>$val){
            $res_it             = $object_it->getList('*',['item_id'=>$val['item_id']]);//二维数组
            $res_ba             = $object_ba->getList($row_ba,['brand_id'=>$res_it[0]['brand_id']]);//二维数组
            if(!empty($res_it[0]['area_id'])){
                $res_ar         = $object_ar->getList($row_ar,['area_id'=>$res_it[0]['area_id']]);//二维数组
            }
            $goods[$kk]['itemNo']           = $val['sku_id'];//??? 电商平台自定义的商品货号（SKU）
            $goods[$kk]['itemName']         = $res_it[0]['gname'];//--gname  销售商品的中文名称
            $goods[$kk]['unit']             = $res_it[0]['unit'];//--unit  千克035,件011 计量单位，填写海关标准的参数代码，参照《JGS-20海关业务代码集》- 计量单位代码。
            $goods[$kk]['qty']              = $val['num'];//--store  商品实际数量。
            $goods[$kk]['price']            = $val['price'];//--price  单价。赠品单价填写为“0”
            $goods[$kk]['totalPrice']       = $val['total_fee'];//总价，单价乘以数量
            $goods[$kk]['country']          = $res_ar[0]['cus_code'];//--area_id  --cus_code  原产国，填写海关标准的参数代码，参照《JGS-20海关业务代码集》-国家（地区）代码表。
            $goods[$kk]['ciqGno']           = $res_it[0]['ciqGno'];//--ciqGno  检验检疫商品备案号，保税进口必填。
            $goods[$kk]['gcode']            = $res_it[0]['gcode'];//--gcode  商品编码，符合《中华人民共和国海关进出品税则》内列明的 10 位税号
            $goods[$kk]['gmodel']           = $res_it[0]['gmodel'];//--gmodel  海关规格型号，包括：品牌、规格、型号等
            $goods[$kk]['ciqGmodel']        = $res_it[0]['ciqGmodel'];//--ciqGmodel  检验检疫规格型号，保税进口必填。
            if(empty($res_ba[0]['brand_name'])){
                $goods[$kk]['brand']        = '无';//--brand_id --brand_name 品牌，没有填“无”
            }else{
                $brand                      = explode('/',$res_ba[0]['brand_name']);
                $goods[$kk]['brand']        = $brand[0];//--brand_id --brand_name 品牌，没有填“无”
            }
            $goods[$kk]['itemRecordNo']     = $res_it[0]['bn'];//--itemRecordNo  账册备案料号,保税进口必填。
            $goods[$kk]['itemNo']           = $res_it[0]['bn'];//??? 电商平台自定义的商品货号（SKU）
            $goods[$kk]['gname']            = $res_it[0]['gname'];//--gname 商品名称,商品名称应据实填报，与电子订单一致。
            $goods[$kk]['qty1']             = $val['num']*$res_it[0]['netWeight'];//--qty1  法定数量,按照商品编码规则对应的法定计量单位的实际数量填写。
            $goods[$kk]['unit1']            = $res_it[0]['unit1'];//035 千克--unit1  法定单位,海关标准的参数代码	《JGS-20  海关业务代 码集》-  计量单位代码
            $goods[$kk]['qty2']             = $val['num'];//--store  商品实际数量。
            $goods[$kk]['unit2']            = $res_it[0]['unit2'];//？？？--unit 千克为035  计量单位，填写海关标准的参数代码，参照《JGS-20海关业务代码集》- 计量单位代码。
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
