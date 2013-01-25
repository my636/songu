<?php
/**
 * 频道分类模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class BlogModel extends Model
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




}