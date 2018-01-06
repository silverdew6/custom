<?php

/**
 * 全部自动更新XunSearch的索引库；
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class systrade_tasks_trade_xunsearch extends base_task_abstract implements base_interface_task {
	public function exec($params = null) {
		$intervalTime = app :: get('sysconf')->getConf('trade.finish.spacing.time');
		$href_url = "http://www.gojmall.com/app/xs_zindex_jmall.php?code=21232f297a57a5a743894a0e4a801fc3&secket=2d5c132d569592963ddf9aa0105f8aa4";
		//初始化
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_URL, $href_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		//执行并获取HTML文档内容
		$output = curl_exec($ch);
		//释放curl句柄
		curl_close($ch);
		return $output ;
	}
}