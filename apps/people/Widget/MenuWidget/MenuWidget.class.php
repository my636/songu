<?php
/**
 * 菜单选择Widget
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class MenuWidget extends Widget
{
	/**
	 * 模板渲染
	 * @param array $data 相关数据
	 * @return string 用户身份选择模板
	 */
	public function render($data) {
        // 设置模板
        $template = empty($data['type']) ? 'industry' : strtolower(t($data['type']));
        // 获取相关数据
        $var['cid'] = intval($data['cid']);
        $var['area'] = intval($data['area']);
        $var['sex'] = intval($data['sex']);
        $var['verify'] = intval($data['verify']);
        $var['type'] = t($data['type']);
        // 获取一级分类
        switch($var['type']) {
        	case 'industry':
                // 父级选中ID
                $pid = model('UserCategory')->where('user_category_id='.$var['cid'])->getField('pid');
                $var['pid'] = ($pid == 0) ? $var['cid'] : $pid; 
		        $var['menu'] = model('UserCategory')->getNetworkList();
        		break;
        	case 'area':
    			$var['menu'] = model('CategoryTree')->setTable('area')->getNetworkList();
        		break;
            case 'verify':
                $var['menu'] = model('CategoryTree')->setTable('user_verified_category')->getNetworkList();
                break;
            case 'official':
                $var['menu'] = model('CategoryTree')->setTable('user_official_category')->getNetworkList();
                break;
        }
        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__)."/".$template.".html", $var);
        // 输出数据
        return $content;
    }
}