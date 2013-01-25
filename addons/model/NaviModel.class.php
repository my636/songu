<?php
/**
 * 导航模型 - 数据对象模型
 * @author jason <renjianchao@zhishisoft.com> 
 * @version TS3.0
 */
class NaviModel extends Model {

	protected $tableName = 'navi';
	protected $fields = array(0=>'navi_id',1=>'navi_name',2=>'app_name',3=>'url',4=>'target',5=>'status',6=>'position',7=>'guest',8=>'is_app_navi',9=>'parent_id',10=>'order_sort');
	
	/**
	 * 获取头部导航
	 * @return array 头部导航 
	 */
	public function getTopNav() {
		if(($topNav = model('Cache')->get('topNav')) === false) {
			$map['status'] = 1;
			$map['position'] = 0;
			$list = $this->where($map)->order('order_sort ASC')->findAll();
			
			foreach($list as &$v) {
				$v['url'] = empty($v['url']) ? 'javascript:;' : str_replace('{website}', SITE_URL, $v['url']);
			}
			$topNav = $list;

			empty($list) && $list = array();
			model('Cache')->set('topNav', $list, 3600);
		}

		return $topNav;
	}	

	/**
	 * 获取底部导航
	 * @return array 底部导航
	 */
	public function getBottomNav() {
		if(($bottomNav = model('Cache')->get('bottomNav')) === false) {
			$map['status'] = 1;
			$map['position'] = 1;
			$list = $this->where($map)->order('order_sort ASC')->findAll();

			foreach($list as &$v){
				$v['url'] = empty($v['url']) ? 'javascript:;' : str_replace('{website}', SITE_URL, $v['url']);
			}
			$bottomNav = $list;

			empty($list) && $list = array();
			model('Cache')->set('bottomNav',$list,3600);
		}

		return $bottomNav;
	}

	/**
	 * 清除导航缓存
	 * @return void
	 */
	public function cleanCache() {
		model('Cache')->rm('topNav');
		model('Cache')->rm('bottomNav');
	}	
}