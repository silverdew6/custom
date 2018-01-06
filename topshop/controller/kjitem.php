<?php

/**
 * @brief 商家商品管理
 */
class topshop_ctl_kjitem extends topshop_controller {

    public $limit = 10;
    public $sys_taxList = array();

    function __construct(){
        parent::__construct();
        $this->sys_taxList= array(array("id"=>1 ,"name"=>"完税"),array("id"=>2 ,"name"=>"保税"),array("id"=>3 ,"name"=>"直邮")) ;;
    }
    //显示列表
    public function kjList(){
//getcwd()返回当前工作目录
        $status = input::get('status',false);
        $pages =  input::get('pages',1);//当前页
        $search = input::get('search','');
        $pagedata['status'] = $status;//？
        $shop_id=$this->shopId;

        $message = input::get('message','');

        if($message != ''){
            $message = json_decode($message,true);//var_dump($message);exit;
        }
        $pagedata['message'] = $message;

        $projectKJ = kernel::single('sysitem_item_kjitem');
        $kjList = $projectKJ->getexcel($shop_id,$pages,$search,$this->limit);

        $pagedata['kjlist'] = $kjList['kjlist'];                    //数据列表
        $pagedata['total'] = $kjList['total_found'];                //数据条数
        $totalPage = ceil($kjList['total_found']/$this->limit);

        $pagersFilter['pages'] = time();
        $pagersFilter['status'] = $status;

        $pagers = array(
            'link'=>url::action('topshop_ctl_kjitem@kjList',$pagersFilter),
            'current'=>$pages,
            'use_app' => 'topshop',
            'total'=>$totalPage,
            'token'=>time(),
        );
        $pagedata['pagers'] = $pagers;
        $pagedata['header'] = $kjList['header'];

        $str = '跨境商品参数';
        if($kjList['type']){
            $str = '跨境商品参数(备份)';
        }
        $this->contentHeaderTitle = app::get('topshop')->_($str);
//        echo "<pre>";
//        print_r($pagedata);
//        echo "</pre>";exit;

        return $this->page('topshop/kjitem/kjlist.html', $pagedata);
    }
    //跨境参数导出
    public function kjexport(){
        $shop_id = $this->shopId;
        if(! kernel::single('sysitem_item_kjitem')->export($shop_id)){
            $mess = '无文本信息';
            return $this->retMess($mess);
        }
    }
    //调用函数
    public function retMess($mess){
        $data = ['mess'=>$mess];
        $msg = json_encode($data);
        $url = url::action('topshop_ctl_kjitem@kjList',array('message'=>$msg) );
        return redirect::to($url);
    }
    //跨境参数导入
    public function kjfileload(){
        $shop_id = $this->shopId;
        $type = input::get('type',0);
        if($type == '1'){
            $filename = getcwd()."/upload/excel/".$shop_id.'/create';
        }else{
            $filename = getcwd()."/upload/excel/".$shop_id;
            //文件上传前的准备工作
            $projectKJ = kernel::single('sysitem_item_kjitem');
            $copy = $projectKJ->file_copy($shop_id);
            if($copy == false){
                $mess = '文件copy失败';
                return $this->retMess($mess);
            }
        }
        $up = kernel::single('sysitem_item_fileload');
//设置属性(上传的位置， 大小， 类型， 名是是否要随机生成)
        $up -> set("path", $filename);
        $up -> set("maxsize", 2000000);
        $up -> set("allowtype", array('xls', 'xlsx', 'csv'));
        $up -> set("israndname", false);
        if($type == '1'){
            $name = time().'.xls';
            $up -> set('fname', $name);
        } //echo "<pre>";print_r($up -> upload("pic"));echo "</pre>";exit;
//使用对象中的upload方法， 就可以上传文件， 方法需要传一个上传表单的名子 pic, 如果成功返回true, 失败返回false
        if($up -> upload("pic")) {
            //上传成功数据库操作???
            if($type == '1'){
                $arr_db = kernel::single('sysitem_item_kjitem') -> dbtake($name, $shop_id);
                if($arr_db['type']){
                    $mess = "行数据有缺失";
                    return $this->retMess($mess);
                }
                if(!empty($arr_db['list'])){
                    $arr_str = implode(',',$arr_db['list']);
                    $mess = "货号为".$arr_str."参数入库失败";
                    return $this->retMess($mess);
                }
            }

            $mess = '文本导入成功';
            return $this->retMess($mess);
        } else {
            $mess = $up->getErrorMsg() ;
            return $this->retMess($mess);
        }
    }
}


