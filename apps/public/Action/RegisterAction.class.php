<?php
/**
 * RegisterAction 注册模块
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
class RegisterAction extends Action
{
	private $_config;					// 注册配置信息字段
	private $_register_model;			// 注册模型字段
	private $_user_model;				// 用户模型字段
	private $_invite;					// 是否是邀请注册
	private $_invite_code;				// 邀请码

	/**
	 * 模块初始化，获取注册配置信息、用户模型对象、注册模型对象、邀请注册与站点头部信息设置
	 * @return void
	 */
	protected function _initialize()
	{
		$this->_invite = false;
		// 未激活与未审核用户
		if($this->mid > 0 && !in_array(ACTION_NAME, array('changeActivationEmail', 'activate', 'isEmailAvailable'))) {
			$GLOBALS['ts']['user']['is_audit'] == 0 && ACTION_NAME != 'waitForAudit' && U('public/Register/waitForAudit', array('uid'=>$this->mid), true);
			$GLOBALS['ts']['user']['is_audit'] == 1 && $GLOBALS['ts']['user']['is_active'] == 0 && ACTION_NAME != 'waitForActivation' && U('public/Register/waitForActivation', array('uid'=>$this->mid), true);
		}
		// 登录后，将不显示注册页面
		$this->mid > 0 && $GLOBALS['ts']['user']['is_init'] == 1 && redirect(U('public/Index/index'));

		$this->_config = model('Xdata')->get('admin_Config:register');
		$this->_user_model = model('User');
		$this->_register_model = model('Register');
		$this->setTitle(L('PUBLIC_REGISTER'));
	}

	/**
	 * 默认注册页面 - 注册表单页面
	 * @return void
	 */
	public function index()
	{
		$this->appCssList[] = 'public/login.css';
		// 验证是否有钥匙 - 邀请注册问题
		if(empty($this->mid)) {
			if((isset($_GET['invite']) || $this->_config['register_type'] != 'open') && !in_array(ACTION_NAME, array('isEmailAvailable', 'isUnameAvailable', 'doStep1'))) {
				if(!isset($_GET['invite'])) {
					$this->error('抱歉，本站目前仅支持邀请注册。');
				}
				$inviteCode = t($_GET['invite']);
				$status = model('Invite')->checkInviteCode($inviteCode, $this->_config['register_type']);
				if($status == 1) {
					$this->_invite = true;
					$this->_invite_code = $inviteCode;
				} else if($status == 2) {
					$this->error('抱歉，该邀请码已使用。');
				} else {
					$this->error('抱歉，本站目前仅支持邀请注册。');
				}
			}
		}
		// 若是邀请注册，获取邀请人相关信息
		if($this->_invite) {
			$inviteInfo = model('Invite')->getInviterInfoByCode($this->_invite_code);
			$this->assign('inviteInfo', $inviteInfo);
		}
		$this->assign('is_invite', $this->_invite);
		$this->assign('invite_code', $this->_invite_code);
		$this->assign('config', $this->_config);		
		$this->assign('invate_key', t($_GET['key']));
		$this->assign('invate_uid', t($_GET['uid']));	
		$this->display();			
	}

	/**
	 * 注册流程 - 执行第一步骤
	 * @return void
	 */
	public function doStep1(){	
		$invite = t($_POST['invate']);
		$inviteCode = t($_POST['invate_key']);
		$email = t($_POST['email']);
		$uname = t($_POST['uname']);
		$sex = 1 == $_POST['sex'] ? 1 : 2;
		$password = trim($_POST['password']);
		$repassword = trim($_POST['repassword']);

		if(!$this->_register_model->isValidEmail($email)) {
			$this->error($this->_register_model->getLastError());
		}

		if(!$this->_register_model->isValidPassword($password, $repassword)){
			$this->error($this->_register_model->getLastError());
		}
/*		if (!$_POST['accept_service']) {
			$this->error(L('PUBLIC_ACCEPT_SERVICE_TERMS'));
		}*/
		$login_salt = rand(11111, 99999);
		$map['uname'] = $uname;
		$map['sex'] = $sex;
		$map['login_salt'] = $login_salt;
		$map['password'] = md5(md5($password).$login_salt);
		$map['login'] = $map['email'] = $email;
		$map['regip'] = get_client_ip();
		$map['ctime'] = time();
		// 添加地区信息
		$map['location'] = t($_POST['city_names']);
		$cityIds = t($_POST['city_ids']);
		$cityIds = explode(',', $cityIds);
		isset($cityIds[0]) && $map['province'] = intval($cityIds[0]);
		isset($cityIds[1]) && $map['city'] = intval($cityIds[1]);
		isset($cityIds[2]) && $map['area'] = intval($cityIds[2]);
		// 审核状态： 0-需要审核；1-通过审核
		$map['is_audit'] = $this->_config['register_audit'] ? 0 : 1;
		$map['is_active'] = $this->_config['need_active'] ? 0 : 1;
		$map['first_letter'] = getFirstLetter($uname);
		//如果包含中文将中文翻译成拼音
		if ( preg_match('/[\x7f-\xff]+/', $map['uname'] ) ){
			//昵称和呢称拼音保存到搜索字段
			$map['search_key'] = $map['uname'].' '.model('PinYin')->Pinyin( $map['uname'] );
		} else {
			$map['search_key'] = $map['uname'];
		}
		$uid = $this->_user_model->add($map);
		if($uid) {
			// 如果是邀请注册，则邀请码失效
			if($invite) {
				$receiverInfo = model('User')->getUserInfo($uid);
				// 验证码使用
				model('Invite')->setInviteCodeUsed($inviteCode, $receiverInfo);
				// 添加用户邀请码字段
				model('User')->where('uid='.$uid)->setField('invite_code', $inviteCode);
			}

			// 添加至默认的用户组
			$userGroup = model('Xdata')->get('admin_Config:register');
			$userGroup = empty($userGroup['default_user_group']) ? C('DEFAULT_GROUP_ID') : $userGroup['default_user_group'];
			model('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));

			//注册来源-第三方帐号绑定
			if(isset($_POST['other_type'])){
				$other['type'] = t($_POST['other_type']);
				$other['type_uid'] = intval($_POST['other_uid']);	
				$other['oauth_token'] = t($_POST['oauth_token']);
				$other['oauth_token_secret'] = t($_POST['oauth_token_secret']);
				$other['uid'] = $uid;
				D('login')->add($other);
			}
			
			//判断是否需要审核
			if($this->_config['register_audit']) {
				$this->redirect('public/Register/waitForAudit', array('uid' => $uid));
			} else {
				if($this->_config['need_active']){
					$this->_register_model->sendActivationEmail($uid);
					$this->redirect('public/Register/waitForActivation', array('uid' => $uid));
				}else{
					$this->assign('jumpUrl', U('public/Passport/login'));
					$this->success('恭喜您，注册成功，请登录');
				}
			}
		} else {
			$this->error(L('PUBLIC_REGISTER_FAIL'));			// 注册失败
		}
	}

	/**
	 * 等待审核页面
	 * @return void
	 */
	public function waitForAudit() {
		$user_info = $this->_user_model->where("uid={$this->uid}")->find();
		$email	=	model('Xdata')->getConfig('sys_email','site');
		if (!$user_info || $user_info['is_audit']) {
			$this->redirect('public/Passport/login');
		}
		$touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
		foreach($touid as $k=>$v){
			model('Notify')->sendNotify($v['uid'], 'register_audit');
		}
		$this->assign('email',$email);
		$this->display();
	}

	/**
	 * 等待激活页面
	 */
	public function waitForActivation() {
		$this->appCssList[] = 'public/login.css';
		$user_info = $this->_user_model->where("uid={$this->uid}")->find();
		// 判断用户信息是否存在
		if($user_info) {
			if($user_info['is_audit'] == '0') {
				// 审核
				exit(U('public/Register/waitForAudit', array('uid'=>$this->uid), true));
			} else if($user_info['is_active'] == '1') {
				// 激活
				exit(U('public/Register/step2',array(),true));				
			}
		} else {
			// 注册
			$this->redirect('public/Passport/login');
		}

		$email_site = 'http://mail.'.preg_replace('/[^@]+@/', '', $user_info['email']);

		$this->assign('email_site', $email_site);
		$this->assign('email', $user_info['email']);
		$this->assign('config', $this->_config);
		$this->display();
	}

	/**
	 * 发送激活邮件
	 * @return void
	 */
	public function resendActivationEmail() {
		$res = $this->_register_model->sendActivationEmail($this->uid);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $res);
	}

	/**
	 * 修改激活邮箱
	 */
	public function changeActivationEmail() {
		$email = t($_POST['email']);
		$res = $this->_register_model->changeRegisterEmail($this->uid, $email);
		$res && $this->_register_model->sendActivationEmail($this->uid);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $res);
	}

	/**
	 * 通过链接激活帐号
	 * @return void
	 */
	public function activate() {
		$user_info = $this->_user_model->getUserInfo($this->uid);

		$this->assign('user',$user_info);
		
		if (!$user_info || $user_info['is_active']) {
			$this->redirect('public/Passport/login');
		}

		$active = $this->_register_model->activate($this->uid, t($_GET['code']));

		if ($active) {
			// 登陆
			model('Passport')->loginLocalWhitoutPassword($user_info['email']);
			// 跳转下一步
			$this->assign('jumpUrl', U('public/Register/step2'));
			$this->success($this->_register_model->getLastError());
		} else {
			$this->redirect('public/Passport/login');
			$this->error($this->_register_model->getLastError());
		}
	}

	/**
	 * 第二步注册
	 * @return void
	 */
	public function step2() {
		// 未登录
		empty($_SESSION['mid']) && $this->redirect('public/Passport/login');
		$user = $this->_user_model->getUserInfo($this->mid);
		$this->assign('user_info', $user);
		$this->display();
	}

	/**
	 * 注册流程 - 第三步骤
	 * 选择职业信息
	 */
	public function step3() {
		// 未登录
		empty($_SESSION['mid']) && $this->redirect('public/Passport/login');
		$this->appCssList[] = 'public/login.css';
		$this->display();
	}

	/**
	 * 注册流程 - 执行第三步骤
	 * 添加职业信息
	 */
	public function doStep3() {
		$userCategoryIds = t($_POST['user_category_ids']);
		empty($userCategoryIds) && exit($this->error('请至少选择一个职业信息'));
		$userCategoryIds = explode(',', $userCategoryIds);
		$userCategoryIds = array_filter($userCategoryIds);
		$userCategoryIds = array_unique($userCategoryIds);
		$result = model('UserCategory')->updateRelateUser($this->mid, $userCategoryIds);
		if($result) {
			$this->ajaxReturn(null, L('PUBLIC_SAVE_SUCCESS'), $result);
		} else {
			$this->ajaxReturn(null, '职业信息保存失败', $result);
		}
	}

	/**
	 * 注册流程 - 第四步骤
	 */
	public function step4() {
		// 未登录
		empty($_SESSION['mid']) && $this->redirect('public/Passport/login');
		$this->appCssList[] = 'public/login.css';
		$this->display();
	}

	/**
	 * 获取推荐用户
	 * @return void
	 */
	public function getRelatedUser() {
		$related_user = $this->_user_model->relatedUser($this->mid, 15 * 3);
		// $relatedUids = model('RelatedUser')->getRelatedUserWithLogin(100);
		$relatedUids = getSubByKey($related_user, 'uid');
		$related_user = $this->_user_model->getUserInfoByUids($relatedUids);
		$this->ajaxReturn(array_values($related_user), null, 1);
	}

	/**
	 * 注册流程 - 执行第四步骤
	 */
	public function doStep4() {
		// 初始化完成
		$this->_register_model->overUserInit($this->mid);
		// 添加默认关注用户
		$defaultFollow = $this->_config['default_follow'];
		if(!empty($defaultFollow)) {
			model('Follow')->bulkDoFollow($this->mid, $defaultFollow);
		}
		// 添加双向关注用户
		$eachFollow = $this->_config['each_follow'];
		if(!empty($eachFollow)) {
			model('Follow')->eachDoFollow($this->mid, $eachFollow);
		}
		$this->redirect('public/Index/index');
	}

	/**
	 * 验证邮箱是否已被使用
	 */
	public function isEmailAvailable() {
		$email = t($_POST['email']);
		$result = $this->_register_model->isValidEmail($email);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
	}

	/**
	 * 验证邀请邮件
	 */
    public function isEmailAvailable_invite() {
		$email = t($_POST['email']);
		if(empty($email)) {
			exit($this->ajaxReturn(null, '', 1));
		}
		$result = $this->_register_model->isValidEmail_invite($email);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
	}

	/**
	 * 验证昵称是否已被使用
	 */
	public function isUnameAvailable() {
		$uname = t($_POST['uname']);
		$oldName = t($_POST['old_name']);
		$result = $this->_register_model->isValidName($uname, $oldName);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
	}

	/**
	 * 添加用户关注信息
	 */
	public function bulkDoFollow() {
		$res = model('Follow')->bulkDoFollow($this->mid, t($_POST['fids']));
    	$this->ajaxReturn($res, model('Follow')->getError(), false !== $res);
	}
}