<?php
/**
 * 频道分类模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class CategoryModel extends Model
{
	protected $tableName = 'channel_category';
	protected $fields =	array('channel_category_id', 'title', 'pid');

	/**
	 * 当指定pid时，查询该父分类的所有子分类；否则查询所有分类
	 * @param integer $pid 父分类ID
	 * @return array 相应的分类列表
	 */
	public function getCategoryList($pid = -1)
	{
		$map = array();
		$pid != -1 && $map['pid'] = $pid;
		$data = $this->where($map)->order('`channel_category_id` ASC')->findAll();
		return $data;
	}

	/**
	 * 判断名称是否重复
	 * @param string $title 名称
	 * @return boolean 是否重复
	 */
	public function isTitleExist($title)
	{
		$map['title'] = t($title);
		$count = $this->where($map)->count();
		$result = ($count == 0) ? false : true;
		return $result;
	}

	/**
	 * 添加频道与微博的关联信息
	 * @param integer $sourceId 微博ID
	 * @param array $channelIds 频道分类ID数组
	 * @param boolean $isAdmin 是否需要审核，默认为true
	 * @return boolean 是否添加成功
	 */
	public function setChannel($feedId, $channelIds, $isAdmin = true)
	{
		// 格式化数据
		!is_array($channelIds) && $channelIds = explode(',', $channelIds);
		// 检验数据
		if(empty($feedId)) {
			return false;
		}
		// 删除微博的全部关联
		$res = D('channel')->where('feed_id='.$feedId)->delete();
		// 删除成功
		if($res !== false) {
			$data['feed_id'] = $feedId;
			// 获取图片的高度与宽度
			$feedInfo = model('Feed')->get($feedId);
			if($feedInfo['type'] == 'postimage') {
				$data['width'] = 195;
				$feedData = unserialize($feedInfo['feed_data']);
				$imageAttachId = is_array($feedData['attach_id']) ? $feedData['attach_id'][0] : $feedData['attach_id'];
				$attach = model('Attach')->getAttachById($imageAttachId);
				$imageInfo = getImageInfo($attach['save_path'].$attach['save_name']);
				$imageInfo !== false && $data['height'] = ceil($data['width'] * $imageInfo[1] / $imageInfo[0]);
			}
			// 用户UID
			$data['uid'] = $feedInfo['uid'];
			// 获取后台配置数据
			$channelConf = model('Xdata')->get('channel_Admin:index');
			$isAudit = ($channelConf['is_audit'] == 1) ? false : true;
			foreach($channelIds as $channelId) {
				$data['channel_category_id'] = $channelId;
				if($isAdmin) {
					$data['status'] = 1;
				} else {
					if($isAudit) {
						$data['status'] = 0;
					} else {
						$data['status'] = 1;
					}
				}
				D('channel')->add($data);
			}
			return true;
		}

		return false;
	}

	/**
	 * 获取指定微博已经加入的频道分类
	 * @param integer $feedId 微博ID
	 * @return array 已加入频道的分类数组
	 */
	public function getSelectedChannels($feedId)
	{
		$map['feed_id'] = $feedId;
		$data = D('channel')->where($map)->getAsFieldArray('channel_category_id');
		return $data;
	}

	/**
	 * 获取指定频道分类下的相关数据 - 分页数据
	 * @param integer $cid 频道分类ID
	 * @return array 指定频道分类下的相关数据
	 */
	public function getDataWithCid($cid, $loadId, $limit = 20)
	{
		if(empty($cid)) {
			return array();
		}
		$map['channel_category_id'] = $cid;

		$map['status'] = 1;

		!empty($loadId) && $map['feed_channel_link_id'] = array('LT', $loadId);

		$data = D('channel')->where($map)->order('feed_channel_link_id DESC')->findPage($limit);
		return $data;
	}

	/**
	 * 删除微博与频道的关联
	 * @param integer $feedId 微博ID
	 * @return boolean 是否删除成功
	 */
	public function deleteChannelLink($feedId)
	{
		// 判断参数
		if(empty($feedId)) {
			return false;
		}
		// 删除数据
		$map['feed_id'] = intval($feedId);
		$result = D('channel')->where($map)->delete();
		return (boolean)$result;
	}

	/**
	 * 获取频道分类的Hash数组
	 * @param integer $pid 父分类ID
	 * @return array 相应的分类列表
	 */
	public function getCategoryHash($pid = -1)
	{
		$map = array();
		$pid != -1 && $map['pid'] = $pid;
		$data = $this->where($map)->getHashList('channel_category_id', 'title');
		return $data;
	}
}