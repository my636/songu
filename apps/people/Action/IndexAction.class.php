<?php
/**
 * 找人首页控制器
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class IndexAction extends Action
{
	public function _initialize()
	{
		$this->appCssList[] = 'people/people.css';
	}

	public function index()
	{
		// 获取相关数据
		$cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
		$sex = isset($_GET['sex']) ? intval($_GET['sex']) : 0;
		$area = isset($_GET['area']) ? intval($_GET['area']) : 0;
		$verify = isset($_GET['verify']) ? intval($_GET['verify']) : 0;
		// 加载数据
		$this->assign('cid', $cid);
		$this->assign('sex', $sex);
		$this->assign('area', $area);
		$this->assign('verify', $verify);
		// 页面类型
		$type = isset($_GET['type']) ? t($_GET['type']) : 'verify';
		$this->assign('type', $type);
		// 获取后台配置用户
		if(in_array($type, array('verify', 'official'))) {
			switch($type) {
				case 'verify':
					$conf = model('Xdata')->get('admin_User:verifyConfig');
					break;
				case 'official':
					$conf = model('Xdata')->get('admin_User:official');
					break;
			}
			$this->assign('topUser', $conf['top_user']);
		}

		$this->display();
	}
}