<?php
if (!defined('SITE_PATH')) exit();

return array(
	/* 常用配置 */
	'DB_TYPE'			=>	'mysql',		// 数据库类型

	'DB_HOST'			=>	'127.0.0.1',	// 数据库服务器地址
	'DB_NAME'			=>	'thinksns',			// 数据库名
	'DB_USER'			=>	'root',			// 数据库用户名
	'DB_PWD'			=>	'',				// 数据库密码
	
	// 'DB_HOST'			=>	'192.168.1.100',	// 数据库服务器地址
	// 'DB_NAME'			=>	'uat_sociax',			// 数据库名
	// 'DB_USER'			=>	'root',			// 数据库用户名
	// 'DB_PWD'			=>	'',				// 数据库密码

	'DB_PORT'			=>	3306,			// 数据库端口
	'DB_PREFIX'			=>	'ts_',			// 数据库表前缀
	'DB_CHARSET'		=>	'utf8',			// 数据库编码
	'SECURE_CODE'		=>	'ts3',	// 数据加密密钥

	/* 网站配置 */
	//'ADMIN_UID'         =>	1,   //创始人ID
	//'ADMIN_PASSWORD'    =>	'THINKSNS!Q@W#E$R',//创始人临时密码,系统数据库出现问题无法登陆时.使用该密码可以登录后台
	'COOKIE_PREFIX'		=>	'ST_',	// 数据加密密钥
	'THEME_NAME'		=>	'stv1',
	'DEPLOY_STASTIC'	=>	false,	//是否独立部署静态文件.开发时否,生产时真.
	'DATA_CACHE_TYPE'	=>	'File',
	'APP_PLUGIN_ON'		=>	true,
);