<?php
/**
 * 用户统计数据模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
class UserDataModel extends Model {

	protected $tableName = 'user_data';
	protected $fields = array(0=>'id',1=>'uid',2=>'key',3=>'value',4=>'mtime');
	protected $uid = '';
	
	/**
	 * 初始化方法，设置默认用户信息
	 * @return void
	 */
	public function _initialize() {
		$this->uid = $GLOBALS['ts']['mid'];
	}

	/**
	 * 设置用户UID
	 * @param integer $uid 用户UID
	 * @return object 用户统计数据对象
	 */
	public function setUid($uid) {
		$this->uid = $uid;
		return $this;
	}

	/**
	 * 更新某个用户的指定Key值的统计数目
	 * Key值：
	 * feed_count：微博总数
	 * weibo_count：微博数
	 * favorite_count：收藏数
	 * following_count：关注数
	 * follower_count：粉丝数
	 * unread_comment：评论未读数
	 * unread_atme：@Me未读数
	 * @param string $key Key值
	 * @param integer $nums 更新的数目
	 * @param boolean $add 是否添加数目，默认为true
	 * @param integer $uid 用户UID
	 * @return array 返回更新后的数据
	 */
	public function updateKey($key, $nums, $add = true, $uid = '') {
		if($nums == 0) {
			$this->error = L('PUBLIC_MODIFY_NO_REQUIRED');			// 不需要修改
			return false;
		}
		// 若更新数目小于0，则默认为减少数目
		$nums < 0 && $add = false;
		$key = t($key);
		// 获取当前设置用户的统计数目
		$data = $this->getUserData($uid);
		if(empty($data) || !$data) {
			$data = array();	
			$data[$key] = $nums;
		} else {
			$data[$key] = $add ? ($data[$key] + abs($nums)) :($data[$key] - abs($nums));
		}

		$data[$key] < 0 && $data[$key] = 0;
		
		$map['uid'] = empty($uid) ? $this->uid : $uid;
		$map['key'] = $key;
		$this->where($map)->limit(1)->delete();
		$map['value'] = $data[$key];
		$map['mtime'] = date('Y-m-d H:i:s'); 
		$this->add($map);
		model('Cache')->rm('UserData_'.$map['uid']);

		return $data;
	}
	
	/**
	 * 设置指定用户指定Key值的统计数目
	 * @param integer $uid 用户UID
	 * @param string $key Key值
	 * @param integer $value 设置的统计数值
	 * @return void
	 */
	public function setKeyValue($uid, $key, $value) {
		$map['uid'] = $uid;
		$map['key'] = $key;
		$this->where($map)->delete();
		$map['value'] = intval($value);
		$this->add($map);
		// 清掉该用户的缓存
		model('Cache')->rm('UserData_'.$uid);
	}
	
	/**
	 * 获取指定用户的统计数据
	 * @param integer $uid 用户UID
	 * @return array 指定用户的统计数据
	 */
	public function getUserData($uid = '') {
		// 默认为设置的用户
		if(empty($uid)) {
			$uid = $this->uid;
		}
		if(($data = model('Cache')->get('UserData_'.$uid)) === false) {
			$map['uid'] = $uid;
			$data = array();
			$list = $this->where($map)->findAll();
			if(!empty($list)) {
				foreach($list as $v) {
					$data[$v['key']] = (int)$v['value']; 
				}
			}
			// 新粉丝的数目统计
			$map = array();
			$map['fid'] = $uid; 
			$map['ctime'] = array('GT', intval($data['view_follower_time']));
			$data['new_folower_count'] = model('Follow')->where($map)->count();
			model('Cache')->set('UserData_'.$uid, $data, 60);
		}

		return $data;
	}
	
	/**
	 * 批量获取多个用户的统计数目
	 * @param array $uids 用户UID数组
	 * @return array 多个用户的统计数目
	 */
	public function getUserDataByUids($uids) {
		$return = $notCache = array();
		$data = model('Cache')->getList('UserData_', $uids);
		// 判断是否存在没有缓存的数据
		foreach($uids as $k => $v) {
			if(!isset($data[$v]) || empty($data[$v])) {
				$notCache[] = $v; 
			}
		}
		// 如果存在没有缓存的数据，获取缓存数据，在进行排序
		if(!empty($notCache)) {
			foreach($notCache as $v) {
				$data[$v] = $this->getUserData($v);
			}
			// 重新排序
			foreach($uids as $v){
				$return[$v] = $data[$v];
			}
			return $return;
		} else {
			return $data;
		}
	}

	/**
	 * 手动统计更新用户数据，微博、关注、粉丝、收藏
	 * @return void
	 */
	public function updateUserData() {
		set_time_limit(0);
		$uids = model('User')->getAsFieldArray('uid');
		foreach($uids as $uid) {
			// 获取总微博数目
			$feedCount = model('Feed')->where('uid='.$uid)->count();
			$this->setKeyValue($uid, 'feed_count', $feedCount);
			// 获取微博数目
			$weiboCount = model('Feed')->where('uid='.$uid.' AND is_del=0')->count();
			$this->setKeyValue($uid, 'weibo_count', $weiboCount);
			// 收藏数目
			$favoriteCount = model('Collection')->where('uid='.$uid)->count();
			$this->setKeyValue($uid, 'favorite_count', $favoriteCount);
			// 关注数目
			$followingCount = model('Follow')->where('uid='.$uid)->count();
			$this->setKeyValue($uid, 'following_count', $followingCount);
			// 粉丝数目
			$followerCount = model('Follow')->where('fid='.$uid)->count();
			$this->setKeyValue($uid, 'follower_count', $followerCount);
		}
	}
}
