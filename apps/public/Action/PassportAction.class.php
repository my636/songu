<?php
/**
 * PassportAction 通行证模块
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
class PassportAction extends Action 
{
	var $passport;

	/**
	 * 模块初始化
	 * @return void
	 */
	protected function _initialize() {
		$this->passport = D('Passport');
	}

	/**
	 * 通行证首页
	 * @return void
	 */
	public function index() {
		$this->display();
	}

	/**
	 * 默认登录页
	 * @return void
	 */
	public function login()
	{
		// 添加样式
		$this->appCssList[] = 'public/login.css';
		if($GLOBALS['ts']['mid'] > 0){
			U('public/Index/index','',true);
		}
		// 获取邮箱后缀
		$registerConf = model('Xdata')->get('admin_Config:register');
		$this->assign('emailSuffix', explode(',', $registerConf['email_suffix']));
		$this->assign( 'register_type' , $registerConf['register_type']);
		$this->display();
	}

	/**
	 * 用户登录
	 * @return void
	 */
	public function doLogin() {
		$login 		= t($_POST['login_email']);
		$password 	= trim($_POST['login_password']);
		$remember	= intval($_POST['login_remember']);
		
		$result 	= $this->passport->loginLocal($login,$password,$remember);
		
		if(!$result){
			$status = 0; 
			$info	= $this->passport->getError();
			$data 	= '';
		}else{
			$status = 1; 
			$info 	= L('PUBLIC_LOGIN_SUCCESS');
			$data 	= $result;
		}
		$this->ajaxReturn($data,$info,$status);
	}	
	
	/**
	 * 注销登录
	 * @return void
	 */
	public function logout() {
		$this->passport->logoutLocal();
		redirect(U('public/Passport/login'));
	}

	/**
	 * 找回密码页面
	 * @return void
	 */
	public function findPassword() {
		$this->error('抱歉，暂时不能找回密码');
		// 添加样式
		$this->appCssList[] = 'public/login.css';
		$this->display();
	}

	/**
	 * 通过安全问题找回密码
	 * @return void
	 */
	public function doFindPasswordByQuestions() {
		$this->display();
	}

	/**
	 * 通过Email找回密码
	 */
	public function doFindPasswordByEmail() {
		$_POST["email"]	= t($_POST["email"]);
		if(!$this->_isEmailString($_POST['email'])) {
			$this->error(L('PUBLIC_EMAIL_TYPE_WRONG'));
		}

		$user =	model("User")->where('`email`="'.$_POST["email"].'"')->find();
		
        if(!$user) {
        	$this->error('找不到该邮箱注册信息');
        } else {
        	$msg['status'] = 1;
        	$msg['info'] = '发送成功';
        	$msg['data'] = $user['uid'];
        	exit(json_encode($msg));
		}
	}

	/**
	 * 找回密码页面
	 */
	public function sendPasswordEmail() {
		$uid = intval($_REQUEST['uid']);
		$user =	model("User")->where('`uid`="'.$uid.'"')->find();
		if($user) {
	    	$this->appCssList[] = 'public/login.css';		// 添加样式
	        $code = base64_encode($user["uid"].".".md5($user["uid"].'+'.$user["password"]));
	        $config['reseturl'] = U('public/Passport/resetPassword', array('code'=>$code));
	        model('Notify')->sendNotify($user['uid'], 'password_reset', $config);
	        $message = L('PUBLIC_EMAIL_SENDTO_SUCCESS')."<strong>{$user['email']}</strong>";
	       	$this->assign('email', $_POST["email"]);
	        $this->assign('message', $message);
	        $this->display();
		}
	}

	/**
	 * 通过手机短信找回密码
	 * @return void
	 */
	public function doFindPasswordBySMS() {
		$this->display();
	}

	/**
	 * 重置密码页面
	 * @return void
	 */
	public function resetPassword() {
		$code = t($_GET['code']);
		$this->_checkResetPasswordCode($code);
		$this->assign('code', $code);
		$this->display();
	}

	/**
	 * 执行重置密码操作
	 * @return void
	 */
	public function doResetPassword() {
		$code = t($_GET['code']);
		$user_info = $this->_checkResetPasswordCode($_POST['code']);

		$password = trim($_POST['password']);
		$repassword = trim($_POST['repassword']);
		if(!model('Register')->isValidPassword($password, $repassword)){
			$this->error(model('Register')->getLastError());
		}

		$map['uid'] = $user_info['uid'];
		$data['login_salt'] = rand(10000,99999);
		$data['password']   = md5(md5($password) . $data['login_salt']);
		$res = model('User')->where($map)->save($data);
		if ($res) {
			model('User')->cleanCache($user_info['uid']);
			$this->assign('jumpUrl', U('public/Passport/login'));
			$config['newpass'] = $password;
			model('Notify')->sendNotify($user_info['uid'],'password_setok',$config);
			//$mail = model('Mail')->sendSysEmail($user_info['email'],'resetPassOk',array(),array('newpass'=>$password));
			$this->success(L('PUBLIC_PASSWORD_RESET_SUCCESS'));
		} else {
			$this->error(L('PUBLIC_PASSWORD_RESET_FAIL'));
		}
	}

	/**
	 * 检查重置密码的验证码操作
	 * @return void
	 */
	public function _checkResetPasswordCode($code) {
		$parse_code = base64_decode($code);
		$parse_code = explode('.', $parse_code);
		$uid = $parse_code[0];
		$user_info = model('User')->where("`uid`={$uid}")->find();

		if (!$user_info || base64_encode( $user_info["uid"] . "." . md5($user_info["uid"] . '+' . $user_info["password"]) != $code )) {
			$this->redirect = U('public/Passport/login');
		}

		return $user_info;
	}

	/*
	 * 验证安全邮箱
	 * @return void
	 */
	public function doCheckEmail() {
		$email = t($_POST['email']);
		if($this->_isEmailString($email)){
			die(1);			
		}else{
			die(0);
		}
	}

	/*
	 * 正则匹配，验证邮箱格式
	 * @return integer 1=成功 ""=失败
	 */
	private function _isEmailString($email) {
		return preg_match("/[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+$/i", $email) !== 0;
	}
}
?>