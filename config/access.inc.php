<?php
/*
 * 游客访问的黑/白名单，不需要开放的，可以注释掉
 */
return array(
	"access"	=>	array(
		'public/Register/*' 	=> true, //注册
		'public/Passport/*'		=> true, //登录
		'public/Widget/*'		=> true, //插件
		'api/*/*'				=> true, //API					
	)
);