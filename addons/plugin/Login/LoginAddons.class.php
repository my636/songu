<?php
class LoginAddons extends NormalAddons{
	protected $version = "2.0";
	protected $author  = "智士软件";
	protected $site    = "t.thinksns.com";
	protected $info    = "使用新浪微博V2的API";
    protected $pluginName = "微博同步V2";
    protected $tsVersion = '2.5';

    public function getHooksInfo()
    {
        $hooks['list']=array('LoginHooks');
        return $hooks;
    }

	/**
	 * 该插件的管理界面的处理逻辑。
	 * 如果return false,则该插件没有管理界面。
	 * 这个接口的主要作用是，该插件在管理界面时的初始化处理
	 * @param string $page
	 */
    public function adminMenu()
    {
	    return array('login_plugin_login'=>"同步登录管理",'login_plugin_publish'=>'同步发布管理');
    }

    public function start()
    {
        return true;
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }
}
