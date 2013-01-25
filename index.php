<?php
//ini_set('display_errors','on');
//error_reporting(E_ALL);
error_reporting(E_ERROR);

//站点根路径
define('SITE_PATH', dirname(__FILE__));

//载入核心文件
require(SITE_PATH.'/core/core.php');

$mem_run_start = memory_get_usage();
$time_run_start =  microtime(TRUE);

//实例化一个网站应用实例
App::run();

$mem_run_end = memory_get_usage();
$time_run_end = microtime(TRUE);

if($_GET['debug'] || C('APP_DEBUG')){
	echo '<hr />';
	echo ' Memories: '."<br/>";
	echo 'ToTal: ',number_format(($mem_run_end - $mem_include_start)/1024),'k',"<br/>";
	echo 'Include:',number_format(($mem_run_start - $mem_include_start)/1024),'k',"<br/>";
	echo 'Run:',number_format(($mem_run_end - $mem_run_start)/1024),'k<br/><hr/>';
	echo 'Time:<br/>';
	echo 'ToTal: ',($time_run_end - $time_include_start),"s<br/>";
	echo 'Include:',($time_run_start - $time_include_start),'s',"<br/>";
	echo 'Run:',($time_run_end - $time_run_start),'s<br/><br/>';
	//数据库查询信息
	echo '<hr />';
	dump(Log::$log);
	echo '<hr />';
	//缓存使用情况
	dump(Cache::$log);
	echo '<hr />';
	//载入文件情况
	$files = get_included_files();
	dump($files);
}