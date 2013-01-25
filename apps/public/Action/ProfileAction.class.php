<?php
/**
 * ProfileAction 个人档案模块
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
class ProfileAction extends Action 
{
	/**
	 * _initialize 模块初始化
	 * @return void
	 */
	protected function _initialize() {
		//短域名判断
		if(!isset($_GET['uid']) || empty($_GET['uid'])){
			$this->uid = $this->mid;
		}elseif(is_numeric($_GET['uid'])){
			$this->uid = intval($_GET['uid']);
		}else{
			$map['domain'] = t($_GET['uid']);
			$this->uid = model('User')->where($map)->getField('uid');
		}
		$this->assign('uid',$this->uid);
	}

	/**
	 * 隐私设置
	 */
	public function privacy($uid){
		if($this->mid != $uid){
			$privacy = model('UserPrivacy')->getPrivacy($this->mid,$uid);
			return $privacy;
	    }else{
	    	return true;
	    }
	}

	/**
	 * 个人档案展示页面
	 */
	public function index() {
		// 获取用户信息
		$user_info = model('User')->getUserInfo($this->uid);
		// 用户为空，则跳转用户不存在
		if(empty($user_info)) {
			$this->error(L('PUBLIC_USER_NOEXIST'));
		}
		//个人空间头部
		$this->_top();
		//判断隐私设置
		$userPrivacy = $this->privacy($this->uid);
		if($userPrivacy['space'] !== 1){
			$this->_sidebar();
			// 加载微博筛选信息
			$d['feed_type'] = t($_GET['feed_type']) ? t($_GET['feed_type']) : '';
			$d['feed_key']  = t($_GET['feed_key']) ? t($_GET['feed_key']) : '';
			$this->assign($d);
		}else{
			$this->_assignUserInfo($this->uid);
		}
		$this->assign('userPrivacy',$userPrivacy);
			
		// 设置页面标题
		if($this->mid == $this->uid) {
		    $this->setTitle( L('PUBLIC_PROFILE_INDEX') );
		} else {
		    $this->setTitle( L('PUBLIC_TA_WEIBO',array('user'=>$user_info['uname'])) );
		}
		$this->display();
	}

	/**
	 * 获取指定应用的信息
	 * @return void
	 */
	public function appprofile() {
		$user_info = model('User')->getUserInfo($this->uid);
		if(empty($user_info)){
			$this->error(L('PUBLIC_USER_NOEXIST'));
		}

		$d['widgetName'] = ucfirst(t($_GET['appname'])).'Profile';
		$d['widgetAttr'] = $_GET;
		$d['widgetAttr']['widget_appname'] = t($_GET['appname']);
		$this->assign($d);

		$this->_assignUserInfo(array($this->uid));
		($this->mid != $this->uid) && $this->_assignFollowState($this->uid);
		$this->display();
	}

	/**
	 * 获取用户详细资料
	 * @return void
	 */
    public function data() {
    	// 获取用户信息
		$user_info = model('User')->getUserInfo($this->uid);
		// 用户为空，则跳转用户不存在
		if(empty($user_info)) {
			$this->error(L('PUBLIC_USER_NOEXIST'));
		}
		//个人空间头部
		$this->_top();
		//判断隐私设置
		$userPrivacy = $this->privacy($this->uid);
		if($userPrivacy['space'] !== 1){
			$this->_sidebar();
			//档案类型
			$ProfileType = model('UserProfile')->getCategoryList();
			$this->assign('ProfileType',$ProfileType);
			//个人资料
			$this->_assignUserProfile($this->uid);
			// 获取用户职业信息
			$userCategory = model('UserCategory')->getRelatedUserInfo($this->uid);
			if(!empty($userCategory)) {
				foreach($userCategory as $value) {
					$user_category .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
				}
			}
			$this->assign('user_category',$user_category);
		}else{
			$this->_assignUserInfo($this->uid);
		}
		$this->assign('userPrivacy',$userPrivacy);

   		if($this->mid == $this->uid){
		    $this->setTitle( L('PUBLIC_PROFILE_DATA') );
		}else{
		    $this->setTitle( L('PUBLIC_TA_PROFILE',array('user'=>$user_info['uname'])) );
		}
		
		$this->display();		
    }
	
	/**
	 * 获取指定用户的某条动态
	 * @return void
	 */
	public function feed() {
		

		if(empty($_GET['feed_id'])){
			$this->error(L('PUBLIC_INFO_ALREADY_DELETE_TIPS'));
		}

		// 获取用户信息
		$user_info = model('User')->getUserInfo($this->uid);
			
		//个人空间头部
		$this->_top();	
		//判断隐私设置
		$userPrivacy = $this->privacy($this->uid);
		if($userPrivacy['space'] !== 1){
			$this->_sidebar();
			$feedInfo = model('Feed')->get(intval($_GET['feed_id']));
			if($feedInfo['is_del'] == '1'){
				$this->error(L('PUBLIC_NO_RELATE_WEIBO'));exit();
			}
			
			$weiboSet = model('Xdata')->get('admin_Config:feed');
	        $a['initNums']         = $weiboSet['weibo_nums'];
	        $a['weibo_type']       = $weiboSet['weibo_type'];
	        $a['weibo_premission'] = $weiboSet['weibo_premission'];
			$this->assign($a);
			switch ( $feedInfo['app'] ){
        		case 'weiba':
        			$feedInfo['from'] = getFromClient(0 , $feedInfo['app'] , '微吧');
        			break;
        		default:
        			$feedInfo['from'] = getFromClient( $from , $feedInfo['app']);
        			break;
        	}
			//$feedInfo['from'] = getFromClient( $feedInfo['from'] , $feedInfo['app']);
			$this->assign('feedInfo',$feedInfo);
		}else{
			$this->_assignUserInfo($this->uid);
		}
		$this->assign('userPrivacy',$userPrivacy);


		
			
		$this->display();
	}
	
	/**
	 * 获取用户关注列表
	 * @return void
	 */
	public function following() {
		// 获取用户信息
		$user_info = model('User')->getUserInfo($this->uid);
		// 用户为空，则跳转用户不存在
		if(empty($user_info)) {
			$this->error(L('PUBLIC_USER_NOEXIST'));
		}
		//个人空间头部
		$this->_top();
		//判断隐私设置
		$userPrivacy = $this->privacy($this->uid);
		if($userPrivacy['space'] !== 1){
			$following_list = model('Follow')->getFollowingList($this->uid, t($_GET['gid']), 20);
			$fids = getSubByKey($following_list['data'], 'fid');
			if($fids){
				$uids = array_merge($fids, array($this->uid));
			}else{
				$uids = array($this->uid);
			}
			// 获取用户组信息
			$userGroupData = model('UserGroupLink')->getUserGroupData($uids);
			$this->assign('userGroupData',$userGroupData);
			$this->_assignFollowState($uids);
			$this->_assignUserInfo($uids);
			$this->_assignUserProfile($uids);
			$this->_assignUserTag($uids);
			$this->_assignUserCount($fids);
			// 关注分组
			($this->mid == $this->uid) && $this->_assignFollowGroup($fids);
			$this->assign('following_list', $following_list);		
		}else{
			$this->_assignUserInfo($this->uid);
		}
		$this->assign('userPrivacy',$userPrivacy);		
		if($this->mid == $this->uid){
		    $this->setTitle( L('PUBLIC_PROFILE_FOLLOWING') );
		}else{
		    $this->setTitle( L('PUBLIC_TA_FOLLOWING',array('user'=>$GLOBALS['ts']['_user']['uname'])) );
		}
		$this->display();
	}

    /**
     * 获取用户粉丝列表
     * @return void
     */
	public function follower() {
		// 获取用户信息
		$user_info = model('User')->getUserInfo($this->uid);
		// 用户为空，则跳转用户不存在
		if(empty($user_info)) {
			$this->error(L('PUBLIC_USER_NOEXIST'));
		}
		//个人空间头部
		$this->_top();
		//判断隐私设置
		$userPrivacy = $this->privacy($this->uid);
		if($userPrivacy['space'] !== 1){
			$follower_list = model('Follow')->getFollowerList($this->uid, 20);
			$fids = getSubByKey($follower_list['data'], 'fid');	
			if($fids){
				$uids = array_merge($fids, array($this->uid));
			}else{
				$uids = array($this->uid);
			}
			// 获取用户用户组信息
			$userGroupData = model('UserGroupLink')->getUserGroupData($uids);
			$this->assign('userGroupData',$userGroupData);
			$this->_assignFollowState($uids);
			$this->_assignUserInfo($uids);
			$this->_assignUserProfile($uids);
			$this->_assignUserTag($uids);
			$this->_assignUserCount($fids);
			//更新查看粉丝时间
			if($this->uid == $this->mid){
				$t = time()-intval($GLOBALS['ts']['_userData']['view_follower_time']);//避免服务器时间不一致 
				model('UserData')->setUid($this->mid)->updateKey('view_follower_time',$t,true);	
			}
			$this->assign('follower_list', $follower_list);
		}else{
			$this->_assignUserInfo($this->uid);
		}
		$this->assign('userPrivacy',$userPrivacy);		
		if($this->mid == $this->uid){
		    $this->setTitle( L('PUBLIC_PROFILE_FOLLWER') );
		}else{
		    $this->setTitle( L('PUBLIC_TA_FOLLWER',array('user'=>$GLOBALS['ts']['_user']['uname'])) );
		}
		$this->display();
	}
		
	/**
	 * 批量获取用户的相关信息加载
	 * @param string|array $uids 用户ID
	 */
	private function _assignUserInfo($uids) {
		!is_array($uids) && $uids = explode(',', $uids);
		$user_info = model('User')->getUserInfoByUids($uids);
		$this->assign('user_info', $user_info);
		//dump($user_info);exit;
	}

	/**
	 * 获取用户的档案信息和资料配置信息
	 * @param  mix uids 用户uid
	 * @return void
	 */
	private function _assignUserProfile($uids) {	
		$data['user_profile'] = model('UserProfile')->getUserProfileByUids($uids);
		$data['user_profile_setting'] = model('UserProfile')->getUserProfileSetting(array('visiable' => 1));
		$this->assign($data);
	}

	/**
	 * 根据指定应用和表获取指定用户的标签
	 * @param array uids 用户uid数组
	 * @return void
	 */
	private function _assignUserTag($uids) {
		$user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($uids);
		$this->assign('user_tag', $user_tag);
	}

	/**
	 * 批量获取多个用户的统计数目
	 * @param  array $uids 用户uid数组
	 * @return void
	 */
	private function _assignUserCount($uids) {
		$user_count = model('UserData')->getUserDataByUids($uids);
		$this->assign('user_count', $user_count);
	}

	/**
	 * 批量获取用户uid与一群人fids的彼此关注状态
	 * @param  array $fids 用户uid数组
	 * @return void
	 */
	private function _assignFollowState($fids = null) {
		// 批量获取与当前登录用户之间的关注状态
		$follow_state = model('Follow')->getFollowStateByFids($this->mid, $fids);
		$this->assign('follow_state', $follow_state);
	//	dump($follow_state);exit;
	}

	/**
	 * 获取用户最后一条微博数据
	 * @param mix uids 用户uid
	 * @param void
	 */
	private function _assignUserLastFeed($uids) {
		return true;//目前不需要这个功能
		$last_feed = model('Feed')->getLastFeed($uids);
		$this->assign('last_feed',$last_feed);
	}

	/**
	 * 调整分组列表
	 * @param  array $fids 指定用户关注的用户列表
	 * @return void
	 */
	private function _assignFollowGroup($fids) {
    	$follow_group_list = model('FollowGroup')->getGroupList($this->mid);
    	//调整分组列表
    	if(!empty($follow_group_list)){
	    	$group_count = count($follow_group_list);
	    	for($i=0;$i<$group_count;$i++){
	    		if($follow_group_list[$i]['follow_group_id'] != $data['gid']){
	    			$follow_group_list[$i]['title'] = (strlen($follow_group_list[$i]['title'])+mb_strlen($follow_group_list[$i]['title'],'UTF8'))/2>8?getShort($follow_group_list[$i]['title'],3).'...':$follow_group_list[$i]['title'];
	    		}
	    		if($i<2){
	    			$data['follow_group_list_1'][] = $follow_group_list[$i];
	    		}else{
	    			if($follow_group_list[$i]['follow_group_id'] == $data['gid']){
	    				$data['follow_group_list_1'][2]  = $follow_group_list[$i];
	    				continue;
	    			}
	    			$data['follow_group_list_2'][] = $follow_group_list[$i];
	    		}
	    	}
	    	if(empty($data['follow_group_list_1'][2]) && !empty($data['follow_group_list_2'][0])){
	    		$data['follow_group_list_1'][2] = $data['follow_group_list_2'][0];
	    		unset($data['follow_group_list_2'][0]);
	    	}
    	}

    	$data['follow_group_status'] = model('FollowGroup')->getGroupStatusByFids($this->mid, $fids);
		
    	$this->assign($data);
	}

	/**
	 * 个人主页头部数据
	 * @return void
	 */
	public function _top(){
		//获取用户组信息
		$userGroupData = model('UserGroupLink')->getUserGroupData($this->uid);
		$this->assign('userGroupData',$userGroupData);
		// 获取用户积分信息
		$userCredit = model('Credit')->getUserCredit($this->uid);
		$this->assign('userCredit',$userCredit);
		// 加载用户关注信息
		($this->mid != $this->uid) && $this->_assignFollowState($this->uid);
		// 获取用户统计信息
		$userData = model('UserData')->getUserData($this->uid);
		$this->assign('userData', $userData);
	}

	/**
	 * 个人主页右侧
	 * @return void
	 */
	public function _sidebar(){
		//判断用户是否已认证
		$isverify = D('user_verified')->where('verified=1 AND uid='.$this->uid)->find();
		if($isverify){
			$this->assign('verifyInfo',$isverify['info']);
		}
		// 加载用户标签信息
		$this->_assignUserTag(array($this->uid));
		// 加载关注列表
		$sidebar_following_list = model('Follow')->getFollowingList($this->uid, null, 12);
		$this->assign('sidebar_following_list', $sidebar_following_list);
		//dump($sidebar_following_list);exit;
		// 加载粉丝列表
		$sidebar_follower_list = model('Follow')->getFollowerList($this->uid, 12);
		$this->assign('sidebar_follower_list', $sidebar_follower_list);
		// 加载用户信息
		$uids = array($this->uid);
		
		$followingfids = getSubByKey($sidebar_following_list['data'], 'fid');
		$followingfids && $uids = array_merge($uids,$followingfids);
		
		$followerfids = getSubByKey($sidebar_follower_list['data'], 'fid');
		$followerfids && $uids = array_merge($uids,$followerfids);
		
		
		$this->_assignUserInfo($uids);
	}
}