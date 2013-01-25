<?php
	return array(
        //后台头部TAB配置
		'admin_channel' => array(
			'index'			=>	L('PUBLIC_SYSTEM'),
    		'user'			=>	L('PUBLIC_USER'),
    		'content'		=>	L('PUBLIC_CONTENT'),
			'task'			=>	L('PUBLIC_TASK'),
    		'extends'		=>	L('PUBLIC_EXPANSION'),
		),
		//后台菜单配置
		'admin_menu'	=> array(
			'index'		=> array(
				L('PUBLIC_SYSTEM_INFO')			=>	array(
	    			L('PUBLIC_BASIC_INFORMATION')			=>	U('admin/Home/statistics'),
					L('PUBLIC_VISIT_CALCULATION')			=>	U('admin/Home/visitorCount'),
	    		//	'资源统计'			=>	U('admin/Home/sourcesCount'),
	    			L('PUBLIC_MANAGEMENT_LOG')				=>	U('admin/Home/logs'),
    				),
    			L('PUBLIC_SYSTEM_SETTING')			=>	array(
    				L('PUBLIC_WEBSITE_SETTING')			=>	U('admin/Config/site'),
	    			L('PUBLIC_REGISTER_SETTING')		=>	U('admin/Config/register'),
    				'邀请配置'							=>	U('admin/Config/invite'),
    				L('PUBLIC_WEIBO_SETTING')			=>	U('admin/Config/feed'),
    				L('PUBLIC_NAVIGATION_SETTING')		=>	U('admin/Config/nav'),
	    			L('PUBLIC_EMAIL_SETTING')			=>	U('admin/Config/email'),
	    			L('PUBLIC_FILE_SETTING')			=>	U('admin/Config/attach'),
	    			L('PUBLIC_FILTER_SETTING')			=>	U('admin/Config/audit'),
    				L('PUBLIC_POINT_SETTING')			=>	U('admin/Config/creditRule'),
    				'地区配置'			=>  U('admin/Config/area'),
    				L('PUBLIC_LANGUAGE')			=>	U('admin/Config/lang'),
    				L('PUBLIC_MAILTITLE_ADMIN')			=>	U('admin/Config/notify'),
    				),
    			L('PUBLIC_SYSTEM_TOOL')			=>	array(
					L('PUBLIC_CLEANCACHE')						=>  'cleancache.php?all',
    				L('PUBLIC_SCHEDULED_TASK_NEWCREATE')		=>  U('admin/Home/schedule'),
    			//	'数据字典'			=>	U('admin/Home/systemdata'),	
    				),
    			),
	    	 'user' 	=> array(
	    		L('PUBLIC_USER')				=>	array(
	    			L('PUBLIC_USER_MANAGEMENT')					=>	U('admin/User/index'),
	    			L('PUBLIC_USER_GROUP_MANAGEMENT')			=>	U('admin/UserGroup/index'),
	    			L('PUBLIC_PROFILE_SETTING')					=>	U('admin/User/profile'),
	    			// L('PUBLIC_DEPARTMENT_MANAGEMENT')			=>  U('admin/Department/index'),
	    			'身份配置'									=>	U('admin/User/category'),
	    			'用户认证'									=>  U('admin/User/verifyConfig'),
	    			'官方用户'									=>	U('admin/User/official'),
	    			),
	    		),
	    	 'content'	=> array(
	    	 	L('PUBLIC_OPERATION_TOOL')			=>  array(
	    	 			// L('PUBLIC_ANNOUNCEMENT_SETTING')	=>  U('admin/Config/announcement'),
	    	 			L('PUBLIC_MESSAGE_NOTIFY')			=>  U('admin/Home/message'),
	    	 			// L('PUBLIC_INVITE_CALCULATION')		=>  U('admin/Home/invatecount'),
	    	 			// L('PUBLIC_FEEDBACK_MANAGEMENT')		=>  U('admin/Home/feedback'),
	    	 		//	'意见反馈分类'	=>  U('admin/Home/feedbackType')
	    	 		),
	    		L('PUBLIC_CONTENT_MANAGEMENT')			=>	array(
		    			L('PUBLIC_WEIBO_MANAGEMENT')	=>	U('admin/Content/feed'),
		    			'话题管理'	=>	U('admin/Content/topic'),
		    			L('PUBLIC_COMMENT_MANAGEMENT')	=>	U('admin/Content/comment'),
		    			L('PUBLIC_PRIVATE_MESSAGE_MANAGEMENT')	=>	U('admin/Content/message'),
		    			L('PUBLIC_FILE_MANAGEMENT')	=>	U('admin/Content/attach'),
		    			L('PUBLIC_REPORT_MANAGEMENT')	=>	U('admin/Content/denounce'),
						L('PUBLIC_TAG_MANAGEMENT')		=>  U('admin/Home/tag'),
		    		),
	    		),
			 'task'			=> array(
			 		L('PUBLIC_TASK_INFO')			=> array(
			 				L('PUBLIC_TASK_LIST')	=> U('admin/Task/index'),
			 				L('PUBLIC_TASK_REWARD') => U('admin/Task/reward'),
			 				'勋章列表'				=> U('admin/Medal/index'),
			 				'用户勋章'				=> U('admin/Medal/userMedal')
			 				)
			 		
			 		),
 	    	 'extends'		=> array(
	    		L('PUBLIC_APP_MANAGEMENT')			=>	array(
		    			L('PUBLIC_INSTALLED_APPLIST')	=>	U('admin/Apps/index'),
		    			L('PUBLIC_UNINSTALLED_APPLIST')	=>	U('admin/Apps/install'),
		    			L('PUBLIC_POINTS_SETTING')	=>  U('admin/Apps/setCreditNode'),
		    			L('PUBLIC_AUTHORITY_SETTING')	=>  U('admin/Apps/setPermNode'),
		    			// L('PUBLIC_WEIBO_TEMPLATE_SETTING')	=>  U('admin/Apps/setFeedNode'),
		    		),
	    		L('PUBLIC_APP_SETTING')	=> model('App')->getConfigList(),
	    		// L('PUBLIC_DIYWIDGET')=>array(
	    		// 		L('PUBLIC_DIYWIDGET')	=> U('admin/Config/diylist'),	
	    		// 	),
    	 		'插件管理' => array(
	    			'插件列表' => U('admin/Addons/index'),
	    		),
    	 		// '插件配置' => model('Addon')->getAddonsAdminUrl(),
    	 		),
	    	 )
	);