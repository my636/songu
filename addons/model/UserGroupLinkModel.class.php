<?php
/**
 * 用户组关联模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
class UserGroupLinkModel extends Model {

	protected $tableName = 'user_group_link';
	protected $fields =	array(0 =>'id',1=>'uid',2=>'user_group_id');
	
	/**
	 * 转移用户的用户组
	 * @param string $uids 用户UID，多个用“，”分割
	 * @param string $user_group_id 用户组ID，多个用“，”分割
	 * @return boolean 是否转移成功
	 */
	public function domoveUsergroup($uids, $user_group_id) {
		// 验证数据
		if(empty($uids) && empty($user_group_id)) {
			$this->error = L('PUBLIC_USER_GROUP_EMPTY');			// 用户组或用户不能为空
			return false;
		}
		$uids = explode(',', $uids);
		$user_group_id = explode(',', $user_group_id);
		$uids = array_unique(array_filter($uids));
		$user_group_id = array_unique(array_filter($user_group_id));
		if(!$uids || !$user_group_id) {
			return false;
		}
		$map['uid'] = array('IN', $uids);
		$this->where($map)->delete();
		foreach($uids as $v) {
			$save = array();
			$save['uid'] = $v;
			foreach($user_group_id as $gv){
				$save['user_group_id'] = $gv;
				$this->add($save);
			}
		}
		model('User')->cleanCache($uids);

		return true;
	}

	/**
	 * 获取用户的用户组信息
	 * @param array $uids 用户UID数组
	 * @return array 用户的用户组信息
	 */
	public function getUserGroup($uids) {
		$uids = !is_array($uids) ? explode(',', $uids) : $uids;
		$uids = array_unique(array_filter($uids));
		if(!$uids) return false;
		$map['uid'] = array('IN', $uids);
		$list = $this->where($map)->findAll();
		$return = array();
		foreach($list as $v) {
			$return[$v['uid']][] = $v['user_group_id'];
		}
		return $return;
	}

	/**
	 * 获取用户所在用户组详细信息
	 * @param array $uids 用户UID数组
	 * @return array 用户的用户组详细信息
	 */
	public function getUserGroupData($uids){
		$uids = !is_array($uids) ? explode(',', $uids) : $uids;
		$uids = array_unique(array_filter($uids));
		if(!$uids) return false;
		$userGids = $this->getUserGroup($uids);
		//return $userGids;exit;
		foreach($userGids as $k=>$v){
			$userGroupData[$k] = model('UserGroup')->getUserGroupByGids($userGids[$k]);
			foreach($userGroupData[$k] as $key => $value) {
				if($value['user_group_icon'] == -1) {
					unset($userGroupData[$k][$key]);
					continue;
				}
				$userGroupData[$k][$key]['user_group_icon_url'] = THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'];
			}
		}
		return $userGroupData;
	}
}
