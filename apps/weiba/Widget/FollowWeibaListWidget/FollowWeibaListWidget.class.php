<?php
/**
 * 某人关注的微吧Widget
 * @example W('FollowWeibaList', array('follower_uid'=>10000))
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class FollowWeibaListWidget extends Widget {

	/**
     * 渲染关注按钮模板
     * @example
     * $data['follower_uid'] integer 用户ID
     * @param array $data 渲染的相关配置参数
     * @return string 渲染后的模板数据
	 */
	public function render($data) {
		$var = array();
		$var['type'] = 'FollowWeibaList';

		$follow = D('weiba_follow')->where('follower_uid='.$data['follower_uid'])->findAll();

		$map['weiba_id'] = array('in', getSubByKey($follow, 'weiba_id'));
		$var['weibaList'] = D('weiba')->where($map)->findAll();

	    $var['weibaListCount'] = D('weiba')->where($map)->count();
		foreach($var['weibaList'] as $k=>$v){
			$logo = D('attach')->where('attach_id='.$v['logo'])->find();
			$var['weibaList'][$k]['logo'] = UPLOAD_URL.'/'.$logo['save_path'].$logo['save_name'];
		}
		is_array($data) && $var = array_merge($var, $data);
		// 渲染模版
		$content = $this->renderFile(dirname(__FILE__) . "/followWeibaList.html", $var);
		unset($var,$data);
		// 输出数据
		return $content;
    }
}