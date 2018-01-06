<?php

/**
 *  报文相关
 */
class sysshop_xml_message{
    //生成报文
    public function get_message($params){
        $type=$params['type'];
        if($type==1){
            $res_p=app::get('ectools')->model('trade_paybill')->getList('payment_id',['tid'=>$params['tid']]);
            if($res_p[0]['payment_id']){
                $params['payment_id']=$res_p[0]['payment_id'];
            }
        }
        $res=app::get('sysshop')->rpcCall('xml.message.create',$params);
        return $res;
    }
    //报文回执信息
    public function get_mess($tid){
        $arr_t=[];//判断报文信息是否显示
        $object_tra=app::get('systrade')->model('trade');
        $row_tra='tax';
        $res_tra=$object_tra->getList($row_tra,['tid'=>$tid]);//二维数组
        //判断是否为保税交易
        if($res_tra[0]['tax']==2){
            $arr_t[1]['type']=0;
            $arr_t[2]['type']=0;
            $arr_t[3]['type']=0;
            $arr_t[4]['type']=0;

            $project_e = app::get('sysshop')->model('elsem');
            $res_m = $project_e->getList('*', ['tid'=>$tid]);
            if(!empty($res_m[0])){
                foreach($res_m as $kk=>$vv){
                    $i=$vv['type'];
                    $arr_t[$i]['type']=$i;//return $arr_t[$i];
                    switch ($vv['status']){
                        case 0:
                            if($vv['ex_type']==0){
                                $arr_t[$i]['status']=0;
                            }else{
                                $arr_t[$i]['status']=4;     //报文申报中
                            }
                            break;
                        case 1:
                            $arr_t[$i]['status']=1;
                            $arr_t[$i]['info']=$vv['platformInfo'];
                            break;
                        case 2:
                            $arr_t[$i]['status']=2;
                            $arr_t[$i]['info']=$vv['customInfo'];
                            break;
                        case 3:
                            $arr_t[$i]['status']=3;
                            $arr_t[$i]['info']=$vv['checkoutInfo'];
                            break;
                        case 5:
                            $arr_t[$i]['status']=5;
                            break;
                    }
                }
            }
        }
        return $arr_t;
    }
    //zip文件下载
    public function downfile($fileurl)
    {
        ob_start();
        $filename=$fileurl;
        $date=date("YmdHis");
        header( "Content-type:application/zip ");//指定下载文件类型
        header( "Accept-Ranges:bytes ");//返回文件的的大小是按照字节进行计算
        header( "Content-Disposition:  attachment;  filename= {$date}.zip");//指定下载文件的描述
        //header('Content-Length: ' . filesize($filename)); //下载文件大小
        $size=readfile($filename);
        header( "Accept-Length: " .$size);//指定文件的大小
    }
    //文件复制
    public function file_copy($filter,$shop_id)
    {
        //选取要获取文本信息，已经下载的报文不再重新获取，不删除原有报文信息，重新生成报文
        $project_e = app::get('sysshop')->model('elsem');
        $excel_all = 1;
        if(!empty($filter['tid'])){
            $data_arr = $filter;
            $excel_all = 0;
        }
        $data_arr['ex_type']=0;
        $res_m = $project_e->getList('tid,name,type,status',$data_arr);

        // return $filter;
        if (!empty($res_m[0])) {
            $copy_p=[];
            foreach ($res_m as $kk => $vv) {
                if($vv['status']!=5){
                    if ($vv['type'] == 1) {
                        $MessageType = 'CEB311';
                    } elseif ($vv['type'] == 2) {
                        $MessageType = 'CEB411';
                    } elseif ($vv['type'] == 3) {
                        $MessageType = 'CEB511';
                    } elseif ($vv['type'] == 4) {
                        $MessageType = 'CEB621';
                    }
                    $pathf = 'upload/xml/' . $shop_id . '/' . $MessageType . '/' . $vv['name'];
                    //$pathf = '../upload/xml/' . $shop_id . '/' . $MessageType . '/' . $vv['name'];
                    if(file_exists($pathf)){
                        //文件复制，复制到制定路径
                        $url_c = 'copy/' . $shop_id . '/' . $MessageType;
                        //$url_c = '../copy/' . $shop_id . '/' . $MessageType;
                        if (!file_exists($url_c)) {
                            mkdir($url_c, 0777, true);
                        }
                        $copy = $url_c . '/' . $vv['name'];
                        if (!copy($pathf, $copy)) {           //$pathf为文件原路径
                            return '报文复制失败!';
                        } else {
                            $copy_p[] = $shop_id . '/' . $MessageType . '/' . $vv['name'];
                            if($excel_all){
                                $copy_tid[] = $vv['tid'];
                            }
                             unlink($pathf);        //删除原文件,测试屏蔽 ,删除失败
                        }
                    }
                }
            }
            if(!empty($copy_p)){
                $res[0] = $copy_p;
                if($excel_all){
                    $res[1] = $copy_tid;
                }
                return $res;
            }
        }
        return false;
    }
    //文件打包
    public function create_zip($files = array(), $destination = '', $overwrite = false)
    {

        if (file_exists($destination) && !$overwrite) {
            return false;
        }
        $valid_files = array();
        $fi = array();//return $files;
        if (is_array($files)) {
            foreach ($files as $file) {
                //$fil = IA_ROOT . '/addons/upgrade/static/system/' . $file;
                $fil = 'copy/' . $file;//return $fil;
                if (file_exists($fil)) {
                    $valid_files[] = $fil;
                    $fi[] = $file;
                }
            }
        }
        if (count($valid_files)) {
            $zip = new ZipArchive();//return $file;
            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            foreach ($valid_files as $k => $file) {
                $zip->addFile($file, $fi[$k]);    //文件保存路径
            }
            $zip->close();
            return file_exists($destination);
        } else {
            return false;
        }
    }
    //删除指定目录文件夹,文件夹所在路径
    public function remove_folders($path,$shop_id)
    {
        //解压前期装备工作
        $handle = opendir($path);
        $arr_d = [];
        while ($file = readdir($handle)) {
            $url = "/$shop_id$/";
            $num_matches = preg_match($url, $file);//正则匹配
            if ($num_matches > 0) {
                $arr_d[] = $file;
            }
        }
        foreach ($arr_d as $v_d) {
            if ($v_d) {
                $arr_file = array();
                $this->tree($arr_file, $path . $v_d);
                foreach ($arr_file as $v) {
                    if ($v) {
                        @unlink($path . $v_d . '/' . $v);
                    }
                    $this->deldir($path . $v_d);
                }
            }
        }
    }
    //获取文件夹下面的所有文件路径，存放在$arr_file数组中
    private function tree(&$arr_file, $directory, $dir_name = '')
    {
        //读取文件目录，返回 Directory 类
        $mydir = dir($directory);
        while ($file = $mydir->read()) {
            if ((is_dir("$directory/$file")) AND ($file != ".") AND ($file != "..")) {
                $this->tree($arr_file, "$directory/$file", "$dir_name/$file");
            } else if (($file != ".") AND ($file != "..")) {
                $arr_file[] = substr("$dir_name/$file", stripos('/', "$dir_name/$file") + 1);
            }
        }
        $mydir->close();
    }

    private function deldir($dir)
    {
        //打开文件夹并读取内容
        $dh = opendir($dir);
        //读取打开文件夹中的内容，返回文件夹名称
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                //判断给定文件名是否是一个文件夹，不是文件夹则删除文件
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldir($fullpath);
                }
            }
        }
        closedir($dh);
        //删除空的文件夹
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }
}


