<?php
//+----------------------------------------------------------------------
// | Sociax [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2012 http://www.thinksns.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: jason <yangjs17@yeah.net>
// +----------------------------------------------------------------------
// 

/**
 +------------------------------------------------------------------------------
 * 内容管理
 +------------------------------------------------------------------------------
 *                            
 * @author    jason <yangjs17@yeah.net> 
 * @version   1.0
 +------------------------------------------------------------------------------
 */
tsload(APPS_PATH.'/admin/Action/AdministratorAction.class.php');

class ContentAction extends AdministratorAction
{
	public $pageTitle = array();
	//TODO  要移位置
	public $from = array(0=>'网站',1=>'手机网页版',2=>'android',3=>'iphone');
	
	public function feed($isRec = 0){
		//搜索区别
		$_POST['rec'] = $isRec = isset($_REQUEST['rec']) ? t($_REQUEST['rec']) : $isRec;
		 
		$this->pageKeyList = array('feed_id','uid','uname','data','publish_time','type','from','DOACTION');
		$this->searchKey = array('feed_id','uid','type','rec');
		$this->opt['type'] = array('0'=>L('PUBLIC_ALL_STREAM'),'post'=>L('PUBLIC_ORDINARY_WEIBO'),'repost'=>L('PUBLIC_SHARE_WEIBO'),'postimage'=>L('PUBLIC_PICTURE_WEIBO'),'postfile'=>L('PUBLIC_ATTACHMENT_WEIBO'));	//TODO 临时写死
		
		$this->pageTab[] = array('title'=>L('PUBLIC_DYNAMIC_MANAGEMENT'),'tabHash'=>'list','url'=>U('admin/Content/feed'));
		$this->pageTab[] = array('title'=>L('PUBLIC_RECYCLE_BIN'),'tabHash'=>'rec','url'=>U('admin/Content/feedRec'));
		
		$this->pageButton[] = array('title'=>L('PUBLIC_DYNAMIC_SEARCH'),'onclick'=>"admin.fold('search_form')");
		if($isRec == 0){
			$this->pageButton[] = array('title'=>L('PUBLIC_DYNAMIC_DELETE'),'onclick'=>"admin.ContentEdit('','delFeed','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_DYNAMIC')."')");
		}else{
			$this->pageButton[] = array('title'=>L('PUBLIC_REMOVE_COMPLETELY'),'onclick'=>"admin.ContentEdit('','deleteFeed','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_DYNAMIC')."')");
		}
		
		$isRec == 1 &&  $_REQUEST['tabHash'] = 'rec';
		$this->assign('pageTitle',$isRec ? L('PUBLIC_RECYCLE_BIN'):L('PUBLIC_DYNAMIC_MANAGEMENT'));
		$map['is_del'] = $isRec==1 ? 1 :0; 
		!empty($_POST['feed_id']) && $map['feed_id'] = array('in',explode(',',$_POST['feed_id']));
		!empty($_POST['uid']) && $map['uid'] = array('in',explode(',',$_POST['uid']));
		!empty($_POST['type']) && $map['type'] = t($_POST['type']);


		$listData = model('Feed')->getList($map,20);
		foreach($listData['data'] as &$v){
			$v['uname']    = $v['user_info']['space_link'];
			$v['type']	   = $this->opt['type'][$v['type']]; 
			$v['from']     = $this->from[$v['from']];
			$v['data']	   = '<div style="width:500px;line-height:22px">'.$v['body'].'  <a target="_blank" href="'.U('public/Profile/feed',array('feed_id'=>$v['feed_id'],'uid'=>$v['uid'])).'">'.L('PUBLIC_VIEW_DETAIL').'&raquo;</a></div>';
			$v['publish_time'] = date('Y-m-d H:i:s',$v['publish_time']);
			$v['DOACTION'] = $isRec==0 ? "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['feed_id']},\"delFeed\",\"".L('PUBLIC_STREAM_DELETE')."\",\"".L('PUBLIC_DYNAMIC')."\")'>".L('PUBLIC_STREAM_DELETE')."</a>"
										:"<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['feed_id']},\"feedRecover\",\"".L('PUBLIC_RECOVER')."\",\"".L('PUBLIC_DYNAMIC')."\")'>".L('PUBLIC_RECOVER')."</a>";
		}
		$this->_listpk = 'feed_id';
		$this->displayList($listData);
	}
	//回收站
	public function feedRec(){
		$this->pageKey = APP_NAME.'_'.MODULE_NAME.'_feed';
        $this->searchPageKey = 'S_'.$this->pageKey ;
		$this->feed(1);
	}
	//恢复
	public function feedRecover(){
		
		$return =  model('Feed')->doEditFeed($_POST['id'],'feedRecover',L('PUBLIC_RECOVER'));
		if($return['status'] == 0){
			$return['data'] = L('PUBLIC_RECOVERY_FAILED');
		}else{
			$return['data'] = L('PUBLIC_RECOVERY_SUCCESS');	
		}
		echo json_encode($return);exit();
		
	}
	//假删除 
	public function delFeed(){
		
		$return =  model('Feed')->doEditFeed($_POST['id'],'delFeed',L('PUBLIC_STREAM_DELETE'));
		if($return['status'] == 0){
			$return['data'] = L('PUBLIC_DELETE_FAIL');
		}else{
			$return['data'] = L('PUBLIC_DELETE_SUCCESS');	
		}
		echo json_encode($return);exit();
	}
	//真删除 
	public function deleteFeed(){
		$return =  model('Feed')->doEditFeed($_POST['id'],'deleteFeed',L('PUBLIC_REMOVE_COMPLETELY'));
		if($return['status'] == 0){
			$return['data'] = L('PUBLIC_REMOVE_COMPLETELY_FAIL');
		}else{
			$return['data'] = L('PUBLIC_REMOVE_COMPLETELY_SUCCESS');	
		}
		echo json_encode($return);exit();
		
	}
	
	/**
	 * 评论管理
	 * @param boolean $isRec 是否是回收站列表
	 * @return array 相关数据
	 */
	public function comment($isRec = false)
	{
		// 搜索区别
		$_POST['rec'] = $isRec = isset($_REQUEST['rec']) ? t($_REQUEST['rec']) : $isRec;
		 
		$this->pageKeyList = array('comment_id','uid','app_uid','source_type','content','ctime','client_type','DOACTION');
		$this->searchKey = array('comment_id','uid','app_uid');
		
		$this->pageTab[] = array('title'=>'评论管理','tabHash'=>'list','url'=>U('admin/Content/comment'));
		$this->pageTab[] = array('title'=>L('PUBLIC_RECYCLE_BIN'),'tabHash'=>'rec','url'=>U('admin/Content/commentRec'));
		
		$this->pageButton[] = array('title'=>L('PUBLIC_SEARCH_COMMENT'),'onclick'=>"admin.fold('search_form')");
		if($isRec == 0){
			$this->pageButton[] = array('title'=>L('PUBLIC_DELETE_COMMENT'),'onclick'=>"admin.ContentEdit('','delComment','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_STREAM_COMMENT')."')");
		}else{
			$this->pageButton[] = array('title'=>L('PUBLIC_REMOVE_COMPLETELY'),'onclick'=>"admin.ContentEdit('','deleteComment','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_STREAM_COMMENT')."')");
		}
		
		$isRec == 1 &&  $_REQUEST['tabHash'] = 'rec';
		$this->assign('pageTitle',$isRec ? L('PUBLIC_RECYCLE_BIN'):'评论管理');
		$map['is_del'] = $isRec==1 ? 1 :0; 
		!empty($_POST['comment_id']) && $map['comment_id'] = array('in',explode(',',$_POST['comment_id']));
		!empty($_POST['uid']) && $map['uid'] = array('in',explode(',',$_POST['uid']));
		!empty($_POST['app_uid']) && $map['app_uid'] = array('in',explode(',',$_POST['app_uid']));
		$listData = model('Comment')->getCommentList($map,'comment_id desc',20);

		foreach($listData['data'] as &$v){
			
			$v['uid']			  = $v['user_info']['space_link']; 
			$v['app_uid']		  = $v['sourceInfo']['source_user_info']['space_link'];	
			$v['source_type']	  = "<a href='{$v['sourceInfo']['source_url']}' target='_blank'>".$v['sourceInfo']['source_type']."</a>";
			$v['content']		  = '<div style="width:400px">'.$v['content'].'</div>';	
			$v['client_type']     = $this->from[$v['client_type']];
			$v['ctime']    = date('Y-m-d H:i:s',$v['ctime']);
			$v['DOACTION'] = $isRec==0 ? "<a href='".$v['sourceInfo']['source_url']."' target='_blank'>".L('PUBLIC_VIEW')."</a> <a href='javascript:void(0)' onclick='admin.ContentEdit({$v['comment_id']},\"delComment\",\"".L('PUBLIC_STREAM_DELETE')."\",\"".L('PUBLIC_STREAM_COMMENT')."\")'>".L('PUBLIC_STREAM_DELETE')."</a>"
										:"<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['comment_id']},\"CommentRecover\",\"".L('PUBLIC_RECOVER')."\",\"".L('PUBLIC_STREAM_COMMENT')."\")'>".L('PUBLIC_RECOVER')."</a>";
		}
		$this->_listpk = 'comment_id';
		$this->displayList($listData);
	}
	
	//回收站
	public function commentRec(){
		$this->pageKey = APP_NAME.'_'.MODULE_NAME.'_comment';
        $this->searchPageKey = 'S_'.$this->pageKey ;
		$this->comment(1);
	}

	//恢复
	public function commentRecover()
	{
		echo json_encode(model('Comment')->doEditComment($_POST['id'],'commentRecover','恢复成功'));
	}
	//假删除 
	public function delComment(){
		echo json_encode( model('Comment')->doEditComment($_POST['id'],'delComment','删除成功'));
	}
	//真删除 
	public function deleteComment(){
		echo json_encode( model('Comment')->doEditComment($_POST['id'],'deleteComment', '评论彻底删除成功'));
	}
	
	public function message($isRec = 0)
	{
		//搜索区别
		$_POST['rec'] = $isRec = isset($_REQUEST['rec']) ? t($_REQUEST['rec']) : $isRec;
		 
		$this->pageKeyList = array('message_id','fuid','from_uid','mix_man','content','mtime','DOACTION');
		$this->searchKey = array('from_uid','mix_man','content');
		
		$this->pageTab[] = array('title'=>L('PUBLIC_PRIVATE_MESSAGE_MANAGEMENT'),'tabHash'=>'list','url'=>U('admin/Content/message'));
		$this->pageTab[] = array('title'=>L('PUBLIC_RECYCLE_BIN'),'tabHash'=>'rec','url'=>U('admin/Content/messageRec'));
		
		$this->pageButton[] = array('title'=>L('PUBLIC_MASSAGE_SEARCH'),'onclick'=>"admin.fold('search_form')");
		if($isRec == 0){
			$this->pageButton[] = array('title'=>L('PUBLIC_MASSAGE_DEL'),'onclick'=>"admin.ContentEdit('','delMessage','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_PRIVATE_MESSAGE')."');");
		}else{
			$this->pageButton[] = array('title'=>L('PUBLIC_REMOVE_COMPLETELY'),'onclick'=>"admin.ContentEdit('','deleteMessage','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_PRIVATE_MESSAGE')."')");
		}
		
		$isRec == 1 &&  $_REQUEST['tabHash'] = 'rec';
		$this->assign('pageTitle',$isRec ? L('PUBLIC_RECYCLE_BIN'):L('PUBLIC_PRIVATE_MESSAGE_MANAGEMENT'));
		$map['a.is_del'] = $isRec==1 ? 1 :0;	//未删除的 
		!empty($_POST['from_uid']) && $map['a.from_uid'] = intval($_POST['from_uid']);
		!empty($_POST['mix_man']) && $map['b.mix_man'] = array('like','%'.intval($_POST['mix_man']).'%');
		!empty($_POST['content']) && $map['a.content'] = array('like','%'.t($_POST['content']).'%');
		$map['b.type'] = array('neq',3);
		
		$listData = model('Message')->getDetailList($map);

		//$listData = D('message')->where($map)->order('message_id desc')->findPage(20); 	//TODO 等model做好后再放入到model中
		

		foreach($listData['data'] as &$v){
			$uids = explode('_',$v['min_max']);
			$map = array();
			$map['uid'] = array('in',$uids);
			$uname = model('User')->where($map)->getHashList('uid','uname');

			$v['mix_man'] = implode(',',$uname);

			if($v['fuid'] == '1'){
				$v['fuid'] = L('PUBLIC_SYSTEM');
			}else{
				$v['fuid'] = $uname[$v['fuid']];
			}

			if($v['from_uid'] == '1'){
				$v['from_uid'] = L('PUBLIC_SYSTEM');
			}else{
				$v['from_uid'] = $uname[$v['from_uid']];	
			}
			
			$v['content']  = '<div style="width:500px">'.getShort($v['content'],120,'...').'</div>';// 截取120字
			$v['mtime']    = date('Y-m-d H:i:s',$v['mtime']);
			$v['DOACTION'] = $isRec==0 ? "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['message_id']},\"delMessage\",\"".L('PUBLIC_STREAM_DELETE')."\",\"".L('PUBLIC_PRIVATE_MESSAGE')."\");'>".L('PUBLIC_STREAM_DELETE')."</a>"
										:"<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['message_id']},\"MessageRecover\",\"".L('PUBLIC_RECOVER')."\",\"".L('PUBLIC_PRIVATE_MESSAGE')."\")'>".L('PUBLIC_RECOVER')."</a>";
		}
		$this->_listpk = 'message_id';
		$this->displayList($listData);
	}
		
	//回收站
	public function messageRec(){
		$this->pageKey = APP_NAME.'_'.MODULE_NAME.'_message';
        $this->searchPageKey = 'S_'.$this->pageKey ;
		$this->message(1);
	}
	//恢复
	public function messageRecover(){
		echo json_encode( model('Message')->doEditMessage($_POST['id'],'messageRecover',L('PUBLIC_RECOVER')));
	}
	//假删除 
	public function delMessage(){
		echo json_encode( model('Message')->doEditMessage($_POST['id'],'delMessage',L('PUBLIC_STREAM_DELETE')));
	}
	//真删除 
	public function deleteMessage(){
		echo json_encode( model('Message')->doEditMessage($_POST['id'],'deleteMessage',L('PUBLIC_REMOVE_COMPLETELY')));
	}
	
	
	public function attach($isRec = 0)
	{
		$this->_listpk = 'attach_id';
		//搜索区别
		$_POST['rec'] = $isRec = isset($_REQUEST['rec']) ? t($_REQUEST['rec']) : $isRec;
		 
		$this->pageKeyList = array('attach_id','name','size','uid','ctime','from','DOACTION');
		$this->searchKey = array('attach_id','name','from');
		
		$this->opt['from'] = array_merge( array('-1'=>L('PUBLIC_ALL_STREAM')), $this->from);
		$this->pageTab[] = array('title'=>L('PUBLIC_FILE_MANAGEMENT'),'tabHash'=>'list','url'=>U('admin/Content/attach'));
		$this->pageTab[] = array('title'=>L('PUBLIC_RECYCLE_BIN'),'tabHash'=>'rec','url'=>U('admin/Content/attachRec'));
		
		$this->pageButton[] = array('title'=>L('PUBLIC_FILE_STREAM_SEARCH'),'onclick'=>"admin.fold('search_form')");
		if($isRec == 0){
			$this->pageButton[] = array('title'=>L('PUBLIC_FILE_STREAM_DEL'),'onclick'=>"admin.ContentEdit('','delAttach','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_FILE_STREAM')."');");
		}else{
			$this->pageButton[] = array('title'=>L('PUBLIC_REMOVE_COMPLETELY'),'onclick'=>"admin.ContentEdit('','deleteAttach','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_FILE_STREAM')."')");
		}
		
		$isRec == 1 &&  $_REQUEST['tabHash'] = 'rec';
		$this->assign('pageTitle',$isRec ? L('PUBLIC_RECYCLE_BIN'):L('PUBLIC_FILE_MANAGEMENT'));
		$map['is_del'] = $isRec==1 ? 1:0;	//未删除的 
		!empty($_POST['attach_id']) && $map['attach_id'] = array('in',explode(',',$_POST['attach_id']));
		$_POST['from']>1 && $map['from'] = t($_POST['from']);
		!empty($_POST['name']) && $map['name'] = array('like','%'.t($_POST['name']).'%');

		$listData = model('Attach')->getAttachList($map,'*','attach_id desc',10);
	
		

		//$listData = model('Comment')->getCommentList($map,'comment_id desc',20);
		$image = array('png','jpg','gif','jpeg','bmp');

		foreach($listData['data'] as &$v){

			$user = model('User')->getUserInfo($v['uid']);
			$v['uid'] 	   = $user['space_link']; 
			$v['name']	   = in_array($v['extension'],$image) 
							? '<a href="'.U('widget/Upload/down',array('attach_id'=>$v['attach_id'])).'">'.
								"<img src='".UPLOAD_URL."/".$v['save_path'].$v['save_name']."' width='100'><br/>{$v['name']}</a>"
							:'<a href="'.U('widget/Upload/down',array('attach_id'=>$v['attach_id'])).'">'.$v['name'].'</a>';
			$v['size']	   = byte_format($v['size']);
			$v['from']     = $this->from[$v['from']];
			$v['ctime']    = date('Y-m-d H:i:s',$v['ctime']);
			$v['DOACTION'] = $isRec==0 ? "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['attach_id']},\"delAttach\",\"".L('PUBLIC_STREAM_DELETE')."\",\"".L('PUBLIC_FILE_STREAM')."\");'>".L('PUBLIC_STREAM_DELETE')."</a>"
										:"<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['attach_id']},\"AttachRecover\",\"".L('PUBLIC_RECOVER')."\",\"".L('PUBLIC_FILE_STREAM')."\")'>".L('PUBLIC_RECOVER')."</a>";
		}
		$this->displayList($listData);
	}
		
	//回收站
	public function attachRec(){
		$this->pageKey = APP_NAME.'_'.MODULE_NAME.'_attach';
        $this->searchPageKey = 'S_'.$this->pageKey ;
		$this->attach(1);
	}
	//恢复
	public function attachRecover(){
		echo json_encode( model('Attach')->doEditAttach($_POST['id'],'attachRecover',L('PUBLIC_RECOVER')) );
	}
	//假删除 
	public function delAttach(){
		echo json_encode( model('Attach')->doEditAttach($_POST['id'],'delAttach',L('PUBLIC_STREAM_DELETE')) );
	}
	//真删除 
	public function deleteAttach(){
		echo json_encode( model('Attach')->doEditAttach($_POST['id'],'deleteAttach',L('PUBLIC_REMOVE_COMPLETELY')) );
	}
	//TODO 临时放着 后面要移动到messagemodel中
	
	
	/**
	 * 举报管理
	 */
	public function denounce($map)
	{
		$_GET['id'] && $map['id'] = array( 'in',explode( ',', $_GET['id'] ) );
		$_GET['uid'] && $map['uid'] = array( 'in',explode( ',', $_GET['uid'] ) );
		$_GET['fuid'] && $map['fuid'] = array( 'in',explode( ',', $_GET['fuid'] ) );
		$_GET['from'] && $map['from'] = $_GET['from'];
		$map['state'] = $_GET['state']?$_GET['state']:'0';
		$data = model( 'Denounce' )->getFromList($map);
		$data['state'] = $map['state'];
		$this->assign($data);
		if( is_array($map) && sizeof($map)=='1' )unset($map);
		$this->assign($_GET);
		$this->assign('isSearch', empty($map)?'0':'1');
		$this->display('denounce');
	}

	/**
	 * 删除举报回收站内容
	 * @return integer 是否删除成功
	 */
	public function doDeleteDenounce()
	{
		// 判断参数
		if(empty($_POST['ids'])) {
			echo 0;
			exit ;
		}

		$data[] = L('PUBLIC_CONTENT_REPORT_DELETE');
		$map['id'] = array('in',t($_POST['ids']));
		$data[] = model('Denounce')->where($map)->findAll();
		// todo 记录日志
		echo model('Denounce')->deleteDenounce(t($_POST['ids']), intval($_POST['state'])) ? '1' : '0';
	}

	/**
	 * 撤销举报内容
	 * @return integer 是否撤销成功
	 */
	public function doReviewDenounce()
	{
		// 判断参数
		if(empty($_POST['ids'])) {
			echo 0;
			exit ;
		}

		$data[] = L('PUBLIC_CONTENT_REPORT_REVOKE');
		$map['id'] = array('in',t($_POST['ids']));
		$data[] = model('Denounce')->where($map)->findall();
		//todo 记录日志
		echo model('Denounce')->reviewDenounce( t($_POST['ids']) ) ? '1' : '0';
	}
	

	/**
	 * 话题管理
	 * @return void
	 */
	public function topic(){
		$this->assign('pageTitle','话题管理');
		// 设置列表主键
		$this->_listpk = 'topic_id';
		$this->pageTab[] = array('title'=>'话题管理','tabHash'=>'list','url'=>U('admin/Content/topic'));
		$this->pageTab[] = array('title'=>'推荐话题','tabHash'=>'recommendTopic','url'=>U('admin/Content/topic',array('recommend'=>1)));
		$this->pageTab[] = array('title'=>'添加话题','tabHash'=>'addTopic','url'=>U('admin/Content/addTopic'));
		$this->pageButton[] = array('title'=>'搜索话题','onclick'=>"admin.fold('search_form')");
		$this->pageButton[] = array('title'=>'批量屏蔽','onclick'=>"admin.setTopic(3,'',0)");
		$this->searchKey = array('topic_id','topic_name','recommend','lock');
		$this->opt['recommend'] = array('0'=>L('PUBLIC_SYSTEMD_NOACCEPT'),'1'=>'是','2'=>'否');
		$this->opt['essence'] = array('0'=>L('PUBLIC_SYSTEMD_NOACCEPT'),'1'=>'是','2'=>'否');
		$this->opt['lock'] = array('0'=>L('PUBLIC_SYSTEMD_NOACCEPT'),'1'=>'是','2'=>'否');
		$this->pageKeyList = array('topic_id','topic_name','note','domain','des','pic','topic_user','outlink','DOACTION');
		$listData = model('FeedTopicAdmin')->getTopic('',$_GET['recommend']);
		//dump($listData);exit;
		$this->displayList($listData);
	}

	/**
	 * 添加话题
	 * @return  void
	 */
	public function addTopic(){
		$this->assign('pageTitle','添加话题');
		$this->pageTab[] = array('title'=>'话题管理','tabHash'=>'list','url'=>U('admin/Content/topic'));
		$this->pageTab[] = array('title'=>'推荐话题','tabHash'=>'recommendTopic','url'=>U('admin/Content/topic',array('recommend'=>1)));
		$this->pageTab[] = array('title'=>'添加话题','tabHash'=>'addTopic','url'=>U('admin/Content/addTopic'));
		$this->pageKeyList = array('topic_name','note','domain','des','pic','topic_user','outlink','recommend');
		$topic['domain'] = SITE_URL.'/topics/'.'<input type="text" value="" name="domain" id="form_domain">';
		$this->opt['recommend'] = array('1'=>'是','0'=>'否');
		//$this->opt['essence'] = array('1'=>'是','0'=>'否');
		$this->notEmpty = array('topic_name','note');
		// 表单URL设置
		$this->savePostUrl = U('admin/Content/doAddTopic');
		$this->onsubmit = 'admin.aaa(this)';
		$this->displayConfig($topic);
	}

	/**
	 * 执行添加话题
	 * @return  void
	 */
	public function doAddTopic(){
		t($_POST['topic_name'])=="" && $this->error('话题名称不能为空');
		t($_POST['note'])=="" && $this->error('话题注释不能为空');
		$map['topic_name'] = t($_POST['topic_name']);
		if(model('FeedTopic')->where($map)->find()) $this->error('此话题已存在');
		if($_POST['domain']!=""){
			$map1['domain'] = t($_POST['domain']);
			if(model('FeedTopic')->where($map1)->find()) $this->error('此话题域名已存在');
		}
		if(h(t($_POST['outlink'])) != ""){
			$res = preg_match('/^(?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*)/',h($_POST['outlink']));
			if(!$res) $this->error('外链格式错误');
		}
		$res = model('FeedTopicAdmin')->addTopic($_POST);
		if($res){
			$this->assign('jumpUrl', U('admin/Content/topic'));
			$this->success(L('PUBLIC_ADD_SUCCESS'));
		}else{
			$this->error($user->getLastError());
		}
	}

	/**
	 * 设置话题为推荐、精华或屏蔽
	 * @return array 操作成功状态和提示信息 
	 */
	public function setTopic(){
		if(empty($_POST['topic_id'])){
			$return['status'] = 0;
			$return['data']   = '';
			echo json_encode($return);exit();
		}
		switch (intval($_POST['type'])) {
			case '1':
				$field = 'recommend';
				break;
			case '2':
				$field = 'essence';
				break;
			case '3':
				$field = 'lock';	
				break;
		}
		if(intval($_POST['value']) == 1){
			$value = 0;
		}else{
			$value = 1;
		}
		!is_array($_POST['topic_id']) && $_POST['topic_id'] = array($_POST['topic_id']);
		$map['topic_id'] = array('in',$_POST['topic_id']);
		$result = model('FeedTopic')->where($map)->setField($field,$value);
		if(!$result){
			$return['status'] = 0;
			$return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
		}else{
			$return['status'] = 1;
			$return['data']   = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
		}
		echo json_encode($return);exit();
	}

	/**
	 * 编辑话题
	 * @return  void
	 */
	public function editTopic(){
		$this->assign('pageTitle','编辑话题');
		$this->pageTab[] = array('title'=>'话题管理','tabHash'=>'list','url'=>U('admin/Content/topic'));
		$this->pageTab[] = array('title'=>'推荐话题','tabHash'=>'recommendTopic','url'=>U('admin/Content/topic',array('recommend'=>1)));
		$this->pageTab[] = array('title'=>'添加话题','tabHash'=>'addTopic','url'=>U('admin/Content/addTopic'));
		$this->pageTab[] = array('title'=>'编辑话题','tabHash'=>'editTopic','url'=>U('admin/Content/editTopic',array('topic_id'=>$_GET['topic_id'],'tabHash'=>'editTopic')));
		$this->pageKeyList = array('topic_id','topic_name','note','domain','des','pic','topic_user','outlink','recommend');
		$this->opt['recommend'] = array('1'=>'是','0'=>'否');
		//$this->opt['essence'] = array('1'=>'是','0'=>'否');
		$topic = model('FeedTopic')->where('topic_id='.$_GET['topic_id'])->find();
		if($topic['pic']){
			$pic = D('attach')->where('attach_id='.$topic['pic'])->find();
			$pic_url = $pic['save_path'].$pic['save_name'];
			$topic['pic_url'] = getImageUrl($pic_url); 
		}
		$topic['domain'] = SITE_URL.'/topics/'.'<input type="text" value="'.$topic['domain'].'" name="domain" id="form_domain">';
		$this->notEmpty = array('note');	
		$this->savePostUrl = U('admin/Content/doEditTopic');
		$this->onsubmit = 'admin.topicCheck(this)';
		$this->displayConfig($topic);
	}

	/**
	 * 执行编辑话题
	 */
	public function doEditTopic(){
		//$_POST['name']=="" && $this->error('话题名称不能为空');
		$_POST['note']=="" && $this->error('话题注释不能为空');
		//$map['topic_id'] = array('neq', $_POST['topic_id']);
		//$map['name'] = t($_POST['name']);
		//if(model('FeedTopic')->where($map)->find()) $this->error('此话题已存在');
		if($_POST['domain']!=""){
			$map1['topic_id'] = array('neq', $_POST['topic_id']);
			$map1['domain'] = t($_POST['domain']);
			if(model('FeedTopic')->where($map1)->find()) $this->error('此话题域名已存在');
		}
		if(h(t($_POST['outlink'])) != ""){
			$res = preg_match('/^(?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*)/',h($_POST['outlink']));
			if(!$res) $this->error('外链格式错误');
		}
		//$data['name'] = t($_POST['name']); 
		$data['note'] = t($_POST['note']);
		$data['domain'] = t($_POST['domain']); 
		$data['des'] = t($_POST['des']); 
		$data['pic'] = t($_POST['pic']); 
		$data['topic_user'] = t($_POST['topic_user']); 
		$data['outlink'] = t($_POST['outlink']); 
		$data['recommend'] = intval($_POST['recommend']); 
		if($data['recommend'] == 1){
			if(!D('feed_topic')->where('topic_id='.intval($_POST['topic_id']))->getField('recommend_time')){
				$data['recommend_time'] = time();
			}
		}else{
			if(D('feed_topic')->where('topic_id='.intval($_POST['topic_id']))->getField('recommend_time')){
				$data['recommend_time'] = 0;
			}
		}
		$data['essence'] = intval($_POST['essence']); 
		$res = D('feed_topic')->where('topic_id='.intval($_POST['topic_id']))->save($data);
		if($res!==false){
			$this->assign('jumpUrl', U('admin/Content/topic'));
			$this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
		}else{
			$this->error($user->getLastError());
		}
	}
}
