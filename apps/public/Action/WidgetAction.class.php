<?php
/**
 * 插件请求控制器
 * @author zivss guolee226@gmail.com
 * @version TS3.0
 */
class WidgetAction extends Action
{
	public function renderWidget()
	{
		//非登录下widget调用过滤
		if(!$this->mid){
			$access_widget = array();
			if(!in_array($_REQUEST['name'],$access_widget))exit;
		}
		$_REQUEST['name']  = t($_REQUEST['name']);
		$_REQUEST['param'] = unserialize(urldecode($_REQUEST['param']));
		send_http_header('utf8');
		echo empty($_REQUEST['name']) ? 'Invalid Param.' : W(t($_REQUEST['name']), t($_REQUEST['param']));
	}

	// 插件的请求转发
	public function addonsRequest()
	{
		Addons::addonsHook(t($_REQUEST['addon']),t($_REQUEST['hook']));
	}

	// 插件的渲染
	public function displayAddons(){
        $result = array();
        $param['res'] = &$result;
        $param['type'] = $_REQUEST['type'];
        Addons::addonsHook($_GET['addon'],$_GET['hook'],$param);
        isset($result['url']) && $this->assign("jumpUrl",$result['url']);
        isset($result['title']) && $this->setTitle($result['title']);
        isset($result['jumpUrl']) && $this->assign('jumpUrl',$result['jumpUrl']);
        if(isset($result['status']) && !$result['status']){
            $this->error($result['info']);
        }
        if(isset($result['status']) && $result['status']){
            $this->success($result['info']);
        }
	}
}
