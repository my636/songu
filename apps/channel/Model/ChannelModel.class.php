<?php
/**
 * 频道分类模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class ChannelModel extends Model
{
	protected $tableName = 'channel';
	// protected $fields =	array('channel_category_id', 'title', 'pid');

	/**
	 * 当指定pid时，查询该父分类的所有子分类；否则查询所有分类
	 * @param integer $pid 父分类ID
	 * @return array 相应的分类列表
	 */
	public function getBlogList($status)
	{	
		$map['status'] = intval($status);
		$data = $this->where($map)->order('`feed_channel_link_id` DESC')->findAll();
		foreach ($data['data'] as $key => $value) {
			$content = D('feed_data')->where('feed_id ='.$value['feed_id'])->find();
			$content['feed_data'] = unserialize($content['feed_data']);
			$data['data'][$key]['data'] = $content;
		}
		$data = getSubByKey($data,'feed_id');
		return $data;
	}

	public function review($feed_id,$status = null){
		$map['feed_id'] = array('IN',$feed_id);
		if($status == 1){
			$data['status'] = 0;
		}
		if($status == 0 || empty($status)){
			$data['status'] = 1;
		}
		$this->where($map)->save($data);
		return true;
	}

	public function del($feed_id){
		$map['feed_id'] = array('IN',$feed_id);
		$res = $this->where($map)->delete();
		//删除相关数据
		D('feed')->where($map)->delete();
		D('feed_data')->where($map)->delete();
		if($res){
			$return['status'] = 1;
		}else{
			$return['status'] = 0;
		}
		return $return;
	}

	/**
	 * 获取资源列表
	 * @param array $map 查询条件
	 * @return array 获取资源列表
	 */
	public function getChannelList($map)
	{
		// 获取资源分页结构
		$data = $this->field('DISTINCT `feed_id`, `status`')->where($map)->order('`feed_channel_link_id` DESC')->findPage();
		// 获取微博ID
		$feedIds = getSubByKey($data['data'], 'feed_id');
		// 获取微博分类频道信息
		$cmap['c.feed_id'] = array('IN', $feedIds);
		$categoryInfo = D()->table('`'.$this->tablePrefix.'channel` AS c LEFT JOIN `'.$this->tablePrefix.'channel_category` AS cc ON cc.channel_category_id = c.channel_category_id')
						   ->field('c.`feed_id`, c.`status`, cc.channel_category_id, cc.`title`')
						   ->where($cmap)
						   ->findAll();
		$categoryInfos = array();
		foreach($categoryInfo as $val) {
			$categoryInfos[$val['feed_id']][] = $val;
		}
		// 获取微博信息
		$feedInfo = model('Feed')->getFeeds($feedIds);
		$feedInfos = array();
		foreach($feedInfo as $val) {
			$feedInfos[$val['feed_id']] = $val;
		}
		// 组装信息
		foreach($data['data'] as &$value) {
			$value['uid'] = $feedInfos[$value['feed_id']]['user_info']['uid'];
			$value['uname'] = $feedInfos[$value['feed_id']]['user_info']['uname'];
			$value['content'] = $feedInfos[$value['feed_id']]['body'];
			$value['categoryInfo'] = $categoryInfos[$value['feed_id']];
		}

		return $data;
	}

	/**
	 * 删除指定资源信息
	 * @param array $rowId 资源ID数组
	 * @return boolean 是否删除成功
	 */
	public function cancelRecommended($rowId)
	{
		$map['feed_id'] = array('IN', $rowId);
		$result = $this->where($map)->delete();
		return (boolean)$result;
	}

	/**
	 * 审核资源操作
	 * @return array $rowId 资源ID数组
	 * @return boolean 是否审核成功
	 */
	public function auditChannelList($rowId)
	{
		$map['feed_id'] = array('IN', $rowId);
		$save['status'] = 1;
		$result = $this->where($map)->save($save);
		return (boolean)$result;
	}
}