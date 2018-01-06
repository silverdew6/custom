<?php
/**
 * 搜索引擎管理
 */
class search_ctl_policy extends desktop_controller {

    public function index()
    {
        if( $policy = app::get('search')->getConf('search_server_policy') )
        {
            $obj = kernel::single($policy);   //search_policy_mysql
            $status = $obj->status($msg); //查询当前搜索的连接状态。
            if( !$status ) {
                app::get('search')->setConf('search_server_policy','');
            }
        }
        //查询已有的搜索方式
        $searchList = $this->finder('search_mdl_policy',
        				   array(
				                'title' =>  app::get('search')->_('搜索引擎管理'),
				                'base_filter' => array(),
				                'use_buildin_set_tag' => false,
				                'use_buildin_export' => false,
				                'use_buildin_selectrow'=>false,
				            ));
        $searchList .= "<div style='padding: 5px;box-shadow: 0 0 1px #999;margin:10px 20px;'><h3>下面是新增 XunSearch 搜索引擎内容</h3><br/><div style=\"padding: 5px;box-shadow: 0 0 1px #999;width:98%;margin:5px auto;\"><p style='color:green; '> 因系统中添加了对XunSearch搜索引擎的支持，故在总后新增了手动更新站内索引的相关操作 （定时任务中添加为每5小时重新生成一次索引）。</p>";		
        $searchList .= '您可在这里执行 <a href="javascript:;" onClick="javascript:W.page(\'?app=search&ctl=policy&act=updateCoverXs\')" >重新生成XunSearch索引</a> '
        			.'<br><br/> 同时也可手动复制下面的链接地址到浏览器来执行：<br/>' 
        			.'1、 http://www.gojmall.com/app/xs_zindex_jmall.php?code=21232f297a57a5a743894a0e4a801fc3&secket=2d5c132d569592963ddf9aa0105f8aa4 (全部更新)<br/>'
        			.'2、 http://www.gojmall.com/app/xs_zindex_jmall.php?code=21232f297a57a5a743894a0e4a801fc3&secket=2d5c132d569592963ddf9aa0105f8aa4&shopId=3 (单个店铺,店铺ID)<br/> '
        			.'3、 http://www.gojmall.com/app/xs_zindex_jmall.php?code=21232f297a57a5a743894a0e4a801fc3&secket=2d5c132d569592963ddf9aa0105f8aa4&itemId=2456,243,111 (多个商品更新,使用商品ID) <br/>'
        			.'<br/></div></div>';     
        return  $searchList ? $searchList : false ;
    }
    
    /**
     * 更新全部索引
     * 
     */
    function updateCoverXs(){
    	$this->begin('?app=search&ctl=policy&act=index');
    	$shopid = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : false;
    	$href_url = "http://www.gojmall.com/app/xs_zindex_jmall.php?code=21232f297a57a5a743894a0e4a801fc3&secket=2d5c132d569592963ddf9aa0105f8aa4";
    	if($shopid){
    		$href_url .= "&shopId=$shopid";
    	}
    	@file_get_contents($href_url);
    	$this->end(true, $this->app->_('全部更新完成 ' ));
    }
    
    /**
     * 开启 Xunsearch 搜索引擎的索引更新操作；
     * 
     */
     function xunsearch(){
     	// 是否开启
     	$xsobj = 	kernel::single("search_policy_xunsearch")->instance("demo");
     	echo "总数据记录条数：".$xsobj->getTotal_num();
     	echo "<br/>";
     	$unnn = 0 ;
     	$condition = array("level"=> 3 , "keyword"=> "奶粉");
     	
     	//分面 $xsobj->commonFacetsConfig(array("parent_id")); $resd = $xsobj->commonFacetsConfig("parent_id" ,true);
     	$rest = $xsobj-> searchXs_Result($condition,1,$unnn);
     	
     	echo "<TTTTTTTTTTT>";
     	
     	$condition2 = array("shop_id"=> 1 , "keyword"=> "韩国进口");
     	$xsobj2 = 	kernel::single("search_policy_xunsearch")->instance("jmall");
     	$rest = $xsobj2-> searchXs_Result($condition2,1,$unnn);
     	var_dump($unnn);
     	exit;
     	$newInd = array("cat_id" => 901 , "cat_name"=> "小样我就这样2","parent_id"=>792,'level'=>3);
     	$ret = $xsobj->add_One($newInd);
     	var_dump($ret);
     	exit;
     	$inputdata = input::get();
     	//print_r($inputdata);
     	$status_pl  = array("open"=> "启用","colse"=> "关闭");
     	//POST 表单提交
     	$msg = "";
     	if(isset($inputdata["open_status"]) && trim($inputdata["open_status"])!= "" ){
     		$up_status = isset($inputdata["open_status"]) ? trim($inputdata["open_status"]) : 'colse';
     		//显示开启操作；
	 		if($up_status== 'open' && intval($inputdata["opta"])===1){
	 			//$xsobj = 	kernel::single("search_policy_xunsearch");  //xunsearch Lib   ＝＝＝＝ search_policy_xunsearch
	            $xsobj = 	kernel::single("search_policy_xunsearch")->instance("demo");
	            if($xsobj->checkXS_status($msg) ) {	//查询当前搜索的连接状态。
	                app::get('search')->setConf('search_server_xunsearch',1);
	                $is_open_status = 'open';
	            } //return redirect::back();  return redirect::action('topc_ctl_list@index',array('n'=>$keyword));
	 		}else{  
	 			app::get('search')->setConf('search_server_xunsearch',0);	//关闭
	 			$is_open_status = 'colse';
	 		}
     	}
     	$is_openxs  = app::get('search')->getConf('search_server_xunsearch');
     	
     	if(isset($is_openxs) && intval($is_openxs) ===1 ){  //已开启 ，才有更新索引的相关功能和操作内容
     		$is_open_status = 'open';
     		//展示索引数量；
     		
     		
     		//查询索引的相关数据；
     		
     		
     	} 
     	$pagedata["xuns_opendata"] = array("slist" => $status_pl , "status" =>  $is_open_status );
     	return $this->page('search/xunsearch/default.html', $pagedata);
     }

    //开启搜索
    public function setDefault()
    {
        $this->begin('?app=search&ctl=policy&act=index');
        $method = $_GET['method'];
        $name = $_GET['name'];
        if($method == 'open')
        {
            $obj = kernel::single($name);
            if( !$obj->status($msg) )
            {
                $this->end(false, $this->app->_('连接异常，请先确认是否连接'));
            }
            app::get('search')->setConf('search_server_policy',$name);
        }
        $this->adminlog("修改默认搜索引擎", 1);
        $this->end(true, $this->app->_('保存成功'));
    }
}

