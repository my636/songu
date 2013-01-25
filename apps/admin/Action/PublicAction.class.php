<?php

class PublicAction extends Action {
	
	public function _initialize(){
		$this->assign('isAdmin',1);	//是否后台
	}
	/**
	 * 登录
	 * Enter description here ...
	 */
	public function login(){
		
		if ($_SESSION['adminLogin']) {
			redirect(U('admin/Index/index'));exit();
		}
		$this->setTitle( L('ADMIN_PUBLIC_LOGIN') );
		$this->display();
	}

	/**
	 * 验证码
	 * Enter description here ...
	 */
	public function verify(){
		tsload(ADDON_PATH.'/liberary/Image.class.php');
		tsload(ADDON_PATH.'/liberary/String.class.php');
		Image::buildImageVerify();
	}
	
	public function doLogin(){
		$login =model('Passport')->adminLogin();
		if($login){
			if(CheckPermission('core_admin','admin_login')){
				$this->success(L('PUBLIC_LOGIN_SUCCESS'));	
			}else{
				$this->assign('jumpUrl',SITE_URL);
				$this->error(L('PUBLIC_NO_FRONTPLATFORM_PERMISSION_ADMIN'));
			}
			
		}else{
			$this->error(model('Passport')->getError());
		}
	}
	
	/**
	 * 退出登录
	 * Enter description here ...
	 */
	public function logout(){
		model('Passport')->adminLogout();
		U('admin/Public/login','',true);
	}
	
	
	/**
	 * 通用部门选择数据接口
	 */
	public function selectDepartment(){
		$return = array('status'=>1,'data'=>'');
		
		if(empty($_POST['pid'])){
			$return['status'] = 0;
			$return['data']   = L('PUBLIC_SYSTEM_CATEGORY_ISNOT');
			echo json_encode( $return );exit();
		}

		$ctree = model('Department')->getDepartment($_POST['pid']);
        if(empty($ctree['_child'])){
        	$return['status'] = 0;
			$return['data']   = L('PUBLIC_SYSTEM_SONCATEGORY_ISNOT');	
        }else{
        	$return['data'] = "<select name='_parent_dept_id[]' onchange='admin.selectDepart(this.value,$(this))' id='_parent_dept_{$_POST['pid']}'>";
        	$return['data'] .= "<option value='-1'>".L('PUBLIC_SYSTEM_SELECT')."</option>";
        	$sid = !empty($_POST['sid']) ? $_POST['sid'] : '';
        	foreach ($ctree['_child'] as $key => $value) {
        		$return['data'] .="<option value='{$value['department_id']}' ".($value['department_id'] == $sid ? " selected='selected'":'').">{$value['title']}</option>";	
        	}	
			$return['data'] .="</select>";	
        }
        echo json_encode( $return );exit();
	}

    /*** 分类模板接口 ***/
    /**
     * 移动分类顺序API
     * @return json 返回相关的JSON信息
     */
    public function moveTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $type = t($_POST['type']);
        $stable = t($_POST['stable']);
        $result = model('CategoryTree')->setTable($stable)->moveTreeCategory($cid, $type);
        // 处理返回结果
        if($result) {
            $res['status'] = 1;
            $res['data'] = $info;
        } else {
            $res['status'] = 0;
            $res['data'] = $info;
        }

        exit(json_encode($res));
    }

 	/**
 	 * 添加分类窗口API
 	 * @return void
 	 */
    public function addTreeCategory()
    {
    	$cid = intval($_GET['cid']);
    	$this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);

    	$this->display('categoryBox');
    }

    /**
     * 添加分类操作API
     * @return json 返回相关的JSON信息
     */
    public function doAddTreeCategory()
    {
    	$pid = intval($_POST['pid']);
    	$title = t($_POST['title']);
    	$stable = t($_POST['stable']);
        $data['attach_id'] = intval($_POST['attach_id']);
    	$result = model('CategoryTree')->setTable($stable)->addTreeCategory($pid, $title, $data);
    	$res = array();
    	if($result) {
    		$res['status'] = 1;
    		$res['data'] = '添加分类成功';
    	} else {
    		$res['status'] = 0;
    		$res['data'] = '添加分类失败';
    	}

    	exit(json_encode($res));
    }

    /**
     * 编辑分类窗口API
     * @return void
     */
    public function upTreeCategory()
    {
        $cid = intval($_GET['cid']);
        $this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
        // 获取该分类的信息
        $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
        if(isset($category['attach_id']) && !empty($category['attach_id'])) {
            $attach = model('Attach')->getAttachById($category['attach_id']);
            $this->assign('attach', $attach);
        }
        $this->assign('category', $category);

    	$this->display('categoryBox');
    }

    /**
     * 编辑分类操作API
     * @return json 返回相关的JSON信息
     */
    public function doUpTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $title = t($_POST['title']);
        $stable = t($_POST['stable']);
        $data['attach_id'] = intval($_POST['attach_id']);
        $result = model('CategoryTree')->setTable($stable)->upTreeCategory($cid, $title, $data);
        $res = array();
        if($result) {
            $res['status'] = 1;
            $res['data'] = '编辑分类成功';
        } else {
            $res['status'] = 1;
            $res['data'] = '编辑分类失败';
        }

        exit(json_encode($res));
    }

    /**
     * 删除分类API
     * @return json 返回相关的JSON信息
     */
    public function rmTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $stable = t($_POST['stable']);
        $result = model('CategoryTree')->setTable($stable)->rmTreeCategory($cid);
        $res = array();
        if($result) {
            if($stable=='user_verified_category'){
                $user_verified = D('user_verified')->where('verified_category_id='.$cid)->findAll();
                foreach($user_verified as $v){
                    D('user_group_link')->where('uid='.$v['uid'].' and user_group_id='.$v['usergroup_id'])->delete();
                }
                D('user_verified')->where('verified_category_id='.$cid)->delete();
            }
            if($stable=='user_official_category'){
                D('user_official')->where('official_category_id='.$cid)->delete();
            }
            $res['status'] = 1;
            $res['data'] = '删除分类成功';
        } else {
            $res['status'] = 0;
            $res['data'] = '删除分类失败';
        }

        exit(json_encode($res));
    }
}	