<?php
/**
 * 微博控制器
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
class FeedAction extends Action {

	/**
	 * 获取表情操作
	 * @return json 表情相关的JSON数据
	 */
	public function getSmile() {
		exit(json_encode(model('Expression')->getAllExpression()));
	}
	/**
	 * 返回好友分组列表
	 */
	public function getFriendGroup(){
		$usergroupList = model('FollowGroup')->getGroupList($this->mid);
		$grouplist = array();
		foreach ( $usergroupList as $g ){
			$group['gid'] = $g['follow_group_id'];
			$group['title'] = $g['title'];
			$grouplist[] = $group;
		}
		//相互关注
		$mutualusers = model('Follow')->getFriendsData($this->mid);
		//未分组
		$nogroupusers = model('FollowGroup')->getDefaultGroupByAll($this->mid);
		//其他分组
		$groupusers = array();
		if( $grouplist ){
			foreach ( $grouplist as $v ){
				$groupinfo = model('FollowGroup')->getUsersByGroup( $this->mid , $v['gid'] );
				$groupusers['group'.$v['gid']] = $groupinfo;
			}
		}
		$groupusers['group-1'] = getSubByKey( $mutualusers , 'fid');
		$groupusers['group-2'] = getSubByKey( $nogroupusers , 'fid' );
		$groups = array(array('gid'=>-1, 'title'=>'相互关注'), array('gid'=>-2, 'title'=>'未分组'));
		//关注列表
		$grouplist && $groups = array_merge( $groups , $grouplist);
		$users = array();
		foreach ($groupusers as &$gu){
			foreach ( $gu as $k=>$u){
				$gu[$k] = model('User')->getUserInfoForSearch( $u , 'uid,uname');
			}
		}
		$this->assign('groups' , $groups);
		$this->assign('groupusers' , $groupusers);
		$this->display();
	}
	/**
	 * 发布微博操作，用于AJAX
	 * @return json 发布微博后的结果信息JSON数据
	 */
	public function PostFeed()
	{	
		// 返回数据格式
		$return = array('status'=>1, 'data'=>'');
		// 用户发送内容
		$d['content'] = isset($_POST['content']) ? h($_POST['content']) : '';
		// 原始数据内容
		$d['body'] = h($_POST['body']);
		// 滤掉话题两端的空白
		$d['body'] = preg_replace("/#[\s]*([^#^\s][^#]*[^#^\s])[\s]*#/is",'#'.trim("\${1}").'#',$d['body']);	
		// 附件信息
		$d['attach_id'] = trim(t($_POST['attach_id']), "|");
		!empty($d['attach_id']) && $d['attach_id'] = explode('|', $d['attach_id']);
		// 发送微博的类型
		$type = t($_POST['type']);
		// 所属应用名称
		$app = isset($_POST['app_name']) ? t($_POST['app_name']) : APP_NAME;			// 当前动态产生所属的应用
		if($data = model('Feed')->put($this->uid, $app, $type, $d)) {
			// 发布邮件之后添加积分
			model('Credit')->updateUserCredit($this->uid,'weibo_add');
			// 微博来源设置
			$data['from'] = getFromClient($data['from'], $data['app']);
			$this->assign($data);
			$return['data'] = $this->fetch();
			// 微博ID
			$return['feedId'] = $data['feed_id'];
			//8.28 添加话题
            model('FeedTopic')->addTopic(html_entity_decode($d['body'], ENT_QUOTES), $data['feed_id'], $type);
			//更新用户最后发表的微博
            $last['last_feed_id'] = $data['feed_id'];
			$last['last_post_time'] = $_SERVER['REQUEST_TIME'];
            model( 'User' )->where('uid='.$this->uid)->save($last);

            // 添加微博到投稿数据中
            $channelId = t($_POST['channel_id']);
            if(!empty($channelId)) {
            	D('Category', 'channel')->setChannel($data['feed_id'], $channelId, false);
            }
		} else {
			$return = array('status'=>0,'data'=>model('Feed')->getError());
		}

		exit(json_encode($return));
	}
	
	/**
	 * 分享/转发微博操作，需要传入POST的值
	 * @return json 分享/转发微博后的结果信息JSON数据
	 */
	public function shareFeed()
	{
		// 获取传入的值
		$post = $_POST;
		// 安全过滤
		foreach($post as $key => $val) {
			$post[$key] = t($post[$key]);
		}
		// 判断资源是否删除
		if(empty($post['curid'])) {
			$map['feed_id'] = $post['sid'];
		} else {
			$map['feed_id'] = $post['curid'];
		}
		$map['is_del'] = 0;
		$isExist = model('Feed')->where($map)->count();
		if($isExist == 0) {
			$return['status'] = 0;
			$return['data'] = '内容已被删除，转发失败';
			exit(json_encode($return));
		}
		// 过滤内容值
		$post['body'] = h($post['body']);
		// 进行分享操作
		$return = model('Share')->shareFeed($post, 'share');
		if($return['status'] == 1) {
			// 添加积分
			model('Credit')->updateUserCredit($this->uid,'weibo_share');
			$this->assign($return['data']);
			$return['data'] =  $this->fetch('PostFeed');
		}
		exit(json_encode($return));
	}

	/**
	 * 删除微博操作，用于AJAX
	 * @return json 删除微博后的结果信息JSON数据
	 */	
	public function removeFeed() {
		$return = array('status'=>0,'data'=>L('PUBLIC_DELETE_FAIL'));			// 删除失败
		$feed_id = intval($_POST['feed_id']);
		if(empty($feed_id)) {
			exit(json_encode($return));
		}
		// TODO:权限验证
		$return = model('Feed')->doEditFeed($feed_id, 'delFeed', '');
		$return['data'] = ($return['status'] == 0) ? L('PUBLIC_DELETE_FAIL') : L('PUBLIC_DELETE_SUCCESS');		// 删除失败，删除成功
		$return['status'] == 1 && model('FeedTopic')->deleteWeiboJoinTopic($feed_id);
		// 删除频道关联信息
		D('Category', 'channel')->deleteChannelLink($feed_id);
		// 删除@信息
		model('Atme')->setAppName('Public')->setAppTable('feed')->deleteAtme(null, $feed_id, null);

		exit(json_encode($return));
	}	
}