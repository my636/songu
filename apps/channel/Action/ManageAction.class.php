<?php
/**
 * 频道前台管理控制器
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class ManageAction extends Action
{
	private $_model_category;		// 频道分类模型

	/**
	 * 初始化操作
	 */
	public function _initialize()
	{
		$this->_model_category = D('Category', 'channel');
	}

	/**
	 * 频道管理弹窗
	 * @return void
	 */
    public function getAdminBox()
    {
    	// 获取微博ID
    	$data['feedId'] = intval($_REQUEST['feed_id']);
        // 频道分类ID
        $data['channelId'] = empty($_REQUEST['channel_id']) ? 0 : intval($_REQUEST['channel_id']);
    	// 获取全部频道列表
        $data['categoryList'] = $this->_model_category->getCategoryList();
        // 获取该微博已经选中的频道
        $data['selectedChannels'] = $this->_model_category->getSelectedChannels($data['feedId']);

        $this->assign($data);
        $this->display('manageBox');
    }

    /**
     * 添加微博进入频道
     * @return json 操作后的相关信息数据
     */
    public function doAddChannel()
    {
    	// 微博ID
    	$feedId = intval($_POST['feedId']);
        // 判断资源是否删除
        $fmap['feed_id'] = $feedId;
        $fmap['is_del'] = 0;
        $isExist = model('Feed')->where($fmap)->count();
        if($isExist == 0) {
            $return['status'] = 0;
            $return['info'] = '内容已被删除，推荐失败';
            exit(json_encode($return));
        }
    	// 频道ID数组
    	$channelIds = t($_POST['data']);
    	$channelIds = explode(',', $channelIds);
    	$channelIds = array_filter($channelIds);
    	$channelIds = array_unique($channelIds);
    	if(empty($feedId)) {
    		$res['status'] = 0;
    		$res['info'] = '推荐失败';
    		exit(json_encode($res));
    	}
    	// 添加微博进入频道
    	$result = $this->_model_category->setChannel($feedId, $channelIds);
    	if($result) {
            $config['feed_content'] = getShort(D('feed_data')->where('feed_id='.$feedId)->getField('feed_content'),10);
            $map['channel_category_id'] = array('in',$channelIds);
            $config['channel_name'] = implode(',',getSubByKey(D('channel_category')->where($map)->field('title')->findAll(),'title'));
            $uid = D('feed')->where('feed_id='.$feedId)->getField('uid');
            $config['feed_url'] = '<a target="_blank" href="'.U('public/Profile/feed',array('feed_id'=>$feedId,'uid'=>$uid)).'">'.$config['feed_content'].'</a>';
    		model('Notify')->sendNotify($uid, 'channel_add_feed', $config); 
            $res['status'] = 1;
    		$res['info'] = '推荐成功';
    	} else {
    		$res['status'] = 0;
    		$res['info'] = '推荐失败';
    	}
    	exit(json_encode($res));
    }

    /**
     * 频道管理弹窗
     * @return void
     */
    public function submission()
    {
        // 获取微博ID
        $data['feedId'] = intval($_REQUEST['feed_id']);
        // 频道分类ID
        $data['channelId'] = empty($_REQUEST['channel_id']) ? 0 : intval($_REQUEST['channel_id']);
        // 获取全部频道列表
        $data['categoryList'] = $this->_model_category->getCategoryList();
        // 获取该微博已经选中的频道
        $selectedChannels = D('channel')->where('feed_id =-1')->field('channel_category_id')->findAll();
        foreach ($selectedChannels as $key => $value) {
           $data['selectedChannels'][$key] = $value['channel_category_id'];
        }
        $this->assign($data);
        $this->display('submission');
    }

    //首页投稿功能
    public function indexAddChannel(){
        // 微博ID
        $feedId = intval($_POST['feedId']);
        // 频道ID数组
        $channelIds = t($_POST['data']);
        $channelIds = explode(',', $channelIds);
        $channelIds = array_filter($channelIds);
        $channelIds = array_unique($channelIds);
        
        // 添加微博进入频道
        $this->_model_category->deleteChannelLink($feedId);
        $this->_model_category->setChannel($feedId, $channelIds);

    }
    //频道页投稿功能
    public function addSubmission(){
        // 获取微博ID
        $data['feedId'] = intval($_REQUEST['feed_id']);
        // 频道分类ID
        $data['channelId'] = empty($_REQUEST['channel_id']) ? 0 : intval($_REQUEST['channel_id']);
        // 获取全部频道列表
        $data['categoryList'] = $this->_model_category->getCategoryList();
        // 获取该微博已经选中的频道
        $data['selectedChannels'] = $this->_model_category->getSelectedChannels($data['feedId']);

        $this->assign($data);
        $this->display();
    }
}