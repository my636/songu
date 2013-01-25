<?php
/**
 * 
 * @author jason
 *
 */
class UserApi extends Api{

	/**
	 * 按用户UID或昵称返回用户资料，同时也将返回用户的最新发布的微博
	 * 
	 */
	function show(){
		
		//$this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
		//用户基本信息
		if(empty($this->user_id) && empty($this->user_name)){
			return false;
		}
		if(empty($this->user_id)){
			$data = model('User')->getUserInfoByName($this->user_name);	
			$this->user_id = $data['uid'];
		}else{
			$data = model('User')->getUserInfo($this->user_id);	
		}
		if(empty($data)){
			return false;
		}
		$data['sex'] = $data['sex'] ==1 ? '男':'女';
		
		$data['profile'] = model('UserProfile')->getUserProfileForApi($this->user_id);

		$profileHash = model('UserProfile')->getUserProfileSetting();
		$data['profile']['email'] = array('name'=>'邮箱','value'=>$data['email']);
		foreach(UserProfileModel::$sysProfile as $k){
			if(!isset($data['profile'][$k])){
				$data['profile'][$k] = array('name'=>$profileHash[$k]['field_name'],'value'=>'');
			}
		}

		//用户统计信息
		$defaultCount =  array('following_count'=>0,'follower_count'=>0,'feed_count'=>0,'favorite_count'=>0,'unread_atme'=>0,'weibo_count'=>0);

		$count   = model('UserData')->getUserData($this->user_id);
		if(empty($count)){
			$count = array();	
		}
		$data['count_info'] = array_merge($defaultCount,$count);
		
		//用户标签
		$data['user_tag'] = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($this->user_id);
		$data['user_tag'] = empty($data['user_tag']) ? '' : implode('、',$data['user_tag']);
		//关注情况
		$followState  = model('Follow')->getFollowState($this->mid,$this->user_id); 
		$data['follow_state'] = $followState;
		
		//最后一条微博
		$lastFeed = model('Feed')->getLastFeed($this->user_id);
		$data['last_feed'] = $lastFeed;

		// 判断用户是否被登录用户收藏通讯录
		$data['isMyContact'] = 0;
		if($this->user_id != $this->mid) {
			$cmap['uid'] = $this->mid;
			$cmap['contact_uid'] = $this->user_id;
			$myCount = D('Contact', 'contact')->where($cmap)->count();
			if($myCount == 1) {
				$data['isMyContact'] = 1;
			}
		}

		return $data;
	}
		
	/**
	 * 上传头像 API
	 * 传入的头像变量 $_FILES['Filedata']
	 */
	function upload_face(){
		$dAvatar = model('Avatar');
		$dAvatar->init($this->mid); // 初始化Model用户id
		$d['attach_type'] = 'avatar';
		$d['upload_type'] = 'image';
		$res = $dAvatar->upload($d);
		if($res['status'] == 1){
			$_POST['picurl'] = $res['data']['picurl'];
			$_POST['w'] = $res['data']['picwidth'];
			$_POST['h'] = $res['data']['picheight'];
			$_POST['x1'] = $_POST['y1'] = 0;
			$_POST['x2'] = $_POST['w'];
			$_POST['y2'] =  $_POST['h'];
			$r = $dAvatar->dosave();
			model('User')->cleanCache($this->mid);
			if($r['status'] == 1){
				return '1';
			}else{
				return '0';
			}
		}else{
			return '0';
		}
	}

	/**
	 *	关注一个用户
	 */
	public function follow_create(){
		if(empty($this->mid) || empty($this->user_id)){
			return 0;
		}

		$r = model('Follow')->doFollow($this->mid,$this->user_id);
		if(!$r){
			return model('Follow')->getFollowState($this->mid,$this->user_id);
			//return 0;
		}
		return $r;
	}

	/**
	 * 取消关注
	 */
	public function follow_destroy(){
		if(empty($this->mid) || empty($this->user_id)){
			return 0;
		}

		$r = model('Follow')->unFollow($this->mid,$this->user_id);
		if(!$r){
			return model('Follow')->getFollowState($this->mid,$this->user_id);
			//return 0;
		}
		return $r;
	}

	/**
	 * 用户粉丝列表
	 */
	public function user_followers(){
		$this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
	
		return model('Follow')->getFollowerListForApi($this->mid,$this->user_id,$this->since_id,$this->max_id,$this->count,$this->page);
	}

	/**
	 * 获取用户关注的人列表
 	 */
	public function user_following(){
		$this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
		return model('Follow')->getFollowingListForApi($this->mid,$this->user_id,$this->since_id,$this->max_id,$this->count,$this->page);
	}

	/**
	 * 获取用户的朋友列表
	 * 
	 */
	public function user_friends()
	{
		$this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
		return model('Follow')->getFriendsForApi($this->mid, $this->user_id, $this->since_id, $this->max_id, $this->count, $this->page);
	}

}