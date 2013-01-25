<?php 
class TestAction extends Action{

	public function _initialize(){
		header("Content-Type:text/html; charset=UTF8");
	}

	public function index(){
		$de = desencrypt('liguo@zhishisoft.com','SociaxV1');
		echo desdecrypt($de,'SociaxV1');
	}

	public function avatar(){
		$info = model('Avatar')->init($this->mid)->getUserAvatar();
		dump($info);
	}

	public function refreshWeibaFeed(){
		set_time_limit(0);
		$sql = "update ts_feed set app_row_table='weiba_post' where app='weiba' AND type='weiba_post' AND app_row_table='feed'";
		$result = D()->execute($sql);
		if(!$result){
			dump("update weiba_post : false");
			echo '<hr />';
		}
		

		$sql = "select * from ts_feed where app='weiba' AND type='repost' AND app_row_table='feed' LIMIT 100";
		$result = D()->query($sql);
		if($result){
			dump("update weiba_repost : ");
			dump($result);
			echo '<hr />';
		}else{
			dump("update weiba_repost : OK");
		}
		// $sql = "update ts_feed as a set a.app_row_table='weiba_post',a.app='weiba',a.type='weiba_repost',a.app_row_id=(select app_row_id from ts_feed as b where b.feed_id=a.app_row_id) where app='weiba' AND type='repost';";
		// $result = D()->execute($sql);
		// dump($result);
		// dump(D()->getLastSql());
	}

	public function feed(){
		// dump(model('Feed')->getNodeList());
		// echo '<hr />';
		// $feed_template = simplexml_load_file(SITE_PATH.'/apps/public/Conf/post.feed.php');
		// dump($feed_template);
		$actor = '刘晓庆';
		$feedIds = array(37);
		$res = model('Feed')->getFeeds($feedIds);
		dump($res);
	}

	public function cloudimage(){
		$this->display();
	}

	//下面是一些测试函数
	public function credit(){
		model('Credit')->updateUserCredit(14983,'weibo_add');
		model('Credit')->updateUserCredit(14983,'weibo_add');
		model('Credit')->updateUserCredit(14983,'weibo_add');
		dump(model('Credit')->getUserCredit(14983));
	}

	public function hello(){
		echo '服务器当前时间:',date('Y-m-d H:i:s');
		dump('hello world');
		echo 11111111;
	}

	public function t(){
		dump(model('UserPrivacy')->getPrivacy(10000,14983));
	}

	public function at($data){
		$html = "@{uid=14983|yangjiasheng}";
		echo parse_html($html);
	}

	public function mail(){
		dump(model('Mail')->send_email('yangjs17@yeah.net','ttt','xxxxxxxx'));
	}

	public function cut(){
		getThumbImage("./data/upload/2012/0604/19/4fcc9b2f67d34.jpg",'300','auto',false);
		echo '<img src="./data/upload/2012/0604/19/4fcc9b2f67d34_300_auto.jpg">';
		echo 11;
	}

	public function data(){
		$nums = 1000;//测试数
		$add = array();
		$add['version'] 		= 1;
		$add['language']		= 'all';
		$add['source_faq_id'] 	= 0;
		$add['creator_uid'] 	= $this->mid;
		$add['create_time'] 	= time();
		$add['comment_count']   = 0;
		for($i=0;$i<$nums;$i++){
			$add['status'] = rand(0,2);
			$add['active'] = rand(0,1);
			$add['category_id'] = rand(1,4);
			$add['tags'] 		='测试tag'.$i;
			$add['question_cn'] = '这里是测试的问题中文'.$i;
			$add['question_en'] = 'here is  test question english'.$i;
			$add['answer_cn'] 	= '这里是测试的答案'.$i;
			$add['answer_en'] 	= 'here is test answer english'.$i;
			D('')->table('sociax_support')->add($add);
			
		}
	}

	public function initdata(){
		D('PublicSearch','public')->initData();
	}

	public function tsearch(){

		D('PublicSearch','public')->search();
	}

	public function findLang(){

		//  - model-
		// $filePath[] = ADDON_PATH.'/model';
		// 
		// - view -
//		$filePath[] = ADDON_PATH.'/theme/stv1/public';
//		$filePath[] = ADDON_PATH.'/theme/stv1/task';
//		$filePath[] = ADDON_PATH.'/theme/stv1/support';
//		$filePath[] = ADDON_PATH.'/theme/stv1/contact';
//		$filePath[] = ADDON_PATH.'/theme/stv1/admin';
		// 
		// - app -
 $filePath[] = SITE_PATH."/apps/public";
 $filePath[] = SITE_PATH."/apps/support";
 $filePath[] = SITE_PATH."/apps/contact";
 $filePath[] = SITE_PATH."/apps/admin";
 $filePath[] = SITE_PATH."/apps/task";


		
		$filelist 	= array();
		require_once ADDON_PATH . '/liberary/io/Dir.class.php';

		foreach($filePath as $v){
			$filelist[$v] = $this->getDir($v);	
		}

		$findLang = array();

		foreach($filelist as $vlist){
			foreach($vlist as $v){
				$ext = substr($v,strrpos($v,'.')+1,strlen($v));
				$data = file_get_contents($v);

	 			if($ext == 'php' || $ext=='js'){
	 				$data = preg_replace("!((/\*)[\s\S]*?(\*/))|(//.*)!", '',$data);//去掉注释里的中文
	 			}
 				preg_match_all('/([\x{4e00}-\x{9fa5}])+/u', $data,$result);
 				if(!empty($result[0])){
 					$findLang[$v] = $result[0];
 				}
 			}
		}
		dump($findLang);
	}
	public function getDir($dir,$list = array()){
		$dirs	= new Dir($dir);
		$dirs	= $dirs->toArray();
		foreach($dirs as $v){
			if($v['isDir']){
				$list = $this->getDir($v['pathname'],$list);
			
			}elseif($v['isFile'] && in_array($v['ext'],array('php','html','js'))){
				$list[] = $v['pathname'];
			}else{
				continue;
			}
		}
		return $list;
	}
	//下面是一些demo
	public function  demo(){
		$this->display();
	}

	public function getImageInfo(){
		$data = getImageInfo('2012/1110/18/509e2b90e7a89.jpeg');
		dump($data);
	}

	public function doupload(){
		dump($_POST);
	}

	public function dig() {
		$data = model('Tips')->findAll();
		$count0 = 0;
		$count1 = 0;
		foreach($data as $value) {
			$value['type'] == 1 ? $count1++ : $count0++;
		}

		$this->assign('count0', $count0);
		$this->assign('count1', $count1);
		$this->assign('data', $data);
		$this->display();
	}


	public function  tree(){
		$category = array(
			array('id'=>1, 'name'=>'A1', 'pid'=>0 ),
			array('id'=>2, 'name'=>'A2', 'pid'=>1 ),
			array('id'=>3, 'name'=>'A3', 'pid'=>1 ),
			array('id'=>4, 'name'=>'A4', 'pid'=>2 ),
			array('id'=>5, 'name'=>'A5', 'pid'=>4 ),
			array('id'=>6, 'name'=>'A6', 'pid'=>3 ),
		);

		print_r($this->_tree($category));

	}

	public function _tree($data){
			
			//所有节点的子节点
			$child = array();
			//hash缓存数组
			$hash = array();
			foreach($data as $dv){
				$hash[$dv['id']] = $dv;
				$tree[$dv['id']] = $dv;
				!isset($child[$dv['id']]) && $child[$dv['id']] = array();
				$tree[$dv['id']]['_child'] = & $child[$dv['id']];
				$child[$dv['pid']][] = & $tree[$dv['id']];		
			}

			return $child[0];
			
	}

	public function plot() {
		$this->display();
	}

	public function autotag(){
		//需要提取的文本
		$text = " 这里asxasx C++ ,test我也有很多T恤衣服！！！";
		//获取model
		$tagX = model("Tag");
		//设置text
		$tagX->setText($text);
		//获取前10个标签
		$result = $tagX->getTop(10);

		echo '<pre>';
		echo 'text:',$text;
		echo '<br/>结果:',$result;
		echo '
		
		---使用举例---
		
		核心php实现 
		( ajax 请求地址： public/Index/getTags  参数：text(内容)，limit:提取的标签个数 ）

		//需要提取的文本
		$text = "这里asxasx C++ ,test我也有很多T恤衣服！！！";
		//获取model
		$tagX = model("Tag");
		//设置text
		$tagX->setText($text);
		//获取前10个标签
		$result = $tagX->getTop(10);';
		echo '</pre>';
		
	}

	public function initLang(){
		model('Lang')->createCacheFile('public',0);
		model('Lang')->createCacheFile('public',1);
		model('Lang')->createCacheFile('channel',0);
		model('Lang')->createCacheFile('channel',1);
	}

	public function initLangPHP(){
		$lang = include(CONF_PATH.'/lang/ask_zh-cn2.php');
		$sql = "insert into sociax_lang (`key`,`appname`,`filetype`,`zh-cn`) VALUES ";
		foreach($lang as $k=>$v){
			$k = trim($k);
			$v = trim($v);
			$sqlArr[] =" ('{$k}','ASK','0','{$v}') ";
		}
		$sql = $sql.implode(',', $sqlArr).';';
		D('')->query($sql);
		echo  model()->getError();
		echo '<br/>',$sql,'<br/>';

	}

	// public function initLangJS(){
	// 	$lang = include(CONF_PATH.'/lang/public_zh-cn.js');
	// 	$sql = "insert into sociax_lang (`key`,`appname`,`filetype`,`zh-cn`) VALUES ";
	// 	foreach($lang as $k=>$v){
	// 		$sqlArr[] =" ('{$k}','PUBLIC','1','{$v}') ";
	// 	}
	// 	$sql = $sql.implode(',', $sqlArr).';';
	// 	D('')->query($sql);
	// }

	public function editLang(){
		// $lang = include(CONF_PATH.'/lang/public_en.js-');
		// foreach($lang as $k=>$v){
		// 	$map = $save = array();
		// 	$map['filetype'] = 1;
		// 	$map['key'] = $k;
		// 	$save['en'] = $v;
		// 	D('')->table('sociax_lang')->where($map)->save($save);			
		// }
		
		// $lang = include(CONF_PATH.'/lang/public_zh-cn.js-');
		// foreach($lang as $k=>$v){
		// 	$map = $save = array();
		// 	$map['filetype'] = 1;
		// 	$map['key'] = $k;
		// 	$save['zh-cn'] = $v;
		// 	D('')->table('sociax_lang')->where($map)->save($save);			
		// }
		
		// $lang = include(CONF_PATH.'/lang/public_zh-tw.js');
		// foreach($lang as $k=>$v){
		// 	$map = $save = array();
		// 	$map['filetype'] = 1;
		// 	$map['key'] = $k;
		// 	$save['zh-tw'] = $v;
		// 	D('')->table('sociax_lang')->where($map)->save($save);
		// }

		$lang = include(CONF_PATH.'/lang/ask_en.php');
		foreach($lang as $k=>$v){
			$map = $save = array();
			$map['filetype'] = 0;
			$map['key'] = $k;
			$save['en'] = $v;
			D('')->table('sociax_lang')->where($map)->save($save);
		}	

		$lang = include(CONF_PATH.'/lang/ask_zh-tw.php');
		foreach($lang as $k=>$v){
			$map = $save = array();
			$map['filetype'] = 0;
			$map['key'] = $k;
			$save['zh-tw'] = $v;
			D('')->table('sociax_lang')->where($map)->save($save);
		}	
	}

	public function langEdit(){
		$data = include(CONF_PATH.'/lang/tt.php');
		foreach($data as $k=>$v){
			$key = trim($k);
			$value = trim($v);
			$map['key'] = $key;
			$save['en'] = $value;
			model('Lang')->where($map)->save($save); 
		}
	}
	public function tt(){
		// $file = fopen(CONF_PATH.'/lang/t.php','r');
		// while(!feof($file)){
		// 	$d = fgets($file,'4096'); 
		// 	$data[] = trim($d)."',";
		// }
		// fclose($file);
		// //生成文件
		// $fileData = implode("\n",$data);
		// $fp = fopen(CONF_PATH.'/lang/tt.php','w+');
		// fwrite($fp, $fileData);
		// fclose($fp);
	}

	public function followTest() {
		model('Friend')->_getRelatedUserFromFriend(11860);
		model('Friend')->_getNewRelatedUser();
	}

	public function testSVn() {
		dump('CESHI');
	}

	public function testUserData()
	{
		model('UserData')->updateUserData();
	}

	public function addChannelData()
	{
		set_time_limit(0);
		$body = array(
					'秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，',
					'随手拍花朵，夏天森林公园行',
					'这个焦点怎么没有乱。。。',
					'春季一定要吃的家常菜 - 日志 - 这个菜炒出来卖相不好看，所以怎么拍的好看是个难题，点缀了红辣椒、葱丝和香菜叶之后总算能见人了。至于香椿和鸡蛋的比例，随个人喜欢，可以香椿多，也可以鸡蛋多，这个看个人啦',
					'部发表于18世纪的法国传奇小说，种下了魔力的种子，从文字到影像穿越时空开花结果。从法国宫廷、美国校园到韩国贵族，再到中国老上海，历经数个电影版本 的演绎，萦绕在《危险关系》戏里戏外的故事，自然生长，如镜子般折射时代、国别、名利场，一次次赋予古老的原著新鲜的生命力。本周四，章子怡、',
					'分享图片',
				);
		$attach = array(2352, 2351, 2350, 2349);
		$data['content'] = '';
		$type = 'postimage';
		$app = 'public';
		for($i = 0; $i < 150; $i++) {
			$data['body'] = $body[rand(0, 5)];
			$data['attach_id'] = $attach[rand(0, 4)];
			$result = model('Feed')->put($this->uid, $app, $type, $data);
			$add['channel_category_id'] = 13;
			$add['feed_id'] = $result['feed_id'];
			D('channel')->add($add);
		}
	}

	// 刷新图片数据
	public function updateImgData()
	{
		set_time_limit(0);
		$data = D('channel')->findAll();
		foreach($data as $value) {
			$feedInfo = model('Feed')->get($value['feed_id']);
			if($feedInfo['type'] == 'postimage') {
				$feedData = unserialize($feedInfo['feed_data']);
				$imgAttachId = is_array($feedData['attach_id']) ? $feedData['attach_id'][0] : $feedData['attach_id'];
				$attach = model('Attach')->getAttachById($imgAttachId);
				$path = UPLOAD_PATH.'/'.$attach['save_path'].$attach['save_name'];
				$imageInfo = getimagesize($path);
				$up['height'] = intval(ceil(195 * $imageInfo[1] / $imageInfo[0]));
				$up['width'] = 195;
				D('channel')->where('feed_id='.$value['feed_id'])->save($up);
			}
		}
	}	

	/**
	 * 插入Ts2.8用户信息
	 * @return void
	 */
	public function insertTsUser()
	{
		set_time_limit(0);
		// 获取插入用户数据
		$data = D('old_user')->field('email, password, sex, uname')->findAll();

		foreach($data as $value) {
			$user['uname'] = $value['uname'];
			$salt = rand(11111, 99999);
			$user['login_salt'] = $salt;
			$user['login'] = $value['email'];
			$user['email'] = $value['email'];
			$user['password'] = md5($value['password'].$salt);
			$user['ctime'] = time();
			$user['first_letter'] = getFirstLetter($value['uname']);
			$user['sex'] = ($value['sex'] == 0) ? 1 : 2;
			$user['is_audit'] = 1;
			$user['is_active'] = 1;
			// 添加用户
			$result = model('User')->add($user);
			// 添加用户组
			model('UserGroupLink')->domoveUsergroup($result, 3);
		}
	}
	function testpiny(){
		$unames = model('User')->field('uid,uname')->findAll();
		foreach ( $unames as $u ){
			if ( preg_match('/[\x7f-\xff]+/',$u['uname']) ){
				model('User')->setField('search_key' , $u['uname'].' '.model('PinYin')->Pinyin($u['uname']) ,'uid='.$u['uid']);
			} else {
				model('User')->setField('search_key' , $u['uname'] ,'uid='.$u['uid']);
			}
		}
	}

	public function testSort()
	{
		dump(111);
		model('CategoryTree')->setTable('area')->updateSort();
		dump(222);
	}

	public function testrm()
	{
		dump(111);
		model('CategoryTree')->setTable('area')->rmTreeCategory();
		dump(222);
	}

	public function testfeed()
	{
		$feedInfo = model('Feed')->get(5210);
		dump($feedInfo);
	}

	public function updateChannelUid()
	{
		set_time_limit(0);
		$channels = D('channel')->findAll();
		foreach($channels as $value) {
			$feedInfo = model('Feed')->get($value['feed_id']);
			$data['uid'] = $feedInfo['uid'];
			D('channel')->where('feed_channel_link_id='.$value['feed_channel_link_id'])->save($data);
		}
	}
}