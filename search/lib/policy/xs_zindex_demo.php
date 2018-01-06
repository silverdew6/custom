<?php


/**
* 索引实时更新管理器
* 判断标准：
* 添加：pass_state=1 && create_time>

文件记录值
* 修改：pass_state=1 && edit_time> 文件记录值
* 删除：status =0如果有del_time也可以通过上面的方法判断，我这里没有
* 文件记录值：上次更新索引时的时间
* 调用方法：
* 正常调用：jishubu.net.php
* 不开启日志：jishubu.net.php?log=no
* 删除日志的说明如下：
* 如果你是通过我这样记录已删除id的方式，并且非物理删除，并且开启了日志功能，第一次调用最好先在浏览器执行jishubu.net.php?first=yes，不然如果删除数据较多，生成的日志文件较多，浪费空间。
* 其他：然后把文件放到crontab中，每天，小时，或分执行一次就行了。请根据自己项目情况改进。
* @author www.jishubu.net on wish 2012.11.30 改进
*/
error_reporting(E_ALL ^ E_NOTICE);
define('APP_PATH', dirname(__FILE__));
$prefix = '/usr/local/xunsearch';
$element = "$prefix/sdk/php/app/mfnews.ini";
require_once "$prefix/sdk/php/lib/XS.php";
$first = (trim($_GET['first']) == 'yes') ? true : false;
$log = (trim($_GET['log']) == 'no') ? false : true;
$xs = new XS($element); // 建立 XS 对象
$index = $xs->index; // 获取 索引对象
$doc = new XSDocument;
// 创建文档对象
//读取上次更新索引时间
$last_index_time = @ file_get_contents(APP_PATH . '/last_index_time.txt');
$last_index_time = $last_index_time ? $last_index_time : time(); //这里也可以在last_index_time.txt文件中加个初始值
//删除过的id列表，如果字段有删除时间字段则不需要记录，如果是物理删除，需要记录删除日志，否则无法知道哪些文件曾被删除
$last_del_id = @ file_get_contents(APP_PATH . '/last_del_id.txt');
$link = mysql_connect('localhost:3306', 'root', '123456') or exit (mysql_error());
mysql_select_db('phpcms', $link) or exit (mysql_error());
mysql_query("SET NAMES utf8");
//查询总数据量，并分批更新
$sql = "select count(*) as zongshu from phpcms_news,phpcms_news_data where phpcms_news.id=phpcms_news_data.id and phpcms_news.status=99 and phpcms_news.islink=0 and (phpcms_news.inputtime > {$last_index_time} or phpcms_news.updatetime > {$last_index_time})";
$zongshu = mysql_query($sql) or exit (mysql_error());
while ($row = mysql_fetch_array($zongshu, MYSQL_ASSOC)) {
	$zx[] = $row;
}
$n = 0;
$i = 1;
$count = 1;
$add_total = 0;
$edit_total = 0;
$addArray = array ();
$editArray = array ();
//添加分批查询避免查询出过多的数据使PHP报错
do {
	$index->openBuffer(); // 开启缓冲区
	//增加，修改索引
	$sql = "select inch_cms_news.id as id,title,url,inputtime,updatetime,phpcms_news_data.content as content from phpcms_news,phpcms_news_data where phpcms_news.id=phpcms_news_data.id and phpcms_news.status=99 and phpcms_news.islink=0 and (phpcms_news.inputtime > {$last_index_time} or phpcms_news.updatetime > {$last_index_time}) limit $n,100";
	$res = mysql_query($sql) or exit (mysql_error());
	$restult = array ();
	while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
		$row['title'] = preg_replace('/&nbsp;|&quot;/', '', strip_tags($row['title']));
		$row['content'] = preg_replace('/&nbsp;|&quot;/', '', strip_tags($row['content']));
		$edit_time = $row['updatetime'];
		$create_time = $row['inputtime'];
		unset ($row['updatetime']);
		if ($edit_time == $create_time) {
			$add_total++;
			$addArray[] = $row;
		} else {
			$edit_total++;
			$editArray[] = $row;
		}
		$doc->setFields($row);
		$index->update($doc);
		$i++; //如果回收站回收的数据，然后从已删除记录中，清除该id 
		if ($last_del_id && strpos($last_del_id, ',' . $row['id'] . ',') !== false) {
			$last_del_id = str_replace($row['id'] . ',', '', $last_del_id);
		}
	}
	$n = $n +100;
	$index->closeBuffer(); // 关闭缓冲区
}
while ($n <= $zx['0']['zongshu']);
$index->openBuffer(); // 开启缓冲区
//删除索引
$sql = "SELECT phpcms_news.id as id,title,url,inputtime,phpcms_news_data.content as content FROM phpcms_news,phpcms_news_data where phpcms_news.id=phpcms_news_data.id and phpcms_news.status!=99 and phpcms_news.islink=0";
$res = mysql_query($sql) or exit (mysql_error());
$del_total = 0;
$ids = '';
$delArray = array ();
while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
	if (strpos($last_del_id, ',' . $row['id'] . ',') === false) {
		$ids .= $row['id'] . ',';
		$delArray[] = $row;
		$del_total++;
	}
}
if ($ids) {
	$index->del(array (
		trim($ids, ',')
	));
	$last_del_id = $last_del_id ? $last_del_id . $ids : ',' . $ids;
}
$index->closeBuffer(); // 关闭缓冲区
$total = $add_total + $edit_total + $del_total;
if ($total) {

	//记录索引更新时间
	@ file_put_contents(APP_PATH . '/last_index_time.txt', time());
	@ file_put_contents(APP_PATH . '/last_del_id.txt', $last_del_id);

	//记录日志
	if ($log) {
		$currdate = date('Y-m-d H:i:s', time());
		if (!$first)
			@ file_put_contents('/tmp/myindex_log.txt', "\n@@@@@@@@@@@@@@@@@@@@@ {$currdate} 本次更新{$total}条记录，详情如下：@@@@@@@@@@@@@@@@@@@@@@\n\n", FILE_APPEND);
		if ($add_total)
			addMyIndexLog('add', $add_total, $addArray);
		if ($edit_total)
			addMyIndexLog('update', $edit_total, $editArray);
		if ($del_total && !$first)
			addMyIndexLog('delete', $del_total, $delArray);
	}
}
function addMyIndexLog($logtype, $logtatal, $logdata, $prefix = '') {
	@ file_put_contents('/tmp/myindex_log.txt', $prefix . date('Y-m-d H:i:s', time()) . ' ' . $logtype . ' index num : ' . $logtatal . ' ' . str_repeat('*', 50) . "\n" . print_r($logdata, true), FILE_APPEND);
}
mysql_free_result($res);
mysql_close($link);
if ($total)
	$index->flushIndex();
?>