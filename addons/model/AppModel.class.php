<?php
/**
 * 应用模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
class AppModel extends Model {

	protected $tableName = 'app';
	protected $fields =	array(0=>'app_id',1=>'app_name',2=>'app_alias',3=>'description',
								4=>'status',5=>'host_type',6=>'app_entry',7=>'icon_url',8=>'large_icon_url',
								9=>'admin_entry',10=>'statistics_entry',11=>'display_order',
								12=>'ctime',13=>'version',14=>'api_key',15=>'secure_key',16=>'company_name',
								17=>'has_mobile',18=>'child_menu','_pk'=>'app_id'
								);

	public static $defaultApp = array();		// 默认应用字段
	public $_host_type = array();				// 应用类型字段

	/**
	 * 初始化 - 用于双语处理
	 * @return void
	 */
	public function _initialize(){
		$this->_host_type = array(0=>L('PUBLIC_LOCAL_APP'), 1=>L('PUBLIC_REMOTE_APP'));		// 本地应用，远程应用
	}	
	
	/**
	 * 获取应用列表 - 分页型
	 * @param array $map 查询条件
	 * @param integer $limit 每页显示的结果数
	 * @param string $order 排序条件
	 * @return array 应用列表分页数据
	 */
	public function getAppByPage($map, $limit=10, $order = 'app_id DESC') {
		$list = $this->where($map)->field('app_id')->order($order)->findPage($limit);
		$list['data'] = $this->getInfoByList($list['data'], true); 

		return $list;
	}
	
	/**
	 * 获取指定用户的应用列表 - 不分页型
	 * @param integer $uid 用户UID
	 * @param integer $inweb 是否为网页端，默认为1
	 * @return array 指定用户的应用列表
	 */
	public function getUserApp($uid, $inweb = 1) {
		$uid = empty($uid) ? $_SESSION['mid'] : $uid;
		$table = $this->tablePrefix.'user_app AS a LEFT JOIN '.$this->tablePrefix.'app AS b ON a.app_id = b.app_id';
		$map['a.uid'] = $uid;
		$map['b.status'] = 1;
		$map['a.inweb'] = intval($inweb);
		$list = $this->table($table)->where($map)->findAll();

		return $list;
	}

	/**
	 * 获取指定用户的应用列表 - 分页型
	 * @param integer $uid 用户UID
	 * @param integer $limit 分页的结果集数目，默认为10
	 * @param integer $inweb 是否为网页端，默认为1
	 * @return array 指定用户的应用列表
	 */
	public function getUserAppByPage($uid, $limit = 10, $inweb = 1) {
		$uid = empty($uid) ? $_SESSION['mid'] : $uid;
		$map['a.uid'] = $uid;
		$map['a.inweb'] = intval($inweb);
		$map['b.status'] = 1;
		$table = $this->tablePrefix.'user_app AS a LEFT JOIN '.$this->tablePrefix.'app AS b ON a.app_id = b.app_id';
		$list = $this->table($table)->where($map)->findPage($limit);
		$list['data'] = $this->getInfoByList($list['data'], true);

		return $list;
	}

	/**
	 * 获取指定用户在前台可管理的应用列表
	 * @param integer $uid 用户UID
	 * @return array 获取指定用户有管理权限的应用列表
	 */
	public function getManageApp($uid) {
		// 静态缓存
		if($list = static_cache('manage_app_'.$uid)) {
			return $list;
		}			
		// 指定用户的权限
		$rules = model('Permission')->loadRule($uid);
		// 管理权限节点
		$manageApp = D('permission_node')->where("rule='manage'")->field('appname')->getAsFieldArray('appname');
		// 获取相应的应用列表
		if(!empty($manageApp)) {
			$apps = array();
			foreach($manageApp as $v){
				if($rules[$v]['admin']['manage']){
					$apps[] = $v;
				}
			}

			if(empty($apps)){
				$list = array();
			}else{
				$map['_string'] = " app_name IN ('".implode("','",$apps)."')";
				$list = $this->getAppList($map);	
			}
		}

		empty($list) && $list = array();

		static_cache('manage_app_'.$uid, $list);

		return $list;
	}

	/**
	 * 获取所有应用列表 - 不分页型
	 * @param array $map 查询条件
	 * @param string $limit 显示结果集数目，默认不设置
	 * @return array 应用列表数据
	 */
	public function getAppList($map = array(), $limit = '') {
		$list = $this->where($map)->field('app_id')->order('app_id DESC');
		// 根据条件获取相应结果集
		if(!empty($limit)) {
			$list = $list->limit($limit)->findAll();
		} else {
			$list = $list->findAll();
		}
		// 组装数据
		if(!empty($list)) {
			foreach($list as $k => $v) {
				$list[$k] = $this->getAppById($v['app_id']);
			}
		}

		return $list;
	}	
	
	/**
	 * 批量获取应用信息
	 * @param array $list 应用列表数组，其中必须包含app_id字段值
	 * @param boolean $used 是否获取应用的使用情况，默认false
	 * @return array 应用信息数组
	 */
	public function getInfoByList($list, $used = false) {
		$r = array();
		if(empty($list)) return $r;
		foreach($list as $v) {
			$r[] = $this->getAppById($v['app_id'], $used);
		}

		return $r;
	}

	/**
	 * 根据APP名称获取相应的应用ID
	 * @param string $appName 应用的名称
	 * @return integer 相应的应用ID
	 */
	public function getAppIdByName($appName) {
		// 获取静态缓存
		$cache = static_cache('app_appNameIdHash');
		if(!empty($cache)) {
			$appId = array_search($appName, $cache);
			return !empty($appId) ? $appId : false;
		}
		// 获取缓存
		$cache = model('Cache')->get('AppHash_NameID');
		// 设置缓存操作
		if(empty($cache)) {
			 $cache = $this->getAppHash('app_id', 'app_name');
			 model('Cache')->set('AppHash_NameID', $cache);
		}

		static_cache('app_appNameIdHash', $cache);

		$appId = array_search($appName, $cache);
		return !empty($appId) ? $appId : false;
	}

	/**
	 * 获取已经安装应用的Hash数组
	 * @param string $hashKey Hash中的Key值，默认为app_id
	 * @param string $hashValue Hash中的Value值，默认为app_alias
	 * @param array $map 查询条件
	 * @return array 安装应用的信息
	 */
	public function getAppHash($hashKey = 'app_id', $hashValue = 'app_alias', $map = array()) {
		$list = $this->getAppList($map);
		$r = array();
		foreach($list as $v){
			$r[$v[$hashKey]] = $v[$hashValue];
		}

		return $r;
	}	

	/**
	 * 通过应用名称，获取应用的信息
	 * @param string $appname 应用名称
	 * @return array 应用的相应信息
	 */
	public function getAppByName($appname) {
		$id = $this->getAppIdByName($appname);	
		return $this->getAppById($id);
	}

	/**
	 * 根据应用ID为应用做缓存，缓存KEY为app_Appinfo_[应用ID]，Appinfo_[应用ID]
	 * @param integer $app_id 应用ID
	 * @param boolean $used 是否获取应用的使用情况，默认false
	 * @return array 返回指定应用的相关信息
	 */
	public function getAppById($app_id, $used = false) {
		// 验证数据的正确性
		if(empty($app_id)) return array();
		// 判断静态缓存是否存在
		$info = static_cache('app_Appinfo_'.$app_id);
		if(empty($info)) {
			// 判断缓存是否存在
			$info = model('Cache')->get('Appinfo_'.$app_id);
			if(empty($info)) {
				$map['app_id'] = $app_id;
				$info = $this->where($map)->find();	
				// 数据格式化
				if($info['host_type'] == "0") {
					// 本地应用
					$info['app_entry'] = U($info['app_name'].'/'.$info['app_entry']);
					empty($info['icon_url']) && $info['icon_url'] = SITE_URL.'/apps/'.$info['app_name'].'/Appinfo/icon_app.png'; 
					empty($info['large_icon_url']) && $info['large_icon_url'] = SITE_URL.'/apps/'.$info['app_name'].'/Appinfo/icon_app_large.png'; 
					$info['iphone_icon'] = SITE_URL.'/apps/'.$info['app_name'].'/Appinfo/icon_iphone.png';
					$info['android_icon'] = SITE_URL.'/apps/'.$info['app_name'].'/Appinfo/icon_android.png';
				}
				// 设置静态缓存
				static_cache('app_Appinfo_'.$app_id, $info);
				// 设置缓存
				model('Cache')->set('Appinfo_'.$app_id, $info);
				// 删除应用Hask表缓存 - APP名称与ID的缓存
				model('Cache')->rm('AppHash_NameID');
			}
		}

		// 是否获取应用的使用情况
		$used && $info['used'] = model('UserApp')->getUsed($app_id);

		return $info;
	}
	
	/**
	 * 获取系统默认配置应用列表
	 * @return array 系统默认应用列表
	 */
	public function getDefaultApp() {
		// 获取静态缓存
		$list = static_cache('app_defaultapp');
		if(!empty($list)) {
			return $list;
		}
		// 获取缓存
		$list = model('Cache')->get('defaultApp');
		if(empty($list)) {
			$map['status'] = 1;
			$list = $this->where($map)->field('app_id')->findAll();
			if(empty($list)){
				$list = array();
			}else{
				$list = $this->getInfoByList($list);	
			}
			model('Cache')->set('defaultApp', $list);
		}

		static_cache('app_defaultapp', $list);

		return $list; 	
	}
	
	/**
	 * 清除缓存
	 * @param array $ids 应用ID数组
	 * @return boolean 是否清除缓存
	 */
	public function cleanCache($ids) {
		// 清空所有缓存
		if(empty($ids)) {	
			$list = $this->field('app_id')->findAll();
			foreach($list as $l) {
				model('Cache')->rm('Appinfo_'.$l['app_id']);
			}
		}
		if(!is_array($ids)) {
			model('Cache')->rm('Appinfo_'.$ids);
		}
		foreach($ids as $v) {
			model('UserApp')->cleanUsed($v);
			model('Cache')->rm('Appinfo_'.$v);
		}
		model('Cache')->rm('AppHash_NameID');
		model('Cache')->rm('defaultApp');
		return true;
	}

	/**
	 * 获取应用的配置列表
	 * @return array 应用的配置列表信息
	 */
	public function getConfigList() {
		$map['admin_entry'] = array('NEQ', '');
		$r = array();
		$list = $this->getAppList($map);
		if(!empty($list)) {
			foreach($list as $v){
				$r[$v['app_alias']] = U($v['admin_entry']);
			}
		}

		return $r;
	}

	/**
	 * 获取未安装应用列表
	 * @return array 未安装应用列表
	 */
	public function getUninstallList() {
		$uninstalled = array();
		$installed = $this->getAppList();
		$installed = getSubByKey($installed, 'app_name');
		// 默认应用，不能安装卸载
		$installed = empty($installed) ? C('DEFAULT_APPS') : array_merge($installed, C('DEFAULT_APPS'));
		
		require_once ADDON_PATH . '/liberary/io/Dir.class.php';
		$dirs = new Dir(APPS_PATH);
		$dirs = $dirs->toArray();
		foreach($dirs as $v) {
			if($v['isDir'] && !in_array($v['filename'], $installed)) {
				if($info = $this->__getAppInfo($v['filename'])) {
					$uninstalled[] = $info;
				}
			}
		}

		return $uninstalled;
	}
	
	/**
	 * 获取应用信息
	 * @param string $path_name 应用路径名称
	 * @param boolean $using_lowercase 返回键值为大写还是小写，默认为小写
	 * @return array 指定应用的相关信息
	 */
	public function __getAppInfo($path_name, $using_lowercase = true) {
		$filename = APPS_PATH.'/'.$path_name.'/Appinfo/info.php';
		
		if(is_file($filename)) {
			$info = include_once $filename;
			
			$info['HOST_TYPE_ALIAS'] = $this->_host_type[$info['HOST_TYPE']];
			$info['APP_ALIAS'] = $info['NAME'];
			$info['PATH_NAME'] = $path_name;
			$info['APP_NAME'] = $path_name;
			return $using_lowercase ? array_change_key_case($info) : array_change_key_case($info, CASE_UPPER);
		} else {
			return false;
		}
	}
	
	/**
	 * 保存应用信息数据
	 * @param array $data 应用相关数据
	 * @return boolean 是否保存成功
	 */
	public function saveApp($data) {
		foreach($data as $k=> &$v) {
			$v = ($k == 'description') ? htmlspecialchars($v) : t($v);
		}

		if($data['host_type'] == 0 && !is_dir(APPS_PATH.'/'.$data['app_name'])) {
			return L('PUBLIC_DIRECTORY_NOEXIST', array('dir'=>$data['app_name']));		// {dir}目录不存在
		}

		if(!empty($data['app_id'])) {
			// 更新应用数据操作
			$map = array();
			$map['app_id'] = $data['app_id'];
			unset($data['app_id']);
			if($this->where($map)->save($data)) {
				$this->cleanCache($map['app_id']);
				return true;
			} else {
				return L('PUBLIC_DATA_UPGRADE_FAIL');		// 数据更新失败，可能未做任何修改
			}
		} else {
			// 清除缓存
			F('_xdata_lget_pageKey', null);
			F('_xdata_lget_searchPageKey', null);
			// 新增加应用操作
			if($this->isAppNameExist($data['app_name'])) {
				return L('PUBLIC_APP_EXIST');		// 应用已经存在
			}

			$oldInfo = $this->__getAppInfo($data['app_name']);
			// 固定数据内容处理
			empty($oldInfo['child_menu']) && $oldInfo['child_menu'] = array();
			$data['child_menu'] = serialize($oldInfo['child_menu']);
			$data['has_mobile'] = intval($oldInfo['has_mobile']);

			$install_script = APPS_PATH.'/'.$data['app_name'].'/Appinfo/install.php';
			if(file_exists($install_script)) {
				include_once $install_script;
			}
			$data['ctime'] = time();
			// 为便于排序，将order设置为ID
			unset($data['app_id']);

			if($res = $this->add($data)) {
				// 成功入库之后执行的操作
				$GLOBALS['appid'] =  $res;
				$install_script = APPS_PATH.'/'.$data['app_name'].'/Appinfo/afterInstall.php';
				if(file_exists($install_script)) {
					include_once $install_script;	
				}

				$this->where('`app_id`='.$res)->setField('display_order', $res);
				return true;
			} else {
				return L('PUBLIC_DATA_INSERT_FAIL');			// 数据插入失败
			}
		}
	}
	
	/**
	 * 判断指定应用是否已经安装
	 * @param string $app_name 应用名称
	 * @param integer $app_id 应用ID
	 * @return boolean 指定应用是否安装
	 */
	public function isAppNameExist($app_name='', $app_id='') {
		// 参数判断
		if(empty($app_name) && empty($app_id)) {
			$this->error = L('PUBLIC_WRONG_DATA');		// 错误的参数
			return false;
		}
		// 默认应用
		if(in_array($app_name, C('DEFAULT_APPS'))) {
			return true;
		}
		// 用户自定义安装应用
		$list = $this->getAppList();
		foreach($list as $v) {
			if(!empty($app_name) && ($v['app_name'] == $app_name)) {
				return true;
			}
			if(!empty($app_id) && ($v['app_id'] == $app_id)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 判断指定应用是否已经开启
	 * @param string $app_name 应用名称
	 * @param string $app_id 应用ID
	 * @return boolean 指定应用是否可用
	 */
	public function isAppNameOpen($app_name = '', $app_id = '')
	{
		// 参数判断
		if(empty($app_name) && empty($app_id)) {
			$this->error = L('PUBLIC_WRONG_DATA');		// 错误的参数
			return false;
		}
		// 默认应用
		if(in_array($app_name, C('DEFAULT_APPS'))) {
			return true;
		}
		// 用户自定义安装应用
		$list = $this->getAppList();
		foreach($list as $v) {
			if($v['status'] == 0) {
				continue;
			}
			if(!empty($app_name) && ($v['app_name'] == $app_name)) {
				return true;
			}
			if(!empty($app_id) && ($v['app_id'] == $app_id)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 后台卸载指定应用
	 * @param integer $app_id 应用ID
	 * @return boolean 是否卸载成功
	 */
	public function uninstall($app_id) {
		$map = array();
		$map['app_id'] = $app_id;
		$appinfo = $this->where($map)->find();
		if(empty($appinfo)) {
			return L('PUBLIC_APP_NOEXIST');		// 应用不存在或未安装
		}
		if($this->where($map)->delete()) {
			$uninstall_script = APPS_PATH.'/'.$appinfo['app_name'].'/Appinfo/uninstall.php';
			if(is_file($uninstall_script)) {
				include_once $uninstall_script;
			}
			// 删除用户应用表中的数据
			$umap['app_id'] = $app_id;
			model('UserApp')->where($umap)->delete();
			// 删除历史搜索数据
			$sm['int01'] = $app_id;
			D('')->table($this->tablePrefix.'search')->where($sm)->delete();
			
			$this->cleanCache($app_id);
			return true;
		} else {
			return L('PUBLIC_ADMIN_OPRETING_ERROR');		// 操作失败
		}
	}
}