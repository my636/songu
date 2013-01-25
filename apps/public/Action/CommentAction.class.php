<?php
/**
 * 我的评论控制器
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
class CommentAction extends Action {
		
	/**
	 * 我的评论页面
	 */
	public function index() {
		// 安全过滤
		$type = t($_GET['type']);
		if($type == 'send') {	
			$map['uid'] = $this->uid;	
		} else {
			//获取未读评论的条数
			$this->assign('unread_comment_count',model('UserData')->where('uid='.$this->mid." and `key`='unread_comment'")->getField('value'));
			// 收到的
			$map['_string'] = " (to_uid = '{$this->uid}' OR app_uid = '{$this->uid}') AND uid !=".$this->uid;
		}
		// 类型描述术语 TODO:放到统一表里面
		$d['tabHash'] = array(
							'feed'	=> L('PUBLIC_WEIBO')			// 微博
						);

		$d['tab'] = model('Comment')->getTab($map);	 
		$this->assign($d);	

		// 安全过滤
		$t = t($_GET['t']);
		!empty($t) && $map['table'] = $t;
		$list = model('Comment')->setAppName($_GET['app_name'])->getCommentList($map,'comment_id DESC',null,true);
		foreach($list['data'] as $k=>$v){
			if($v['sourceInfo']['app']=='weiba'){
				$list['data'][$k]['sourceInfo']['source_body'] = str_replace($v['sourceInfo']['row_id'], $v['comment_id'], $v['sourceInfo']['source_body']);
			}
		}
		model('UserCount')->resetUserCount($this->mid, 'unread_comment',  0);
		$this->assign('list', $list);

		$this->setTitle(L('PUBLIC_COMMENT_INDEX'));					// 我的评论
		$this->display();
	}

	/**
	 * 我的评论中，回复弹窗页面
	 */
	public function reply() {
		$var = $_GET;

		$var['initNums'] = model('Xdata')->getConfig('weibo_nums', 'feed');
		$var['commentInfo'] = model('Comment')->getCommentInfo($var['comment_id'], false);
		$var['canrepost']  = $var['commentInfo']['table'] == 'feed'  ? 1 : 0;
		$var['cancomment'] = 1;
		// 获取原作者信息
		$rowData = model('Feed')->get(intval($var['commentInfo']['row_id']));
		$appRowData = model('Feed')->get($rowData['app_row_id']);
		$var['user_info'] = $appRowData['user_info'];
		// 微博类型
		$var['feedtype'] = $rowData['type'];
		// $var['cancomment_old'] = ($var['commentInfo']['uid'] != $var['commentInfo']['app_uid'] && $var['commentInfo']['app_uid'] != $this->uid) ? 1 : 0;
		$var['initHtml'] = L('PUBLIC_STREAM_REPLY').'@'.$var['commentInfo']['user_info']['uname'].' ：';		// 回复

		$this->assign($var);
		$this->display();
	}
}