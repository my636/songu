<?php
/**
 * 频道版本信息
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
if(!defined('SITE_PATH')) exit();

return array(
	// 应用名称 [必填]
	'NAME'						=> 'DIY',	
	// 应用简介 [必填]
	'DESCRIPTION'				=> 'DIY',
	// 托管类型 [必填]（0:本地应用，1:远程应用）
	'HOST_TYPE'					=> '0',
	// 前台入口 [必填]（格式：Action/act）
	'APP_ENTRY'					=> 'Diy/index',
	// 为空
	'ICON_URL'					=> '',
	// 为空
	'LARGE_ICON_URL'			=> '',
	// 版本号 [必填]
	'VERSION_NUMBER'			=> '1',
	// 后台入口 [选填]
	'ADMIN_ENTRY'				=> 'page/Admin/index',
	// 统计入口 [选填]（格式：Model/method）
	'STATISTICS_ENTRY'			=> 'Statistics/statistics',
	// 公司名称
	'COMPANY_NAME'				=> '智士软件',
	// 快捷方式子导航
	'CHILD_MENU'				=> array(
										// 我的XX  直接使用应用名，其他链接使用语言包KEY
										'page'=>array(
															//链接地址
															'url'	 => 'page/Diy/index',
															//是否公开：为0 则不在档案页展示，为1则在档案页展示
															'public' => 0, 
													),
													
										),

	// 是否有移动端
	'HAS_MOBILE'				=> '1',
);