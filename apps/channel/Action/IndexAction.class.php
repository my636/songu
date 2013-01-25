<?php
/**
 * 频道首页控制器
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class IndexAction extends Action
{
	/**
	 * 频道首页页面
	 * @return void
	 */
	public function index()
	{
		// 添加样式
		$this->appCssList[] = 'channel/channel.css';
		// 获取频道分类列表
		$channelCategory = D('Category', 'channel')->getCategoryList();
		$this->assign('channelCategory', $channelCategory);
		// 频道分类选中
		$cid = intval($_GET['cid']);
		if(empty($cid)) {
			$cid = $channelCategory[0]['channel_category_id'];
		}
		$this->assign('cid', $cid);
		// 获取指定频道的相关数据
		$data = D('Category', 'channel')->getDataWithCid($cid, 0, 10);
		
		$data['data'] = $this->_formatAttach($data['data']);
		//dump($data['data']);exit;
		$this->assign($data);
		// 载入ID
		if(isset($data['data'][(count($data['data'])-1)]['feed_channel_link_id'])){
			$loadId = $data['data'][(count($data['data'])-1)]['feed_channel_link_id'];
		}else{
			$loadId = 0;
		}
		$this->assign('loadId', $loadId);

		$this->display();
	}

	/**
	 * 获取指定分类下的微博信息 - 分页形式
	 * @return void
	 */
	public function loadMoreData()
	{
		// 频道ID
		$cid = intval($_REQUEST['cid']);
		// 查询是否有分页
		if(!empty($_REQUEST['p']) || intval($_REQUEST['loadCount']) == 3) {
			// unset($_REQUEST['loadId']);
			$limitNums = 40;
		} else {
			$return['status'] = -1;
			$return['msg'] = L('PUBLIC_LOADING_ID_ISNULL');
			$limitNums = 10;
		}
		$loadId = intval($_REQUEST['loadId']);
		// 获取HTML数据
		$content = $this->getData($cid, $limitNums, $loadId);
		// 查看是否有更多数据
		if(empty($content['pageHtml'])) {
			$return['status'] = 0;
			$return['msg'] = L('PUBLIC_WEIBOISNOTNEW');
		} else {
			$return['status'] = 1;
			$return['msg'] = L('PUBLIC_SUCCESS_LOAD');
    		$return['html'] = $content['html'];
    		$return['loadId'] = $content['lastId'];
            $return['firstId'] = (empty($_REQUEST['p']) && empty($_REQUEST['loadId']) ) ? $content['firstId'] : 0;
    		$return['pageHtml']	= $content['pageHtml'];
		}

		exit(json_encode($return));
	}

	/**
	 * 获取相关数据
	 * @param integer $cid 频道分类ID
	 * @param integer $limit 显示数据
	 * @param array 相关数据
	 */
	private function getData($cid, $limit, $loadId)
	{
		// 获取微博数据
		$list = D('Category', 'channel')->getDataWithCid($cid, $loadId, $limit);
    	// 分页的设置
        isset($list['html']) && $var['html'] = $list['html'];
    	if(!empty($list['data'])) {
    		$content['firstId'] = $var['firstId'] = $list['data'][0]['feed_channel_link_id'];
    		$content['lastId'] = $var['lastId'] = $list['data'][(count($list['data'])-1)]['feed_channel_link_id'];
            $var['data'] = $this->_formatAttach($list['data']);
    	}

    	$content['pageHtml'] = $list['html'];
	    // 渲染模版
	    $content['html'] = fetch('_loadMore', $var);

	    return $content;
	}

	/**
	 * 处理微博附件数据
	 * @param array $data 频道关联数组信息
	 * @return array 处理后的微博数据
	 */
	private function _formatAttach($data)
	{
		// 组装微博信息
		foreach($data as &$value) {
			// 获取微博信息
			$feedInfo = model('Feed')->get($value['feed_id']);
			$value = array_merge($value, $feedInfo);
			switch($value['type']) {
				case 'postimage':
					$feedData = unserialize($value['feed_data']);
					$imgAttachId = is_array($feedData['attach_id']) ? $feedData['attach_id'][0] : $feedData['attach_id'];
					$attach = model('Attach')->getAttachById($imgAttachId);
					$value['attachInfo'] = getImageUrl($attach['save_path'].$attach['save_name'],'200');
					$value['attach_id'] = $feedData['attach_id'];
					$value['body'] = parse_html($feedData['body']);
					break;
				case 'postfile':
					$feedData = unserialize($value['feed_data']);
					$attach = model('Attach')->getAttachByIds($feedData['attach_id']);
					foreach($attach as $key => $val) {
						$_attach = array(
								'attach_id' => $val['attach_id'],
								'name' => $val['name'],
								'attach_url' => getImageUrl($val['save_path'].$val['save_name'],'200'),
								'extension' => $val['extension'],
								'size' => $val['size']
							);
						$value['attachInfo'][] = $_attach;
					}
					$value['body'] = parse_html($feedData['body']);
					break;
				case 'repost':
					$feedData = unserialize($value['feed_data']);
					$value['body'] = parse_html($feedData['body']);
					break;
				case 'weiba_post':
					$feedData = unserialize($value['feed_data']);
					$post_url = '<a class="ico-details" target="_blank" href="'.U('weiba/Index/postDetail',array('post_id'=>$value['app_row_id'])).'"></a>';
					$value['body'] = preg_replace('/\<a href="javascript:void\(0\)" class="ico-details"(.*)\>(.*)\<\/a\>/',$post_url,$value['body']);
					break;
				case 'weiba_repost':
					$feedData = unserialize($value['feed_data']);
					$post_id = D('feed')->where('feed_id='.$value['app_row_id'])->getField('app_row_id');
					$post_url = '<a class="ico-details" target="_blank" href="'.U('weiba/Index/postDetail',array('post_id'=>$post_id)).'"></a>';
					$value['body'] = preg_replace('/\<a href="javascript:void\(0\)" class="ico-details"(.*)\>(.*)\<\/a\>/',$post_url,$value['body']);
			}
		}
		
		return $data;
	}

	/**
	 * 获取分类数据列表
	 */
	public function getCategoryData()
	{
		$data = D('Category', 'channel')->getCategoryList();
		$result = array();
		if(empty($data)) {
			$result['status'] = 0;
			$result['data'] = '获取数据失败';
		} else {
			$result['status'] = 1;
			$result['data'] = $data;
		}
		
		exit(json_encode($result));
	}
}