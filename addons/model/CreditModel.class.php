<?php
/**
 * 积分模型 - 数据对象模型
 * @example
 * $credit = model('Credit')->updateUserCredit($uid,'weibo_demo');
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
class CreditModel extends Model {

	protected $tableName = 'credit_node';
	protected $fields = array(0=>'id',1=>'appname',2=>'action',3=>'info','_autoinc'=>true,'_pk'=>'id');

	public $creditSetting = array();			// 所有设置的值
	private $keyInTable = 'credit_user';		// 积分等字段默认所在的表
	private $app = 'admin';						// 当前模型对应的应用
	private $type = 'experience';				// 等级的图标类型
	public static $_type = array();				// 未使用
   
	/**
	 * 设置用户积分的存储表
	 * @param string $table 积分存储表名
	 * @return object 积分模型对象
	 */
    public function setKeyTable($table = 'credit_user') {
    	if(empty($table)) {
    		throw_exception('table is empty');
    	}
    	$this->keyInTable = $table;
    	return $this;
    }

    /**
     * 获取积分设置
     * @return array 积分设置
     */
	public function getCreditSet() {
		// TODO:后期要考虑缓存
		$defaultList = $this->getDefaultList();			// 节点数据
		if($setData = $this->getSetData()) {
			if(!empty($setData)) {
				foreach($setData as $app => $node) {
					foreach($node as $action => $type) {
						isset($defaultList[$app][$action]) && $defaultList[$app][$action] = array_merge($defaultList[$app][$action], $type);
					}
				}
			}
		}

		return $defaultList;
	}

	/**
	 * 保存积分配置修改
	 * @param array $setData 积分修改的数据
	 * @return boolean 是否修改成功
	 */
	public function saveCreditSet($setData) {
		model('Xdata')->put('admin_Credit:set', $setData);
		model('Cache')->rm('credit_default');
		model('Cache')->rm('credit_set');
		return true;
	}

	/**
	 * 获取积分配置的Hash列表
	 * @param boolean $set 是否主动更新缓存，默认为false
	 * @return array 积分配置的Hash列表
	 */
	public function getDefaultList($set = false) {
		$data = $this->getNodeList($set);
		$hash = array();
		foreach($data as $v) {
			$hash[$v['appname']][$v['action']] = $v;
		}
		
		return $hash;
	}
	
	/**
	 * 获取数据节点列表
	 * @param boolean $set 是否主动更新缓存
	 * @return array 数据节点列表
	 */
	public function getNodeList($set) {
		$data = array();
		// 积分的配置信息修改的比较少，所以不采用mutex模式缓存
		if($set || ($data = model('Cache')->get('credit_default')) == false) {
			$data = D('credit_node')->findAll();
			model('Cache')->set('credit_default', $data);
		}

		return $data;
	}

	/**
	 * 通过积分节点，更新用户积分
	 * @param integer $uid 用户ID
	 * @param string $action 积分节点Key值
	 * @param integer $loop 积分操作执行次数，默认为1
	 * @param integer $nums 指定添加的积分数
	 * @param string $defaultType 操作积分类型，默认为gold
	 * @return boolean 是否更新成功
	 */
	public function updateUserCredit($uid, $action, $loop = 1, $nums = false, $defaultType = 'gold') {
		if(empty($uid)) {
			return false;
		}

		$loop = intval($loop);
		if($loop < 1) {
			return false;
		}

		$set = $this->getCreditSet();
		list($app, $act) = explode('_', $action);
		$actionInfo = $set[$app][$act];
		if(empty($actionInfo)) {
			$this->error = L('PUBLIC_POINTS_NOEXIST');			// 不存在的积分节点
			return false;
		}
		$creditType = $this->getTypeList('hash');						// 需要修改的积分类型
		// 修改用户积分表，TODO:需要优化
		$umap = array();
		$umap['uid'] = $uid;
		// 积分等字段类型
		$userinfo = D('')->table($this->tablePrefix.$this->keyInTable)->where($umap)->find();
		if(empty($userinfo)) {
			//如果不存在 则新加
			D('')->table($this->tablePrefix.$this->keyInTable)->add($umap);
		}
		// 需要插入到历史表的记录集
		$history = array();
		$_v['uid'] = $uid;
		$_v['info'] = $actionInfo['info'];
		$_v['action'] = $act;
		$_v['mtime'] = time();
		// 自定义积分数值时
		$nums !== false && $actionInfo[$defaultType] = $nums;

		foreach($creditType as $k => $v) {
			if(empty($actionInfo[$k])) {
				continue;
			}
			if(!isset($userinfo[$k]) || empty($userinfo[$k])) {
				$userinfo[$k] = 0;
			}
			
			$flag = $actionInfo[$k] > 0 ? "+" : "-";
			$_v['type'] = $k;
			$_v['credit'] = $flag.($actionInfo[$k] * $loop);
			$history[] = $_v;
			$save[$k] = ($flag == '+') ? ($userinfo[$k] + ($actionInfo[$k] * $loop)) : ($userinfo[$k] - abs($actionInfo[$k] * $loop));
			$save[$k] = $save[$k] < 0 ? 0 :$save[$k];
		}
		// 此项操作不需要修改任何一类积分
		if(empty($history)) {
			return false;	
		}
	
		if(D('')->table($this->tablePrefix.$this->keyInTable)->where($umap)->save($save)) {
			// 插入历史记录
			foreach($history as $v) {
				D('')->table($this->tablePrefix.'user_credit_history')->add($v);
			}
			$this->cleanCache($uid);
			$this->getUserCredit($uid, true);
			return true;	
		} else {			
			return false;
		}
	}	

	/**
	 * 后台直接修改用户积分
	 * @param array $data 更改用户积分相应数据
	 * @return boolean 是否修改成功
	 */
	public function updateByAdmin($data) {
		if(empty($data['uids']) && empty($data['userGroup'])) {
			return false;
		} else {
			if($data['uid_chose'] == 1) {
				unset($data['uids']);
			} else {
				unset($data['userGroup']);
			}
		}
		// 确保用户积分记录一直存在，如果这里执行慢，请确认积分字段表uid是否加了索引
		$sql1 = "SELECT a.uid as uid FROM ".$this->tablePrefix."user AS a LEFT JOIN ".$this->tablePrefix."credit_user AS b ON a.uid = b.uid WHERE b.uid is null";
		$notin = D('')->query($sql1);
		if(!empty($notin)) {
			$sql = "INSERT INTO ".$this->tablePrefix.$this->keyInTable." (`uid`) VALUES ";
			foreach($notin as $v) {
				$sql .= "(".$v['uid']."),";
			}
			$sql = trim($sql, ',');
			D('')->query($sql);
			D('')->query('REPAIR TABLE '.$this->tablePrefix.$this->keyInTable);
			D('')->query('OPTIMIZE TABLE '.$this->tablePrefix.$this->keyInTable);
		}
		
		$field  = t($data['creditType']);
		
		$update = intval($data['nums']) > 0 ? "+".intval($data['nums']) : intval($data['nums']);
		
		if($data['todo'] == 1) {
			// 加减
			$set = " SET {$field} = {$field}{$update}"; 
			$msg = L('PUBLIC_MODIFY_USER_POINT');			// 系统修改用户积分
		} else {
			// 设定
			$set = " SET {$field} = '".intval($data['nums'])."'";
			$msg = L('PUBLIC_SET_USER_POINT');				// 系统设定用户积分为
		}
		
		$table = $this->tablePrefix.$this->keyInTable;
		
		if($data['uids']) {
			// 只有uid
			$uids = implode("','", explode(",", $data['uids']));
			$sql = "UPDATE {$table} {$set} WHERE uid IN ('{$uids}')";
			$where = " AND uid IN ('{$uids}')";
			$this->cleanCache($uids);
		} else {
			// 用户组判断
			$where = " AND uid IN (SELECT uid FROM ".$this->tablePrefix."user_group_link WHERE user_group_id = ".intval($data['userGroup']).")";
			$sql = "UPDATE {$table} {$set} WHERE 1 {$where}";	
		}		
		
		if(D('')->query($sql) !== false) {
			// TODO:管理员操作的后台积分记录记为“系统修改”
			$sql = "INSERT INTO ".$this->tablePrefix."user_credit_history (`uid`,`info`,`action`,`type`,`credit`,`mtime`)  
				SELECT uid,'".$msg.( $data['nums']>0 ? '+':'-')."','adminDo','{$field}','{$update}','".time()."' FROM {$table} WHERE 1 {$where}";
			D('')->query($sql);
			// 把负数的更新回来
			if($data['todo'] < 0) {
				$sql = "UPDTAE {$table} SET {$field} = 0 WHERE {$field} < 0 ";
				D('')->query($sql);
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 获取积分设置信息
	 * @return array 积分设置信息
	 */
	public function getSetData() {
		if(($data = model('Cache')->get('credit_set')) == false) {
			$data = model('Xdata')->get('admin_Credit:set');
			model('Cache')->set('credit_set', $data);
		}

		return $data; 
	}

	/**
	 * 获取指定用户的积分
	 * @param integer $uid 用户ID
	 * @param boolean $set 是否主动更新缓存，默认为false
	 * @return array 指定用户的积分
	 */
	public function getUserCredit($uid, $set = false) {
		$map['uid'] = $uid;
		$table = $this->tablePrefix.$this->keyInTable;
		$data = model('Cache')->get('credit_user_'.$uid);
		if($set || !$data) {
			$data = D('')->table($table)->where($map)->find();
			model('Cache')->set('credit_user_'.$uid, $data);
		}
		
		if(empty($data)) {
			$data = array();
			$data[$this->type] = 0;
		} 
		// 获取积分等级规则
		$level = $this->getLevel();
	
		$creditType = $this->getTypeList('hash');
		$data['creditType'] = $creditType;
		
		foreach($level as $k => $v) {
			if($data[$this->type] >= $v['start'] && $data[$this->type] <= $v['end']) {
				$data['level'] = $v;
				$data['level']['level_type'] = $this->type;
				$data['level']['nextNeed'] = $v['end'] - $data[$this->type];
				$data['level']['nextName'] = $level[$k + 1]['name'];
				$data['level']['src'] = THEME_PUBLIC_URL.'/image/level/'.$data['level']['image'];
				break;
			}
			if($data[$this->type] > $v['end'] && !isset($level[$k + 1])) {
				$data['level'] = $v;
				$data['level']['nextNeed'] = '';
				$data['level']['nextName'] = '';
				$data['level']['src'] = THEME_PUBLIC_URL.'/image/level/'.$data['level']['image'];
				break;
			}
		}
		
		return $data;
	}
	
	/**
	 * 主动清空用户积分缓存
	 * @param array $uids 用户ID数组
	 * @return boolean 是否清除成功
	 */
	public function cleanCache($uids) {
		$uids = is_array($uids) ? $uids : explode(',', $uids);
		foreach($uids as $uid) {
			model('Cache')->rm('credit_user_'.$uid);
		}
		return true;
	}
	
	/**
	 * 获取积分类型列表，支持Hash和List两种返回格式
	 * @param string $return 返回类型，默认为List
	 * @return [type]         [description]
	 */
	public function getTypeList($return = 'list') {
		if(!$data = static_cache('credit_type')) {
			$data = model('Xdata')->lget('creditType');
			static_cache('credit_type',$data);
		}
		
		$arr = array();
		if($return =='hash') {
			foreach($data as $key => $value) {
				$arr[$key] = $value['CreditName'];
			}
		}
		if($return =='list') {
			foreach($data as $key => $value) {
				$arr[] = $value;
			}
		}
		empty($arr) && $arr = $data;

		return $arr;
	}
	
	/**
	 * 获取积分等级规则
	 * @return array 积分等级规则信息
	 */
	public function getLevel() {
		$data = model('Xdata')->get('admin_Credit:level');
		if(!$data) {
			$file = ADDON_PATH.'/lang/zh-cn/creditlevel.php';
			$data = include($file);
			model('Xdata')->put('admin_Credit:level', $data);
		}

		return $data;
	}
	
	/**
	 * 保存积分等级规则
	 * @param array $d 修改的积分等级规则
	 * @return void
	 */
	public function saveCreditLevel($d) {
		$data = $this->getLevel();
		$data[$d['level'] - 1]['name'] = $d['name'];
		$data[$d['level'] - 1]['image'] = $d['image'];
		$data[$d['level'] - 1]['start'] = $d['start'];
		$data[$d['level'] - 1]['end'] = $d['end'];
		model('Xdata')->put('admin_Credit:level', $data);
	}

	/**
	 * 保存积分类型
	 * @param array $data 积分类型数据
	 * @return mix 保存失败返回false，保存成功返回相应配置项的ID
	 */
	public function saveType($data) {
		if(empty($data['CreditType']) || empty($data['CreditName'])) {
			return false;
		} else {
			// 添加积分表内字段
			$sql = "SHOW COLUMNS FROM ".$this->tablePrefix.$this->keyInTable;
			$list = $this->query($sql);
			$data['CreditType'] = strtolower($data['CreditType']); 

			foreach($list as $f) {
				if(strtolower($f['Field']) == strtolower($data['CreditType'])) {
					$key = "creditType:".$data['CreditType'];
					return model('Xdata')->put($key, $data);
				}
			}

			$sql = "ALTER TABLE `".$this->tablePrefix.$this->keyInTable."` ADD `{$data['CreditType']}` INT( 10 ) NOT NULL COMMENT '{$data['CreditName']}'";

			if($this->query($sql) !== false) {
				$key = "creditType:".$data['CreditType'];
				return model('Xdata')->put($key, $data);
			}
			return false; 
		}
	}

	/**
	 * 获取指定积分类型的设置
	 * @param string $type 积分类型
	 * @return array 指定积分类型的设置
	 */
	public function getType($type) {
		return model('Xdata')->get('creditType:'.$type);
	}

	/**
	 * 删除指定的积分类型
	 * @param string $type 积分类型
	 * @return 删除失败返回false，删除成功返回相应配置项的ID
	 */
	public function delType($type) {
		// 删除积分表内字段
		$drop = is_array($type) ? implode("`,DROP `", $type) : $type;
		$sql = "ALTER TABLE `".$this->tablePrefix.$this->keyInTable."` DROP `{$drop}` ";
		if($this->query($sql) !== false) {
			$where = " `list` = 'creditType' ";
			$where .= is_array($type) ? " AND `key` IN ('".implode("','", $type). "')": " AND `key` ='{$type}'";
			model('Xdata')->lput('creditType', null);
			return model('Xdata')->where($where)->delete();
		}
		return false;
	}
	/**
	 * 添加任务积分
	 * @param int $exp
	 * @param int $score
	 * @param int $uid
	 */
	public function addTaskCredit( $exp , $score , $uid ){
		//加积分
		D('credit_user')->setInc('experience','uid='.$uid,$exp);
		D('credit_user')->setInc('gold','uid='.$uid,$score);
		$this->cleanCache( $uid );
		$this->getUserCredit($uid, true);
	}
}