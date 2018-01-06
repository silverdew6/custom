<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_export extends topshop_controller{

    public function view()
    {
        //导出方式 直接导出还是通过队列导出
        $pagedata['check_policy'] = 'download';

        $filetype = array(
            'csv'=>'.csv',
            'xls'=>'.xls',
            'xml'=>'.xml',
        );
        $pagedata['model'] = input::get('model');                       //tarde
        $pagedata['app'] = input::get('app');                           //systrade
        $pagedata['orderBy'] = input::get('orderBy');                     //tid
        $supportType = input::get('supportType');                         //NULL
        //支持导出类型
        if( $supportType && $filetype[$supportType] )
        {
            $pagedata['export_type'] = array($supportType=>$filetype[$supportType]);
        }
        else
        {
            $pagedata['export_type'] = $filetype;
        }

        return view::make('topshop/export/export.html', $pagedata);
    }

    //文件导出
    public function export()
    {

//        获取要导出订单的订单号
        if( input::get('filter') )
        {
            $arr_tid=json_decode(input::get('filter'), false, 512, JSON_BIGINT_AS_STRING);
        }
        if(is_object($arr_tid)){
            foreach($arr_tid as $key=>$val){
                $filter[$key]=$val;
            }
        }else{
            $filter=$arr_tid;
        }

        $filetype=input::get('filetype');  //要导出文本类型

        //从这里截取，获取报文信息 chx 17/07/18 start
        if($filetype=='xml'){

            //为全部导出时$filter=NULL,部分导出时为二维数组
            $shop_id=shopAuth::getShopId();
            $objPayment = kernel::single('sysshop_xml_message');
            //文件备份
            $copy_res = $objPayment->file_copy($filter,$shop_id);
//            echo "<pre>";
//            print_r($copy_res);
//            echo "</pre>";exit;

            $copy_p = $copy_res[0];

            if(!$copy_p){
                return response::make('无相关报文详情', 503);
            }
            //删除原有压缩包
            $url_y='copy/'.'xml.zip';
            if(file_exists($url_y)){
                unlink($url_y);
            }
            //文件压缩
            $files=$copy_p;
            $destination='copy/xml.zip';
            $reslut=$objPayment->create_zip($files,$destination);
            if(!$reslut){
                return response::make('文件打包失败', 503);
            }else{

                if(!empty($filter)){
                    $data=$filter['tid'];
                }else{
                    $data=$copy_res[1];
                }
                $tax_data['ex_type']=1;
                //数据库修改,报错
                if(!empty($data)){
                    $tax_ifter['tid|in']=$data;
                    app::get('sysshop')->model('elsem')->update($tax_data,$tax_ifter);
                }
                //删除备份
                $url=$_SERVER['DOCUMENT_ROOT'];
                $path=$url.'/copy/';//var_dump($path);exit;
                $objPayment->remove_folders($path,$shop_id);
                //下载文件夹到本地
                $path_z='copy/xml.zip';
                $objPayment->downfile($path_z);
            }
            exit;
        }
        //导出报文   end

        //$orderBy = input::get('orderBy');
        $orderBy = str_replace(';', '', input::get('orderBy'));
        $orderBy = str_replace('\'', '', $orderBy);                    //tid
        //echo '<pre>';print_r($orderBy);exit();
        $permission = [
            'systrade' =>['trade','order'],
            'sysclearing' =>['settlement','settlement_detail'],
        ];

        $app = input::get('app',false);                                //systrade
        $model = input::get('model',false);                             //trade
        //var_dump(shopAuth::getShopId());exit;
        //filetype
        if( input::get('name') && $app && $model && $permission[$app] && in_array($model,$permission[$app]) )
        {
            $model = $app.'_mdl_'.$model;
            $filter['shop_id'] = shopAuth::getShopId();
            try {
                kernel::single('importexport_export')->fileDownload(input::get('filetype'), $model, input::get('name'), $filter,$orderBy);
            }
            catch (Exception $e)
            {
                return response::make('导出参数错误', 503);
            }
        }
        else
        {
            return response::make('导出参数错误', 503);
        }
    }
}

