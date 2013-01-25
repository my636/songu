<?php
//TODO 授权刷新 - 同步动态
// require_once 'renren/HttpRequestService.class.php';
// require_once 'renren/RenrenRestApiService.class.php';
class renren{

	var $loginUrl;

	function getUrl($redirect_uri){
		if(!$redirect_uri)
			$redirect_uri = Addons::createAddonShow('Login','no_register_display',array('type'=>'renren','do'=>"bind"));
		$this->loginUrl = 'https://graph.renren.com/oauth/authorize?'.'client_id='.RENREN_KEY.'&redirect_uri='.urlencode($redirect_uri).'&response_type=code&scope=publish_feed';
		return $this->loginUrl;
	}

	//用户资料
	function userInfo(){
		if($_SESSION['renren']['uid']){
			$user['id']         = $_SESSION['renren']['uid'];
			$user['uname']      = $_SESSION['renren']['uname'];
			$user['province']   = 0;
			$user['city']       = 0;
			$user['location']   = '';
			$user['userface']   = $_SESSION['renren']['userface'];
			$user['sex']        = ($_SESSION['renren']['sex']=='1')?1:0;
			return $user;
		}else{
			//用接口获取数据
			return false;
		}
	}

	//验证用户
	function checkUser(){
		if($_GET['code']){
			$redirect_uri = Addons::createAddonShow('Login','no_register_display',array('type'=>'renren','do'=>"bind"));
			$url = 'https://graph.renren.com/oauth/token?grant_type=authorization_code&client_id='.RENREN_KEY.'&code='.$_GET['code'].'&client_secret='.RENREN_SECRET.'&redirect_uri='.urlencode($redirect_uri);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($ch);
			$res = json_decode($result,TRUE);
			if($res['user']){
				$_SESSION['renren']['access_token']['oauth_token'] = $res['access_token'];
				$_SESSION['renren']['access_token']['oauth_token_secret'] = $res['refresh_token'];
				$_SESSION['renren']['isSync'] = 1;
				$_SESSION['renren']['uid'] = $res['user']['id'];
				$_SESSION['renren']['uname'] = $res['user']['name'];
				$_SESSION['renren']['userface'] = $res['user']['avatar'][2]['url']?$res['user']['avatar'][2]['url']:'';
				$_SESSION['open_platform_type'] = 'renren';
				return $res;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	//发布一条微博
	public function update($text,$opt){
		return true;
	}

	//上传一个照片，并发布一条微博
	public function upload($text,$opt,$pic){
		return true;
	}

	//转发一条微博
    public function transpond($transpondId,$reId,$content='',$opt=null){
		return true;
	}

	//保存数据
	public function saveData($data){
		return true;
	}
}