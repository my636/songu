<?php
/**
 * 应用管理控制器
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
class AppAction extends Action {

	/**
	 * 初始化控制器，加载相关样式表
	 */
	protected function _initialize() {
		$this->appCssList[] = APP_NAME.'/app.css';
	}

	/**
	 * 应用列表页面，默认为所有应用
	 */
	public function index() {
		$map['status'] = 1;
		$list = model('App')->getAppByPage($map, 10);
		$installIds = model('UserApp')->getUserAppIds($this->uid);
		$this->assign('installIds', $installIds);
		$this->assign('list', $list);
		$this->setTitle(L('PUBLIC_APP_INEX'));				// 添加应用
		$this->display();
	}

	/**
	 * 我的应用列表页面，登录用户已经安装的应用
	 */
	public function myApp() {
		$list = model('App')->getUserAppByPage($this->uid, 10);
		$this->assign('list', $list);
		$this->display();
	}
	
	/**
	 * 登录用户卸载应用操作
	 * @return json 返回操作后的JSON信息数据
	 */
	public function uninstall() {
		$return = array('status'=>1,'data'=>L('PUBLIC_SYSTEM_MOVE_SUCCESS'));			// 移除成功
		$appId = intval($_POST['app_id']);
		if(empty($appId)) {
			$return = array('status'=>1,'data'=>L('PUBLIC_SYSTEM_MOVE_FAIL'));			// 移除失败
			exit(json_encode($return));
		}
		if(!model('UserApp')->uninstall($this->uid, $appId)) {
			$return['status'] = 0;
			$return['data'] = model('UserApp')->getError(); 
		}
		exit(json_encode($return));
	}

	/**
	 * 登录用户安装应用操作
	 * @return json 返回操作后的JSON信息数据
	 */
	public function install() {
		$return = array('status'=>1,'data'=>L('PUBLIC_ADD_SUCCESS'));					// 添加成功
		$appId = intval($_POST['app_id']);
		if(empty($appId)) {
			$return = array('status'=>1,'data'=>L('PUBLIC_ADD_FAIL'));					// 添加失败
			exit(json_encode($return));
		}
		if(!model('UserApp')->install($this->uid, $appId)) {
			$return['status'] = 0;
			$return['data'] = model('UserApp')->getError(); 
		}
		exit(json_encode($return));
	}
}