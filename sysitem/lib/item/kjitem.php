<?php
use Endroid\QrCode\Reader;
//use Endroid\QrCode\QrCode;

class sysitem_item_kjitem{

//    读取excel文件信息，如果没有则读取，备份信息
    public function getexcel($shop_id,&$pages,$search = '',$k=10){

        $path = getcwd()."/upload/excel/".$shop_id."/example.xls";//return $path;
        $arr = [];
        if(!file_exists( $path )) {
            $path = getcwd()."/upload/excel/".$shop_id."/copy/example.xls";
            if(!file_exists( $path )){
                return $arr;
            }
            $ret_arr['type']=1;             //备份
        }
        $data = new Reader();//创建对象
        $data->setOutputEncoding('UTF-8');//设置编码格式
        $res=$data->read($path);//读取excel文档
        if(!empty($res)){
            return $arr;
        }
        $numRows = $data->sheets[0]['numRows'];//读出一共几行
        $ret_arr['header'] = $data->sheets[0]['cells'][1];
        $ret_arr['total_found'] = $numRows-1;


        if(trim($search)==''){
            $m = $k*($pages-1)+2;
            $j = $k*($pages)+1;
            if($j < $numRows){
                $n = $j;
            }else{
                $n = $numRows;
            }
            for($i = $m;$i <= $n;$i++){
                $arr[]=$data->sheets[0]['cells'][$i];
            }
        }else{
            for($i = 2;$i <= $numRows;$i++){
                if($data->sheets[0]['cells'][$i][1] == trim($search)){
                    $arr[]=$data->sheets[0]['cells'][$i];
                    $pages=ceil(($i-2)/$k);
                }
            }
        }

        foreach($arr as $key=>$val){
            for($k=1;$k<10;$k++){
                if(isset($val[$k])){
                   $arr_r[$key][] =  $val[$k];
                }else{
                    $arr_r[$key][] =  '';
                }
            }
        }
        $ret_arr['kjlist']=$arr_r;

        return $ret_arr;
    }

//    文件导出，有原文件则导出原文件，没有原文件则导出备份文件
    public function export($shop_id){
        $filename = getcwd()."/upload/excel/".$shop_id."/example.xls";
        $str='example';
        if(!file_exists( $filename )) {
            $filename = getcwd() . "/upload/excel/".$shop_id."/copy/example.xls";
            if(!file_exists( $filename )){
                return false;
                //echo "无文本信息";exit;
            }
            $str='example(备份)';
        }
        $fileinfo = pathinfo($filename);//var_dump($fileinfo);exit;
        header('Content-type: application/x-'.$fileinfo['extension']);
        header('Content-Disposition: attachment; filename='.$str.'.xls');
        header('Content-Length: '.filesize($filename));
        readfile($filename);
        exit();
    }

//    文件备份
    public function file_copy($shop_id)
    {
        $pathf = 'upload/excel/' . $shop_id . '/example.xls';//要复制文本路径
        if(file_exists($pathf)) {
            //文件复制，复制到制定路径
            $url_c = 'upload/excel/' . $shop_id . '/copy';
            //$url_c = '../copy/' . $shop_id . '/' . $MessageType;
            if (!file_exists($url_c)) {
                mkdir($url_c, 0777, true);
            }
            $copy = $url_c . '/example.xls';
            if(file_exists($copy) && file_exists($pathf)){
                unlink($copy);
                if (copy($pathf, $copy)) {           //$pathf为文件原路径
                    unlink($pathf);
                }else{
                    return false;
                }
            }
        }
        return true;
    }

//    数据整理，数据库操作时执行整行数据
    public function dbdata($name, $shop_id)
    {
        $path = getcwd() . "/upload/excel/" . $shop_id . "/create/" . $name;
        $data = new Reader();                       //创建对象
        $data->setOutputEncoding('UTF-8');          //设置编码格式
        $data->read($path);//读取excel文档
        $numRows = $data->sheets[0]['numRows'];     //读出一共几行,4
        $arr = [];
        for ($i = 2; $i <= $numRows; $i++) {
            $dataRows = $data->sheets[0]['cells'][$i];      //第i行数据
            if(!empty($dataRows[1])){
//                $arr[0][$i] = $dataRows[1];         //每行数据的第一列
                $arr[1][$i] = $dataRows;            //每行数据
            }
        }
        return $arr;
    }

//    数据库操作
    public function dbtake($name, $shop_id){
        $proItem = app::get('sysitem')->model('item');
        $arr = $this->dbdata($name, $shop_id); //获取操作数据
        $res_arr = [];
        //回滚
        $db = app::get('sysitem')->database();
        $db->beginTransaction();
        try{

        if(!empty($arr[2])){
            $res_arr['type'] = $arr[2];         //该行数据错误，报错的同时删除文本
        }else{                                  //修改表中数据，报错时返回商品对应的货号
            foreach($arr[1] as $key=>$val){
                $file = ['bn'=>$val[1]];
                if(!empty($val[2])){
                    $data['ciqGno'] = (string)$val[2];
                }
                if(!empty($val[3])){
                    $data['ciqGmodel'] = $val[3];
                }
                if(!empty($val[4])){
                    $data['gname'] = $val[4];
                }
                if(!empty($val[5])){
                    $data['gmodel'] = $val[5];
                }
                if(!empty($val[6])){
                    $data['grossWeight'] = $val[6];
                }
                if(!empty($val[7])){
                    $data['netWeight'] = $val[7];
                }
                if(!empty($val[8])){
                    $data['unit1'] = $val[8];
                }
                if(!empty($val[1])){
                    $data['itemRecordNo'] = $val[1];
                }
                if(!empty($val[9])){
                    $data['unit2'] = $val[9];
                }
//                echo "<pre>";
//                print_r ($file);
//                echo "</pre>";
//                exit;

                $res = $proItem -> update($data,$file);
                if(!$res){
                    $res_arr['list'] = $arr[0][$key];
                    throw new Exception($arr[0][$key]);
                }
            }
        }
            $db->commit();
        }
        catch(Exception $e)
        {
            $db->rollback();
            throw $e;
        }
        return $res_arr;
    }
}

