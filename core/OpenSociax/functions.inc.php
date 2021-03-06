<?php
/**
 * Cookie 设置、获取、清除 (支持数组或对象直接设置) 2009-07-9
 * 1 获取cookie: cookie('name')
 * 2 清空当前设置前缀的所有cookie: cookie(null)
 * 3 删除指定前缀所有cookie: cookie(null,'think_') | 注：前缀将不区分大小写
 * 4 设置cookie: cookie('name','value') | 指定保存时间: cookie('name','value',3600)
 * 5 删除cookie: cookie('name',null)
 * $option 可用设置prefix,expire,path,domain
 * 支持数组形式:cookie('name','value',array('expire'=>1,'prefix'=>'think_'))
 * 支持query形式字符串:cookie('name','value','prefix=tp_&expire=10000')
 * 2010-1-17 去掉自动序列化操作，兼容其他语言程序。
 */
function cookie($name,$value='',$option=null) {
    // 默认设置
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), // cookie 名称前缀
        'expire' => C('COOKIE_EXPIRE'), // cookie 保存时间
        'path'   => C('COOKIE_PATH'),   // cookie 保存路径
        'domain' => C('COOKIE_DOMAIN'), // cookie 有效域名
    );

    // 参数设置(会覆盖黙认设置)
    if (!empty($option)) {
        if (is_numeric($option)) {
            $option = array('expire'=>$option);
        }else if( is_string($option) ) {
            parse_str($option,$option);
    	}
    	$config	=	array_merge($config,array_change_key_case($option));
    }

    // 清除指定前缀的所有cookie
    if (is_null($name)) {
       if (empty($_COOKIE)) return;
       // 要删除的cookie前缀，不指定则删除config设置的指定前缀
       $prefix = empty($value)? $config['prefix'] : $value;
       if (!empty($prefix))// 如果前缀为空字符串将不作处理直接返回
       {
           foreach($_COOKIE as $key=>$val) {
               if (0 === stripos($key,$prefix)){
                    setcookie($_COOKIE[$key],'',time()-3600,$config['path'],$config['domain']);
                    unset($_COOKIE[$key]);
               }
           }
       }
       return;
    }
    $name = $config['prefix'].$name;

    if (''===$value){
        //return isset($_COOKIE[$name]) ? unserialize($_COOKIE[$name]) : null;// 获取指定Cookie
        return isset($_COOKIE[$name]) ? ($_COOKIE[$name]) : null;// 获取指定Cookie
    }else {
        if (is_null($value)) {
            setcookie($name,'',time()-3600,$config['path'],$config['domain']);
            unset($_COOKIE[$name]);// 删除指定cookie
        }else {
            // 设置cookie
            $expire = !empty($config['expire'])? time()+ intval($config['expire']):0;
            //setcookie($name,serialize($value),$expire,$config['path'],$config['domain']);
            setcookie($name,($value),$expire,$config['path'],$config['domain']);
            //$_COOKIE[$name] = ($value);
        }
    }
}

/**
 * 获取站点唯一密钥，用于区分同域名下的多个站点
 * @return string
 */
function getSiteKey(){
    return md5(C('SECURE_KEY').C('SECURE_CODE').C('COOKIE_PREFIX'));
}

/**
 * 是否AJAX请求
 * @return bool
 */
function isAjax() {
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
		if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
			return true;
	}
	if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
		return true;
	return false;
}

/**
 * 字符串命名风格转换
 * type
 * =0 将Java风格转换为C的风格
 * =1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name,$type=0) {
    if($type) {
        return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    }else{
        $name = preg_replace("/[A-Z]/", "_\\0", $name);
        return strtolower(trim($name, "_"));
    }
}

/**
 * 优化格式的打印输出
 * @param string $var 变量
 * @param bool $return 是否return
 * @return mixed
 */
function dump($var, $return=false) {
	ob_start();
	var_dump($var);
	$output = ob_get_clean();
	if(!extension_loaded('xdebug')) {
		$output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
		$output = '<pre style="text-align:left">'. htmlspecialchars($output, ENT_QUOTES). '</pre>';
	}
    if (!$return) {
    	echo '<pre style="text-align:left">';
        echo($output);
        echo '</pre>';
    }else
        return $output;
}

/**
 * 自定义异常处理
 * @param string $msg 异常消息
 * @param string $type 异常类型
 * @return string
 */
function throw_exception($msg,$type='') {
    if(defined('IS_CGI') && IS_CGI)   exit($msg);
    if(class_exists($type,false))
        throw new $type($msg,$code,true);
    else
        die($msg); // 异常类型不存在则输出错误信息字串
}

/**
 * 系统自动加载ThinkPHP基类库和当前项目的model和Action对象
 * 并且支持配置自动加载路径
 * @param string $name 对象类名
 * @return void
 */
function halt($text) {
	return dump($text);
}

/**
 * 区分大小写的文件存在判断
 * @param string $filename 文件明
 * @return bool
 */
function file_exists_case($filename) {
    if(is_file($filename)) {
        if(IS_WIN && C('APP_FILE_CASE')) {
            if(basename(realpath($filename)) != basename($filename))
                return false;
        }
        return true;
    }
    return false;
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param mixed $mix 输入变量
 * @return string 输出唯一编号
 */
function to_guid_string($mix) {
    if(is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    }elseif(is_resource($mix)){
        $mix = get_resource_type($mix).strval($mix);
    }else{
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * 取得对象实例 支持调用类的静态方法
 * @param string $name 类名
 * @param string $method 方法
 * @param string $args 参数
 * @return object 对象实例
 */
function get_instance_of($name,$method='',$args=array()) {
    static $_instance = array();
    $identify   =   empty($args)?$name.$method:$name.$method.to_guid_string($args);
    if (!isset($_instance[$identify])) {
        if(class_exists($name)){
            $o = new $name();
            if(method_exists($o,$method)){
                if(!empty($args)) {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                }else {
                    $_instance[$identify] = $o->$method();
                }
            }
            else
                $_instance[$identify] = $o;
        }
        else
            halt(L('_CLASS_NOT_EXIST_').':'.$name);
    }
    return $_instance[$identify];
}

/**
 * 自动加载类
 * @param string $name 类名
 * @return void
 */
function __autoload($name) {
    // 检查是否存在别名定义
    if(import($name)) return ;
    // 自动加载当前项目的Actioon类和Model类
    if(substr($name,-5)=="Model") {
        import(LIB_PATH.'Model/'.ucfirst($name).'.class.php');
    }elseif(substr($name,-6)=="Action"){
        import(LIB_PATH.'Action/'.ucfirst($name).'.class.php');
    }else {
        // 根据自动加载路径设置进行尝试搜索
        if(C('APP_AUTOLOAD_PATH')) {
            $paths  =   explode(',',C('APP_AUTOLOAD_PATH'));
            foreach ($paths as $path){
                if(import($path.'/'.$name.'.class.php')) {
                    // 如果加载类成功则返回
                    return ;
                }
            }
        }
    }
    return ;
}

/**
 * 导入类库
 * @param string $name 类名
 * @return bool
 */
function import($filename) {
    static $_importFiles = array();
    global $ts;
	//$filename   =  realpath($filename);
    if (!isset($_importFiles[$filename])) {
		if(file_exists($filename)){
            require $filename;
            $_importFiles[$filename] = true;
        }
        elseif(file_exists($ts['_config']['alias_files'][$filename])){
			require $ts['_config']['alias_files'][$filename];
			$_importFiles[$filename] = true;
        }
		else
		{
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

/**
 * C函数用于读取/设置系统配置
 * @param string name 配置名称
 * @param string value 值
 * @return mixed 配置值|设置状态
 */
function C($name=null,$value=null) {
    global $ts;
    // 无参数时获取所有
    if(empty($name)) return $ts['_config'];
    // 优先执行设置获取或赋值
    if (is_string($name))
    {
        if (!strpos($name,'.')) {
            $name = strtolower($name);
            if (is_null($value))
                return isset($ts['_config'][$name])? $ts['_config'][$name] : null;
            $ts['_config'][$name] = $value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.',$name);
        $name[0]   = strtolower($name[0]);
        if (is_null($value))
            return isset($ts['_config'][$name[0]][$name[1]]) ? $ts['_config'][$name[0]][$name[1]] : null;
        $ts['_config'][$name[0]][$name[1]] = $value;
        return;
    }
    // 批量设置
    if(is_array($name))
        return $ts['_config'] = array_merge((array)$ts['_config'],array_change_key_case($name));
    return null;// 避免非法参数
}

//D函数的别名
function M($name='',$app='@') {
    return D($name,$app);
}

/**
 * D函数用于实例化Model
 * @param string name Model名称
 * @param string app Model所在项目
 * @return object
 */
function D($name='',$app='@') {
    static $_model = array();

    if(empty($name)) return new Model;
    if(empty($app) || $app=='@')   $app =  APP_NAME;

    $name = ucfirst($name);
    	
    if(isset($_model[$app.$name]))
        return $_model[$app.$name];

    $OriClassName = $name;
	$className =  $name.'Model';

    //优先载入核心的 所以不要和核心的model重名
    if(file_exists(ADDON_PATH.'/model/'.$className.'.class.php')){
		tsload(ADDON_PATH.'/model/'.$className.'.class.php');
	}elseif(file_exists(APPS_PATH.'/'.$app.'/Model/'.$className.'.class.php')){
        $common = APPS_PATH.'/'.$app.'/Common/common.php';
        if(file_exists($common)){
            tsload($common);
        }
        tsload(APPS_PATH.'/'.$app.'/Model/'.$className.'.class.php');
    }
    
    if(class_exists($className)) {

        $model = new $className();
    }else{
        $model  = new Model($name);
    }
    $_model[$app.$OriClassName] =  $model;
    return $model;
}

/**
 * A函数用于实例化Action
 * @param string name Action名称
 * @param string app Model所在项目
 * @return object
 */
function A($name,$app='@') {
    static $_action = array();

	if(empty($app) || $app=='@')   $app =  APP_NAME;

    if(isset($_action[$app.$name]))
        return $_action[$app.$name];

    $OriClassName = $name;
    $className =  $name.'Action';
    tsload(APP_ACTION_PATH.'/'.$className.'.class.php');

    if(class_exists($className)) {
        $action = new $className();
        $_action[$app.$OriClassName] = $action;
        return $action;
    }else {
        return false;
    }
}

/**
 * L函数用于读取/设置语言配置
 * @param string name 配置名称
 * @param string value 值
 * @return mixed 配置值|设置状态
 */
function L($key,$data = array()){
    $key = strtoupper($key);
     if(!isset($GLOBALS['_lang'][$key])){
          return $key;
     }
     if(empty($data)){
          return $GLOBALS['_lang'][$key];
     }
     $replace = array_keys($data);
     foreach($replace as &$v){
        $v = "{".$v."}";
     }
     return str_replace($replace,$data,$GLOBALS['_lang'][$key]);
}


/**
 * 用于判断文件后缀是否是图片
 * @param string file 文件路径，通常是$_FILES['file']['tmp_name']
 * @return bool
 */
function is_image_file($file){
  $fileextname = strtolower(substr(strrchr(rtrim(basename($file).'?'), "."),1,4));
  if(in_array($fileextname,array('jpg','jpeg','gif','png','bmp','ile'))){
    return true;
  }else{
    return false;
  }
}

/**
 * 用于判断文件后缀是否是PHP、EXE类的可执行文件
 * @param string file 文件路径
 * @return bool
 */
function is_notsafe_file($file){
  $fileextname = strtolower(substr(strrchr(rtrim(basename($file).'?'), "."),1,4));
  if(in_array($fileextname,array('php','php3','php4','php5','exe','sh'))){
    return true;
  }else{
    return false;
  }
}

/**
 * t函数用于过滤标签，输出没有html的干净的文本
 * @param string text 文本内容
 * @return string 处理后内容
 */
function t($text){
    $text = nl2br($text);
    $text = strip_tags($text);
    $text = htmlspecialchars_decode($text);
    $text = strip_tags($text);
    $text = htmlspecialchars($text,ENT_QUOTES);
    $text = str_ireplace(array("\r","\n","\t","&nbsp;"),'',$text);
    $text = trim($text);
	return $text;
}

/** 
 * h函数用于过滤不安全的html标签，输出安全的html
 * @param string $text 待过滤的字符串
 * @param string $type 保留的标签格式
 * @return string 处理后内容
 */
function h($text, $type = 'html'){
    // 转移尖括号
    $text = htmlspecialchars_decode($text);
    // 无标签格式
    $text_tags  = '';
    //只保留链接
    $link_tags  = '<a>';
    //只保留图片
    $image_tags = '<img>';
    //只存在字体样式
    $font_tags  = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
    //标题摘要基本格式
    $base_tags  = $font_tags.'<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
    //兼容Form格式
    $form_tags  = $base_tags.'<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
    //内容等允许HTML的格式
    $html_tags  = $base_tags.'<ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
    //专题等全HTML格式
    $all_tags   = $form_tags.$html_tags.'<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
    //过滤标签
    $text = strip_tags($text, ${$type.'_tags'});
    // 过滤攻击代码
    if($type != 'all') {
        // 过滤危险的属性，如：过滤on事件lang js
        while(preg_match('/(<[^><]+) (onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background|codebase|dynsrc|lowsrc)([^><]*)/i',$text,$mat)){
            $text = str_ireplace($mat[0], $mat[1].$mat[3], $text);
        }
        while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
            $text = str_ireplace($mat[0], $mat[1].$mat[3], $text);
        }
    }
    return $text;
}

/** 
 * U函数用于生成URL地址
 * @param string $url ThinkSNS特有URL标识符
 * @param array $params URL附加参数
 * @param bool $redirect 是否自动跳转到生成的URL
 * @return string 输出URL
 */
function U($url,$params=false,$redirect=false) {

	//普通模式
	if(false==strpos($url,'/')){
		$url	.='//';
	}

	//填充默认参数
	$urls	=	explode('/',$url);
	$app	=	isset($urls[0]) && !empty($urls[0]) ? $urls[0] : APP_NAME;
	$mod	=	isset($urls[1]) && !empty($urls[1]) ? $urls[1] : 'Index';
	$act	=	isset($urls[2]) && !empty($urls[2]) ? $urls[2] : 'index';

	//组合默认路径
	$site_url	=	SITE_URL.'/index.php?app='.$app.'&mod='.$mod.'&act='.$act;

	//填充附加参数
	if($params){
		if(is_array($params)){
			$params	=	http_build_query($params);
			$params	=	urldecode($params);
		}
		$params		=	str_replace('&amp;','&',$params);
		$site_url	.=	'&'.$params;
	}

	//开启路由和Rewrite
	if(C('URL_ROUTER_ON')){

		//载入路由
		$router_ruler	=	C('router');
		$router_key		=	$app.'/'.ucfirst($mod).'/'.$act;

		//路由命中
		if(isset($router_ruler[$router_key])){

			//填充路由参数
			if(false==strpos($router_ruler[$router_key],'://')){
				$site_url	=	SITE_URL.'/'.$router_ruler[$router_key];
			}else{
				$site_url	=	$router_ruler[$router_key];
			}

			//填充附加参数
			if($params){

				//解析替换URL中的参数
				parse_str($params,$r);
				foreach($r as $k=>$v){
					if(strpos($site_url,'['.$k.']')){
						$site_url	=	str_replace('['.$k.']',$v,$site_url);
					}else{
						$lr[$k]	=	$v;
					}
				}

				//填充剩余参数
				if(isset($lr) && is_array($lr) && count($lr)>0){
					$site_url	.=	'?'.http_build_query($lr);
				}

			}
		}
	}

	//输出地址或跳转
	if($redirect){
		redirect($site_url);
	}else{
		return $site_url;
	}
}

/** 
 * URL跳转函数
 * @param string $url ThinkSNS特有URL标识符
 * @param integer $time 跳转延时(秒)
 * @param string $msg 提示语
 * @return void
 */
function redirect($url,$time=0,$msg='') {
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if(empty($msg))
        $msg    =   "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if(0===$time) {
            header("Location: ".$url);
        }else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    }else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if($time!=0)
            $str   .=   $msg;
        exit($str);
    }
}

/**
 * 用来对应用缓存信息的读、写、删除
 *
 * $expire = null/0 表示永久缓存，否则为缓存有效期
 */
function S($name,$value='',$expire=null) {

	static $_cache = array();	//减少缓存读取

	$cache = model('Cache');

	//$name = C('DATA_CACHE_PREFIX').$name;

	if('' !== $value) {

		if(is_null($value)) {
			// 删除缓存
			$result =   $cache->rm($name);
			if($result)   unset($_cache[$name]);
			return $result;
		}else{
			// 缓存数据
			$cache->set($name,$value,$expire);
			$_cache[$name]     =   $value;
		}
		return true;
	}
	if(isset($_cache[$name]))
		return $_cache[$name];
	// 获取缓存数据
	$value      =  $cache->get($name);
	$_cache[$name]     =   $value;
	return $value;
}

/**
 * 文件缓存,多用来缓存配置信息
 *
 */
function F($name,$value='',$path=false) {
    static $_cache = array();
    if(!$path) {
    	$path	=	C('F_CACHE_PATH');
    }
    if(!is_dir($path)) {
    	mkdir($path,0777,true);
    }
    $filename   =   $path.'/'.$name.'.php';
    if('' !== $value) {
        if(is_null($value)) {
            // 删除缓存
            return unlink($filename);
        }else{
            // 缓存数据
            $dir   =  dirname($filename);
            // 目录不存在则创建
            if(!is_dir($dir))  mkdir($dir,0777,true);
            return @file_put_contents($filename,"<?php\nreturn ".var_export($value,true).";\n?>");
        }
    }
    if(isset($_cache[$name])) return $_cache[$name];
    // 获取缓存数据
    if(is_file($filename)) {
        $value   =  include $filename;
        $_cache[$name]   =   $value;
    }else{
        $value  =   false;
    }
    return $value;
}

function W($name,$data=array(),$return=false) {
    $class = $name.'Widget';
	if(file_exists(APP_LIB_PATH.'Widget/'.$class.'/'.$class.'.class.php')){
		tsload(APP_LIB_PATH.'Widget/'.$class.'/'.$class.'.class.php');
	}elseif(!empty($data['widget_appname']) && file_exists(APPS_PATH.'/'.$data['widget_appname'].'/Widget/'.$class.'/'.$class.'.class.php')){
        addLang($data['widget_appname']);
        tsload(APPS_PATH.'/'.$data['widget_appname'].'/Widget/'.$class.'/'.$class.'.class.php');
    }else{
		tsload(ADDON_PATH.'/widget/'.$class.'/'.$class.'.class.php');
	}
    if(!class_exists($class))
        throw_exception(L('_CLASS_NOT_EXIST_').':'.$class);
    $widget		=	new $class();
    $content	=	$widget->render($data);
    if($return)
        return $content;
    else
        echo $content;
}

// 实例化服务
function api($name,$params=array()) {
    return X($name,$params,'Api');
}

// 实例化服务
function service($name,$params=array()) {
    return X($name,$params,'Service');
}

// 实例化服务
function widget($name,$params=array(),$return=false) {
    return X($name,$params,'Widget');
}

// 实例化model
function model($name,$params=array()) {
    return X($name,$params,'Model');
}

// 调用接口服务
function X($name,$params=array(),$domain='service') {
    static $_service = array();
    //if(empty($app))
    $app =  C('DEFAULT_APP');

    if(isset($_service[$domain.'_'.$app.'_'.$name]))
        return $_service[$domain.'_'.$app.'_'.$name];

	$class = $name.$domain;
	if(file_exists(APP_LIB_PATH.$domain.'/'.$class.'.class.php')){

		tsload(APP_LIB_PATH.$domain.'/'.$class.'.class.php');
	}else{
		if($class == "FeedModel"){
	 		tsload(ADDON_PATH.'/'.strtolower($domain).'/'.$class.'.class.php',true);
		}
	}
	//服务不可用时 记录日志 或 抛出异常
	if(class_exists($class)){
		$obj   =  new $class($params);
		$_service[$domain.'_'.$app.'_'.$name] =  $obj;
		return $obj;
	}else{
		throw_exception(L('_CLASS_NOT_EXIST_').':'.$class);
	}
}

// 渲染模板
//$charset 不能是UTF8 否则IE下会乱码
function fetch($templateFile='',$tvar=array(),$charset='utf-8',$contentType='text/html',$display=false) {
	//注入全局变量ts
	global	$ts;
	$tvar['ts'] = $ts;
	//$GLOBALS['_viewStartTime'] = microtime(TRUE);

	if(null===$templateFile)
		// 使用null参数作为模版名直接返回不做任何输出
		return ;

	if(empty($charset))  $charset = C('DEFAULT_CHARSET');

	// 网页字符编码
	header("Content-Type:".$contentType."; charset=".$charset);

	header("Cache-control: private");  //支持页面回跳

	//页面缓存
	ob_start();
	ob_implicit_flush(0);

	// 模版名为空.
	if(''==$templateFile){
		$templateFile	=	APP_TPL_PATH.'/'.MODULE_NAME.'/'.ACTION_NAME.'.html';

	// 模版名为ACTION_NAME
	}elseif(file_exists(APP_TPL_PATH.'/'.MODULE_NAME.'/'.$templateFile.'.html')) {
		$templateFile	=	APP_TPL_PATH.'/'.MODULE_NAME.'/'.$templateFile.'.html';

	// 模版是绝对路径
	}elseif(file_exists($templateFile)){

	// 模版不存在
	}else{
		throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
	}

    //模版缓存文件
	$templateCacheFile	=	C('TMPL_CACHE_PATH').'/'.tsmd5($templateFile).'.php';

	//载入模版缓存
	if(!$ts['_debug'] && file_exists($templateCacheFile)) {
	//if(1==2){	//TODO  开发
		extract($tvar, EXTR_OVERWRITE);

		//载入模版缓存文件
		include $templateCacheFile;

	//重新编译
	}else{

		tshook('tpl_compile',array('templateFile',$templateFile));

		// 缓存无效 重新编译
		tsload(CORE_LIB_PATH.'/Template.class.php');
		tsload(CORE_LIB_PATH.'/TagLib.class.php');
		tsload(CORE_LIB_PATH.'/TagLib/TagLibCx.class.php');

		$tpl	=	Template::getInstance();
		// 编译并加载模板文件
		$tpl->load($templateFile,$tvar,$charset);
	}

	// 获取并清空缓存
	$content = ob_get_clean();

	// 模板内容替换
    $replace =  array(
        '__ROOT__'      =>  SITE_URL,           // 当前网站地址
        '__UPLOAD__'    =>  UPLOAD_URL,         // 上传文件地址
        '__PUBLIC__'    =>  PUBLIC_URL,         // 公共静态地址
        '__THEME__'     =>  THEME_PUBLIC_URL,   // 主题静态地址
        '__APP__'       =>  APP_PUBLIC_URL,     // 应用静态地址
    );

    if(C('TOKEN_ON')) {
        if(strpos($content,'{__TOKEN__}')) {
            // 指定表单令牌隐藏域位置
            $replace['{__TOKEN__}'] =  $this->buildFormToken();
        }elseif(strpos($content,'{__NOTOKEN__}')){
            // 标记为不需要令牌验证
            $replace['{__NOTOKEN__}'] =  '';
        }elseif(preg_match('/<\/form(\s*)>/is',$content,$match)) {
            // 智能生成表单令牌隐藏域
            $replace[$match[0]] = $this->buildFormToken().$match[0];
        }
    }

    // 允许用户自定义模板的字符串替换
    if(is_array(C('TMPL_PARSE_STRING')) )
        $replace =  array_merge($replace,C('TMPL_PARSE_STRING'));

    $content = str_replace(array_keys($replace),array_values($replace),$content);

	// 布局模板解析
	//$content = $this->layout($content,$charset,$contentType);
    // 输出模板文件
	if($display)
		echo $content;
	else
		return $content;
}

// 输出模版
function display($templateFile='',$tvar=array(),$charset='UTF8',$contentType='text/html') {
	fetch($templateFile,$tvar,$charset,$contentType,true);
}

function mk_dir($dir, $mode = 0755){
  if (is_dir($dir) || @mkdir($dir,$mode)) return true;
  if (!mk_dir(dirname($dir),$mode)) return false;
  return @mkdir($dir,$mode);
}

/**
 * 字节格式化 把字节数格式为 B K M G T 描述的大小
 * @return string
 */
function byte_format($size, $dec=2) {
	$a = array("B", "KB", "MB", "GB", "TB", "PB");
	$pos = 0;
	while ($size >= 1024) {
		 $size /= 1024;
		   $pos++;
	}
	return round($size,$dec)." ".$a[$pos];
}

/**
 * 获取客户端IP地址
 */
function get_client_ip() {
   if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
       $ip = getenv("HTTP_CLIENT_IP");
   else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
       $ip = getenv("HTTP_X_FORWARDED_FOR");
   else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
       $ip = getenv("REMOTE_ADDR");
   else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
       $ip = $_SERVER['REMOTE_ADDR'];
   else
       $ip = "unknown";
   return($ip);
}

/**
 * 记录日志
 * Enter description here ...
 * @param unknown_type $app_group
 * @param unknown_type $action
 * @param unknown_type $data
 * @param unknown_type $isAdmin 是否管理员日志
 */
function LogRecord($app_group,$action,$data,$isAdmin=false){
	static $log = null;
	if($log == null){
		$log = model('Logs');
	}
	return $log->load($app_group)->action($action)->record($data,$isAdmin);
}

/**
 * 验证权限方法
 * @param string $load 应用 - 模块 字段
 * @param string $action 权限节点字段
 * @param unknown_type $group 是否指定应用内部用户组
 */
function CheckPermission($load = '', $action = '', $group = ''){
	if(empty($load) || empty($action)) {
		return false;
	}
	$Permission = model('Permission')->load($load);
	if(!empty($group)){
		return $Permission->group($group)->check($action);
	}

	return $Permission->check($action);
}

//获取当前用户的前台管理权限
function manageList($uid){
    $list = model('App')->getManageApp($uid);
    return $list;
}

/**
 * 取一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
 * @param $pArray 一个二维数组
 * @param $pKey 数组的键的名称
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey="", $pCondition=""){
    $result = array();
    if(is_array($pArray)){
        foreach($pArray as $temp_array){
            if(is_object($temp_array)){
                $temp_array = (array) $temp_array;
            }
            if((""!=$pCondition && $temp_array[$pCondition[0]]==$pCondition[1]) || ""==$pCondition) {
                $result[] = (""==$pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
            }
        }
        return $result;
    }else{
        return false;
    }
}

/**
 * 获取字符串的长度
 *
 * 计算时, 汉字或全角字符占1个长度, 英文字符占0.5个长度
 *
 * @param string  $str
 * @param boolean $filter 是否过滤html标签
 * @return int 字符串的长度
 */
function get_str_length($str, $filter = false){
	if ($filter) {
		$str = html_entity_decode($str, ENT_QUOTES);
		$str = strip_tags($str);
	}
	return (strlen($str) + mb_strlen($str, 'UTF8')) / 4;
}

function getShort($str, $length = 40, $ext = '') {
	$str	=	htmlspecialchars($str);
	$str	=	strip_tags($str);
	$str	=	htmlspecialchars_decode($str);
	$strlenth	=	0;
	$out		=	'';
	preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $match);
	foreach($match[0] as $v){
		preg_match("/[\xe0-\xef][\x80-\xbf]{2}/",$v, $matchs);
		if(!empty($matchs[0])){
			$strlenth	+=	1;
		}elseif(is_numeric($v)){
			//$strlenth	+=	0.545;  // 字符像素宽度比例 汉字为1
			$strlenth	+=	0.5;    // 字符字节长度比例 汉字为1
		}else{
			//$strlenth	+=	0.475;  // 字符像素宽度比例 汉字为1
			$strlenth	+=	0.5;    // 字符字节长度比例 汉字为1
		}

		if ($strlenth > $length) {
			$output .= $ext;
			break;
		}

		$output	.=	$v;
	}
	return $output;
}

/**
 * 检查字符串是否是UTF8编码
 * @param string $string 字符串
 * @return Boolean
 */
function is_utf8($string) {
    return preg_match('%^(?:
         [\x09\x0A\x0D\x20-\x7E]            # ASCII
       | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
       |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
       |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
       |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
       | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
       |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
   )*$%xs', $string);
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents,$from,$to){
    $from   =  strtoupper($from)=='UTF8'? 'utf-8':$from;
    $to       =  strtoupper($to)=='UTF8'? 'utf-8':$to;
    if( strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents)) ){
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if(is_string($fContents) ) {
        if(function_exists('mb_convert_encoding')){
            return mb_convert_encoding ($fContents, $to, $from);
        }elseif(function_exists('iconv')){
            return iconv($from,$to,$fContents);
        }else{
            return $fContents;
        }
    }
    elseif(is_array($fContents)){
        foreach ( $fContents as $key => $val ) {
            $_key =     auto_charset($key,$from,$to);
            $fContents[$_key] = auto_charset($val,$from,$to);
            if($key != $_key )
                unset($fContents[$key]);
        }
        return $fContents;
    }
    else{
        return $fContents;
    }
}

/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @param string $alt   已失效
 * @return string
 */
function friendlyDate($sTime,$type = 'normal',$alt = 'false') {
    if (!$sTime)
        return '';
	//sTime=源时间，cTime=当前时间，dTime=时间差
	$cTime		=	time();
	$dTime		=	$cTime - $sTime;
	$dDay		=	intval(date("z",$cTime)) - intval(date("z",$sTime));
	//$dDay		=	intval($dTime/3600/24);
	$dYear		=	intval(date("Y",$cTime)) - intval(date("Y",$sTime));
	//normal：n秒前，n分钟前，n小时前，日期
	if($type=='normal'){
		if( $dTime < 60 ){
			if($dTime < 0){
				return '刚刚';	//by yangjs
			}else{
				return $dTime."秒前";
			}
		}elseif( $dTime < 3600 ){
			return intval($dTime/60)."分钟前";
		//今天的数据.年份相同.日期相同.
		}elseif( $dYear==0 && $dDay == 0  ){
			//return intval($dTime/3600)."小时前";
			return '今天'.date('H:i',$sTime);
		}elseif($dYear==0){
			return date("m月d日 H:i",$sTime);
		}else{
			return date("Y-m-d H:i",$sTime);
		}
	}elseif($type=='mohu'){
		if( $dTime < 60 ){
			return $dTime."秒前";
		}elseif( $dTime < 3600 ){
			return intval($dTime/60)."分钟前";
		}elseif( $dTime >= 3600 && $dDay == 0  ){
			return intval($dTime/3600)."小时前";
		}elseif( $dDay > 0 && $dDay<=7 ){
			return intval($dDay)."天前";
		}elseif( $dDay > 7 &&  $dDay <= 30 ){
			return intval($dDay/7) . '周前';
		}elseif( $dDay > 30 ){
			return intval($dDay/30) . '个月前';
		}
	//full: Y-m-d , H:i:s
	}elseif($type=='full'){
		return date("Y-m-d , H:i:s",$sTime);
	}elseif($type=='ymd'){
		return date("Y-m-d",$sTime);
	}else{
		if( $dTime < 60 ){
			return $dTime."秒前";
		}elseif( $dTime < 3600 ){
			return intval($dTime/60)."分钟前";
		}elseif( $dTime >= 3600 && $dDay == 0  ){
			return intval($dTime/3600)."小时前";
		}elseif($dYear==0){
			return date("Y-m-d H:i:s",$sTime);
		}else{
			return date("Y-m-d H:i:s",$sTime);
		}
	}
}

/**
 * 
 * 正则替换和过滤内容
 * 
 * @param  $html
 * @author jason
 */
function preg_html($html){
	$p = array("/<[a|A][^>]+(topic=\"true\")+[^>]*+>#([^<]+)#<\/[a|A]>/",
			"/<[a|A][^>]+(data=\")+([^\"]+)\"[^>]*+>[^<]*+<\/[a|A]>/",
			"/<[img|IMG][^>]+(src=\")+([^\"]+)\"[^>]*+>/");
	$t = array('topic{data=$2}','$2','img{data=$2}');
	$html = preg_replace($p, $t, $html);
	$html 	= strip_tags($html,"<br/>");
	return $html;
}

//解析数据成网页端显示格式
function parse_html($html){
	$html = htmlspecialchars_decode($html);
    //以下三个过滤是旧版兼容方法-可屏蔽
    $html = preg_replace("/img{data=([^}]*)}/"," ", $html);
	$html = preg_replace("/topic{data=([^}]*)}/",'<a href="$1" topic="true">#$1#</a>', $html);
    $html = preg_replace_callback("/@{uid=([^}]*)}/", "_parse_at_by_uid", $html);
    //链接替换
    $html = str_replace('[SITE_URL]',SITE_URL,$html);
    //外网链接地址处理
    //$html = preg_replace_callback('/((?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*\.)?[a-zA-Z0-9][a-zA-Z0-9\-]*(?:\.[a-zA-Z0-9]+)+(?:\:[0-9]*)?(?:\/[^\x{2e80}-\x{9fff}\s<\'\"“”‘’,，。]*)?)/u', '_parse_url', $html);
    //表情处理
    $html = preg_replace_callback("/(\[.+?\])/is",_parse_expression,$html);
    //话题处理
    $html = str_replace("＃", "#", $html);
    $html = preg_replace_callback("/#([^#]*[^#^\s][^#]*)#/is",_parse_theme,$html);
    //@提到某人处理
    $html = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", "_parse_at_by_uname",$html);
    //敏感词过滤
    $html = filter_keyword($html);
	return $html;
}

//解析成api显示格式
function parseForApi($html){
    $html = strip_tags(htmlspecialchars_decode($html));
    //以下三个过滤是旧版兼容方法-可屏蔽
    $html = preg_replace_callback("/img{data=([^}]*)}/",'_parse_img_forapi', $html);
    $html = preg_replace_callback("/@{uid=([^}]*)}/", '_parse_at_forapi', $html);
    $html = preg_replace("/topic{data=([^}]*)}/",'#$1#', $html);
    $html = str_replace(array('[SITE_URL]','&nbsp;'),array(SITE_URL,' '),$html);
    $html = filter_keyword($html);
    //敏感词过滤
    return $html;
}

/**
 * 格式化微博,替换话题
 * @param string  $content 待格式化的内容
 * @param boolean $url     是否替换URL
 * @return string
 */
function format($content,$url=false){
    return $content;
}

function replaceTheme($content){
    $content = str_replace("＃", "#", $content);
    $content = preg_replace_callback("/#([^#]*[^#^\s][^#]*)#/is",_parse_theme,$content);
    return $content;
}

function replaceUrl($content){
    //$content = preg_replace_callback('/((?:https?|ftp):\/\/(?:[a-zA-Z0-9][a-zA-Z0-9\-]*)*(?:\/[^\x{2e80}-\x{9fff}\s<\'\"“”‘’,，。]*)?)/u', '_parse_url', $content);
    $content = str_replace('[SITE_URL]', SITE_URL, $content);
    $content = preg_replace_callback('/((?:https?|mailto|ftp):\/\/([^\s<\'\"“”‘’，。]*)?)/u', '_parse_url', $content);
    return $content;
}


/**
 * 表情替换 [格式化微博与格式化评论专用]
 * @param array $data
 */
function _parse_expression($data) {
	if(preg_match("/#.+#/i",$data[0])) {
		return $data[0];
	}
	$allexpression = model('Expression')->getAllExpression();
	$info = $allexpression[$data[0]];
	if($info) {
		return preg_replace("/\[.+?\]/i","<img src='".__THEME__."/image/expression/miniblog/".$info['filename']."' />",$data[0]);
	}else {
		return $data[0];
	}
}

/**
 * 格式化微博,替换链接地址
 * @param string $url
 */
function _parse_url($url){
	$str = '<div class="url">';
    if ( strpos( $url[0] , 'youku.com') || strpos( $url[0] , 'tudou.com') ){
		$str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-video"></a>';
	} else if ( strpos( $url[0] , 'taobao.com') ){
		$str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-taobao"></a>';
	} else {
		$str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-web"></a>';
	}
    $str .= '<div class="url-detail" style="display:none;">'.$url[0].'</div></div>';
	return $str;
}

/**
 * 话题替换 [格式化微博专用]
 * @param array $data
 * @return string
 */
function _parse_theme($data){
    //如果话题被锁定，则不带链接
    if(!model('FeedTopic')->where(array('name'=>$data[1]))->getField('lock')){
        return "<a href=".U('public/Topic/index',array('k'=>urlencode($data[1]))).">".$data[0]."</a>";
    }else{
        return $data[0];
    }
}

/**
 * 根据用户昵称获取用户ID [格式化微博与格式化评论专用]
 * @param array $name
 * @return string
 */
function _parse_at_by_uname($name) {
	$info = static_cache( 'user_info_uname_'.$name[1]);
	if ( !$info && intval($name[1]) == 0){
		$info = model( 'User')->getUserInfoByName($name[1]);
		if ( !$info ){
			$info = 1;
		}
		static_cache( 'user_info_uname_'.$name[1] , $info);
	}
	if ( $info && $info['is_active'] && $info['is_audit'] && $info['is_init'] ) {
		return '<a href="'.$info['space_url'].'" uid="'.$info['uid'].'" event-node="face_card" target="_blank">'.$name[0]."</a>";
	}else {
		return $name[0];
    }
}

/**
 * 解析at成web端显示格式
 */
function _parse_at_by_uid($result){
    $_userInfo = explode("|",$result[1]);
    $userInfo = model('User')->getUserInfo($_userInfo[0]);
    return '<a uid="'.$userInfo['uid'].'" event-node="face_card" data="@{uid='.$userInfo['uid'].'|'.$userInfo['uname'].'}" 
            href="'.$userInfo['space_url'].'">@'.$userInfo['uname'].'</a>';
}

/**
 * 解析at成api显示格式
 */
function _parse_at_forapi($html){
    $_userInfo = explode("|",$html[1]);
    return "@".$_userInfo[1];
}

/**
 * 解析图片成api格式
 */
function _parse_img_forapi($html){
    $basename = basename($html[1]);
    return "[".substr($basename,0, strpos($basename, "."))."]";
}

/**
 * 敏感词过滤
 */
function filter_keyword($html){
    static $audit  =null;
    static $auditSet = null;
    if($audit == null){ //第一次
        $audit = model('Xdata')->get('keywordConfig');
        $audit = explode(',',$audit);
        $auditSet =  model('Xdata')->get('admin_Config:audit');
    }
    // 不需要替换
    if(empty($audit) || $auditSet['open'] == '0'){
        return $html;
    }
    return str_replace($audit, $auditSet['replace'], $html);
}

//文件名
/**
 * 获取缩略图
 * @param unknown_type $filename 原图路劲、url
 * @param unknown_type $width 宽度
 * @param unknown_type $height 高
 * @param unknown_type $cut 是否切割 默认不切割
 * @return string
 */
function getThumbImage($filename,$width=100,$height='auto',$cut=false,$replace=false){
	$filename  = str_ireplace(UPLOAD_URL,'',$filename);	//将URL转化为本地地址
	$info      = pathinfo($filename);
    $oldFile   = $info['dirname'].DIRECTORY_SEPARATOR.$info['filename'].'.'.$info['extension'];
	$thumbFile = $info['dirname'].DIRECTORY_SEPARATOR.$info['filename'].'_'.$width.'_'.$height.'.'.$info['extension'];

	$oldFile = str_replace('\\','/', $oldFile);
	$thumbFile = str_replace('\\','/',$thumbFile);

    //原图不存在直接返回
	if(!file_exists(UPLOAD_PATH.$oldFile)){
        @unlink(UPLOAD_PATH.$thumbFile);
		$info['src']	= $oldFile;
		$info['width']  = intval($width);
		$info['height'] = intval($height);
		return $info;
	//缩图已存在并且 replace替换为false
    }elseif(file_exists(UPLOAD_PATH.$thumbFile) && !$replace){
        $imageinfo      = getimagesize(UPLOAD_PATH.$thumbFile);
        $info['src']    = $thumbFile;
        $info['width']  = intval($imageinfo[0]);
        $info['height'] = intval($imageinfo[1]);
        return $info;
    //执行缩图操作
    }else{
        $oldimageinfo     = getimagesize(UPLOAD_PATH.$oldFile);
        $old_image_width  = intval($oldimageinfo[0]);
        $old_image_height = intval($oldimageinfo[1]);
        if($old_image_width<=$width && $old_image_height<=$height){
            @unlink(UPLOAD_PATH.$thumbFile);
            @copy(UPLOAD_PATH.$oldFile,UPLOAD_PATH.$thumbFile);
            $info['src']    = $thumbFile;
            $info['width']  = $old_image_width;
            $info['height'] = $old_image_height;
            return $info;
        }else{
            tsload( ADDON_PATH.'/liberary/Image.class.php' );
            //生成缩略图
            if($cut){
                Image::cut(UPLOAD_PATH.$filename, UPLOAD_PATH.$thumbFile, $width, $height);
            }else{
                Image::thumb(UPLOAD_PATH.$filename, UPLOAD_PATH.$thumbFile, '', $width, $height);   
            }
            //缩图不存在
            if(!file_exists($thumbFile)){
                $thumbFile = $oldFile;
            }
            $info = Image::getImageInfo(UPLOAD_PATH.$thumbFile);
            $info['src'] = $thumbFile;
            return $info;
        }
	}
}

//获取图片信息 - 兼容云
function getImageInfo($file){
    $cloud = model('CloudImage');
    if($cloud->isOpen()){
        $imageInfo = getimagesize($cloud->getImageUrl($file));
    }else{
        $imageInfo = getimagesize(UPLOAD_PATH.'/'.$file);
    }
    return $imageInfo;
}

//获取图片地址 - 兼容云
function getImageUrl($file,$width='0',$height='auto',$cut=false,$replace=false){
    $cloud = model('CloudImage');
    if($cloud->isOpen()){
        $imageUrl = $cloud->getImageUrl($file,$width,$height,$cut);
    }else{
        if($width && $height){
            $thumbInfo = getThumbImage($file,$width,$height,$cut,$replace);
            $imageUrl = UPLOAD_URL.'/'.ltrim($thumbInfo['src'],'/');
        }else{
            $imageUrl = UPLOAD_URL.'/'.ltrim($file,'/');
        }
    }
    return $imageUrl;
}

function getSiteLogo($logoid = ''){
	if(empty($logoid)){
		$logoid = $GLOBALS['ts']['site']['site_logo'];
	}
	if($logoInfo = model('Attach')->getAttachById($logoid)){
		$logo = UPLOAD_URL.'/'.$logoInfo['save_path'].$logoInfo['save_name'];
		!file_exists(UPLOAD_PATH.'/'.$logoInfo['save_path'].$logoInfo['save_name']) && $logo = THEME_PUBLIC_URL.'/'.C('site_logo'); 
	}else{
		$logo = THEME_PUBLIC_URL.'/'.C('site_logo');
	}
	return $logo;
}

//获取当前访问者的客户端类型
function getVisitorClient(){
	//客户端类型，0：网站；1：手机版；2：Android；3：iPhone；3：iPad；3：win.Phone
	return '0';
}

//获取一条微博的来源信息
function getFromClient($type=0, $app='public', $app_name){
	if ( $app != 'public' ){
		return '来自<a href="'.U($app).'" target="_blank">'.$app_name."</a>";
	}
    $type = intval($type);
    $client_type = array(
        0 => '来自网站',
        1 => '来自手机版',
        2 => '来自Android客户端',
        3 => '来自iPhone客户端',
        4 => '来自iPad客户端',
        5 => '来自win.Phone客户端',
    );

    //在列表中的
    if(in_array($type, array_keys( $client_type ))){
        return $client_type[$type];
    }else{
        return $client_type[0];
    }
}

/**
 * DES加密函数
 *
 * @param string $input
 * @param string $key
 */
function desencrypt($input,$key) {

    //使用新版的加密方式
    tsload(ADDON_PATH.'/liberary/DES_MOBILE.php');
    $desc = new DES_MOBILE();
    return $desc->setKey($key)->encrypt($input);

	$size = mcrypt_get_block_size('des', 'ecb');
	$input = pkcs5_pad($input, $size);

	$td = mcrypt_module_open('des', '', 'ecb', '');
	$iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	@mcrypt_generic_init($td, $key, $iv);
	$data = mcrypt_generic($td, $input);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	$data = base64_encode($data);
	return $data;
}

/**
 * DES解密函数
 *
 * @param string $input
 * @param string $key
 */
function desdecrypt($encrypted,$key) {
    //使用新版的加密方式
    tsload(ADDON_PATH.'/liberary/DES_MOBILE.php');
    $desc = new DES_MOBILE();
    return $desc->setKey($key)->decrypt($encrypted);

	$encrypted = base64_decode($encrypted);
	$td = mcrypt_module_open('des','','ecb','');	//使用MCRYPT_DES算法,cbc模式
	$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	$ks = mcrypt_enc_get_key_size($td);
	@mcrypt_generic_init($td, $key, $iv);			//初始处理

	$decrypted = mdecrypt_generic($td, $encrypted); //解密

	mcrypt_generic_deinit($td);       				//结束
	mcrypt_module_close($td);

	$y = pkcs5_unpad($decrypted);
	return $y;
}

/**
 * @see desencrypt()
 */
function pkcs5_pad($text, $blocksize) {
	$pad = $blocksize - (strlen($text) % $blocksize);
	return $text . str_repeat(chr($pad), $pad);
}

/**
 * @see desdecrypt()
 */
function pkcs5_unpad($text) {
	$pad = ord($text{strlen($text)-1});

	if ($pad > strlen($text))
		return false;
	if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
		return false;

	return substr($text, 0, -1 * $pad);
}


function getOAuthToken($uid){
	return md5($uid . 'sociaxV1');
}

function getOAuthTokenSecret(){
	return md5($_SERVER['REQUEST_TIME'] . 'sociaxV1');
}

// 获取字串首字母
function getFirstLetter($s0) {
    $firstchar_ord = ord(strtoupper($s0{0}));
    if($firstchar_ord >= 65 and $firstchar_ord <= 91) return strtoupper($s0{0});
    if($firstchar_ord >= 48 and $firstchar_ord <= 57) return '#';
    $s = iconv("UTF-8", "gb2312", $s0);
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if($asc>=-20319 and $asc<=-20284) return "A";
    if($asc>=-20283 and $asc<=-19776) return "B";
    if($asc>=-19775 and $asc<=-19219) return "C";
    if($asc>=-19218 and $asc<=-18711) return "D";
    if($asc>=-18710 and $asc<=-18527) return "E";
    if($asc>=-18526 and $asc<=-18240) return "F";
    if($asc>=-18239 and $asc<=-17923) return "G";
    if($asc>=-17922 and $asc<=-17418) return "H";
    if($asc>=-17417 and $asc<=-16475) return "J";
    if($asc>=-16474 and $asc<=-16213) return "K";
    if($asc>=-16212 and $asc<=-15641) return "L";
    if($asc>=-15640 and $asc<=-15166) return "M";
    if($asc>=-15165 and $asc<=-14923) return "N";
    if($asc>=-14922 and $asc<=-14915) return "O";
    if($asc>=-14914 and $asc<=-14631) return "P";
    if($asc>=-14630 and $asc<=-14150) return "Q";
    if($asc>=-14149 and $asc<=-14091) return "R";
    if($asc>=-14090 and $asc<=-13319) return "S";
    if($asc>=-13318 and $asc<=-12839) return "T";
    if($asc>=-12838 and $asc<=-12557) return "W";
    if($asc>=-12556 and $asc<=-11848) return "X";
    if($asc>=-11847 and $asc<=-11056) return "Y";
    if($asc>=-11055 and $asc<=-10247) return "Z";
    return '#';
}

// 区间调试开始
function debug_start($label=''){
    $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
    $GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

// 区间调试结束，显示指定标记到当前位置的调试
function debug_end($label=''){   
    $GLOBALS[$label]['_endTime'] = microtime(TRUE);
    $log =  'Process '.$label.': Times '.number_format($GLOBALS[$label]['_endTime']-$GLOBALS[$label]['_beginTime'],6).'s ';
    $GLOBALS[$label]['_endMem'] = memory_get_usage();
    $log .= ' Memories '.number_format(($GLOBALS[$label]['_endMem']-$GLOBALS[$label]['_beginMem'])/1024).' k';
    $GLOBALS['logs'][$label] = $log;
} 

// 全站语言设置 - PHP
function setLang() {
    // 获取当前系统的语言
    $lang = getLang();
    // 设置全站语言变量
    if(!isset($GLOBALS['_lang'])) {
        $GLOBALS['_lang'] = array();
        $_lang = array();
        if(file_exists(LANG_PATH.'/public_'.$lang.'.php')) {
            $_lang = include(LANG_PATH.'/public_'.$lang.'.php');
            $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
        }
        $removeApps = array('api', 'widget', 'public');
        if(!in_array(TRUE_APPNAME, $removeApps)) {
            if(file_exists(LANG_PATH.'/'.strtolower(TRUE_APPNAME).'_'.$lang.'.php')) {
                $_lang = include(LANG_PATH.'/'.strtolower(TRUE_APPNAME).'_'.$lang.'.php');
                $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
            }
        }
    }
}

//主动添加语言包
function addLang($appname){
    static $langHash = array();
    if(isset($langHash[$appname])){
        return true;
    }
    $langHash[$appname] = 1;
    $lang = getLang();
    if(file_exists(LANG_PATH.'/'.$appname.'_'.$lang.'.php')){
        $_lang = include(LANG_PATH.'/'.$appname.'_'.$lang.'.php');
        empty($_lang) && $_lang = array();
        $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
        return true;
    }
    return false;
}

// 全站语言设置 - JavaScript
function setLangJavsScript() {
    // 获取当前系统的语言
    $lang = getLang();
    // 获取相应要载入的JavaScript语言包路径
    $langJsList = array();
    if(file_exists(LANG_PATH.'/public_'.$lang.'.js')) {
        $langJsList[] = LANG_URL.'/public_'.$lang.'.js';
    }
    $removeApps = array('api', 'widget', 'public');
    if(!in_array(TRUE_APPNAME, $removeApps)) {
        if(file_exists(LANG_PATH.'/'.strtolower(TRUE_APPNAME).'_'.$lang.'.js')) {
            $langJsList[] = LANG_URL.'/'.strtolower(TRUE_APPNAME).'_'.$lang.'.js';
        }
    }

    return $langJsList;
}

// 获取站点所使用的语言
function getLang() {
    $defaultLang = 'zh-cn';
    $cLang = cookie('lang');
    $lang = '';
    // 判断是否已经登录
    if(isset($_SESSION['mid']) && $_SESSION['mid']>0){
        $userInfo = model('User')->getUserInfo($_SESSION['mid']);
        return $userInfo['lang'];
    }
    // 是否存在cookie值，如果存在显示默认的cookie语言值
    if(is_null($cLang)) {
        // 手机端直接返回默认语言
        if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $defaultLang;
        }
        // 判断操作系统的语言状态
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $accept_language = strtolower($accept_language);
        $accept_language_array = explode(',', $accept_language);
        $lang = array_shift($accept_language_array);
        // 获取默认语言
        $fields = model('Lang')->getLangType();
        $lang = in_array($lang, $fields) ? $lang : $defaultLang;
        cookie('lang', $lang);
    } else {
        $lang = $cLang;
    }

    return $lang;
}

function ShowNavMenu($apps){
    $html = '';
    foreach($apps as $app){
        $child_menu = unserialize($app['child_menu']);
        if(empty($child_menu)){
            continue;
        }
        foreach($child_menu as $k=>$cm){

            if($k == $app['app_name']){
                //我的XXX
                $title = L('PUBLIC_MY').L('PUBLIC_APPNAME_'.strtoupper($k));
                $url = U($cm['url']);
            }else{
                //其他导航 一般不会有其他导航
                $title = L($k);
                //地址直接是cm值
                $url = U($cm);
            }
            
            $html .="<dd><a href='{$url}'>{$title}</a></dd>";    
        }
    }
    return $html;
}

function showNavProfile($apps){
    $html = '';
    foreach($apps as $app){

        $child_menu = unserialize($app['child_menu']);

        if(empty($child_menu)){
            continue;
        }
        foreach($child_menu as $k=>$cm){

            if($k == $app['app_name'] && $cm['public'] == 1){
                //我的XXX 只会显示这类数据
                $title = "<img width='16' src='{$app['icon_url']}'> ".L('PUBLIC_APPNAME_'.strtoupper($k));
                $url   = U('public/Profile/appprofile',array('appname'=>$k));    
                $html .="<dd class='profile_{$app['app_name']}'><a href='{$url}'>{$title}</a></dd>";    
            }
        }
    }
    return $html;   
}

/**
 * 是否能进行邀请
 * @param integer $uid 用户ID
 */
function isInvite(){
    $config = model('Xdata')->get('admin_Config:register');
    $result = false;
    if(in_array($config['register_type'], array('open', 'invite'))) {
        $result = true;
    }

    return $result;
}

/**
 * 传统形式显示无限极分类树
 * @param array $data 树形结构数据
 * @param string $stable 所操作的数据表
 * @param integer $left 样式偏移
 * @return string 树形结构的HTML数据
 */
function showTreeCategory($data, $stable, $left){
    $html = '<ul class="sort">';
    foreach($data as $val) {
        // 判断是否有符号
        $isFold = empty($val['child']) ? false : true;
        $html .= '<li id="'.$stable.'_'.$val['id'].'" class="underline" style="padding-left:'.$left.'px;">
                  <div class="c1">';
        if($isFold) {
            $html .= '<a href="javascript:;" onclick="admin.foldCategory('.$val['id'].')"><img id="img_'.$val['id'].'" src="'.__THEME__.'/admin/image/on.png" /></a>';
        }
        $html .= '<span>'.$val['title'].'</span></div>
                  <div class="c2">
                  <a href="javascript:;" onclick="admin.addTreeCategory('.$val['id'].', \''.$stable.'\');">添加子分类</a>&nbsp;-&nbsp;
                  <a href="javascript:;" onclick="admin.upTreeCategory('.$val['id'].', \''.$stable.'\');">编辑</a>&nbsp;-&nbsp;
                  <a href="javascript:;" onclick="admin.rmTreeCategory('.$val['id'].', \''.$stable.'\');">删除</a>
                  </div>
                  <div class="c3">
                  <a href="javascript:;" onclick="admin.moveTreeCategory('.$val['id'].', \'up\', \''.$stable.'\')" class="ico_top mr5"></a>
                  <a href="javascript:;" onclick="admin.moveTreeCategory('.$val['id'].', \'down\', \''.$stable.'\')" class="ico_btm"></a>
                  </div>
                  </li>';
        if(!empty($val['child'])) {
            $html .= '<li id="sub_'.$val['id'].'" style="display:none;">';
            $html .= showTreeCategory($val['child'], $stable, $left + 15);
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    return $html;
}
/**
 * 返回解析空间地址
 * @param integer $uid 用户ID
 * @param string $class 样式类
 * @param string $target 是否进行跳转
 * @param string $text 标签内的相关内容
 * @param boolen $icon 是否显示用户组图标，默认为true
 * @return string 解析空间地址HTML
 */
function getUserSpace($uid, $class, $target, $text, $icon = true)
{
    // 2.8转移
    // 静态变量
    static $_userinfo = array();
    // 判断是否有缓存
    if(!isset($_userinfo[$uid])) {
        $_userinfo[$uid] = model('User')->getUserInfo($uid);
    }
    // 配置相关参数
    empty($target) && $target = '_self';
    empty($text) && $text = $_userinfo[$uid]['uname'];
    // 判断是否存在替换信息
    preg_match('|{(.*?)}|isU', $text, $match);
    if($match) {
        if($match[1] == 'uname') {
            $text = str_replace('{uname}', $_userinfo[$uid]['uname'], $text);
            empty($class) && $class = 'username';
        } else {
            preg_match("/{uavatar}|{uavatar\\=(.*?)}/e", $text, $face_type);
            $face = isset($face_type[1]) ? $_userinfo[$uid]['avatar_'.$face_type[1]] : $_userinfo[$uid]['avatar_small'];
            $text = '<img src="'.$face.'" />';
            empty($class) && $class = 'userface';
            $icon = false;
        }
    }
    // 组装返回信息
    $user_space_info = '<a event-node="face_card" uid="'.$uid.'" href="'.$_userinfo[$uid]['space_url'].'" class="'.$class.'" target="'.$target.'">'.$text.'</a>';
    // 用户认证图标信息
    if($icon) {
        $group_icon = array();
        $user_group = static_cache( 'usergrouplink_'.$uid );
        if ( !$user_group ){
        	$user_group = model('UserGroupLink')->getUserGroupData($uid);
        	static_cache( 'usergrouplink_'.$uid , $user_group );
        }
        if(!empty($user_group)) {
            foreach($user_group[$uid] as $value) {
                $group_icon[] = '<img title="'.$value['user_group_name'].'" src="'.$value['user_group_icon_url'].'" class="space-group-icon" />';
            }
            $user_space_info .= '&nbsp;'.implode('&nbsp;', $group_icon);
        }
    }

    return $user_space_info;
}