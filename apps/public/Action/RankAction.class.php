<?php
/**
 * 排行榜
 * @author Stream
 *
 */
class RankAction extends Action{
	public function index(){
		$type = $_GET['type'] ? $_GET['type'] : 1;

		//1为好友排行
		if ( $type == 1 ){
			$followGroupList = model('Follow')->getFriendsData($this->mid);
			$fids = getSubByKey($followGroupList, 'fid');
			$fids[] = $this->mid;
		}
		$ranList = $this->_getRankList( $type , $fids);
		$list = $this->_parseRankList( $ranList );
		$typename = $type == 1 ? '好友':'全站';
		$this->assign('typename' , $typename);
		$this->assign( 'mid' , $this->mid );
		$this->assign( 'type' , $type );
		$this->assign( $list );
		$this->display();
	}
	/**
	 * 获取排名
	 * @param unknown_type $type
	 * @param unknown_type $fids
	 */
	public function _getRankList( $type , $fids ){
		$userDataDao = model( 'UserData' );
		$creditUserDao = D('credit_user');
		$userCheckDao = D('check_info');
		$ranklistkey = 'user_rank_list_'.$type;
		if ( $type == 1 ){
			$followermap['uid'] = array('in' , $fids);
				
			$experiencemap['uid'] = array('in' , $fids);
				
			$goldmap['uid'] = array('in' , $fids);
				
			$medalmap['uid'] = array('in' , $fids);
				
			$checkmap['uid'] = array('in' , $fids);
				
			$checktmap['uid'] = array('in' , $fids);
			
			$ranklistkey = 'user_rank_list_'.$type.'_'.$this->mid;
		}
		$res = model( 'Cache' )->get( $ranklistkey );
		if ( !$res ){
			/****全站排行*****/
			//粉丝排行
			$followermap['key'] = 'follower_count';
			$followeruids = $userDataDao->where($followermap)->field('uid,`value`')->order('`value`+0 desc,uid')->limit(100)->findAll();
			$ifollower = 0;
			foreach ( $followeruids as &$fu ){
				$ifollower++;
				$fu['rank'] = $ifollower;
				$fu['uid'] = model( 'User' )->getUserInfo( $fu['uid'] );
				$fu['val'] = $fu['value'];
			}
			//经验排行
			$experienceuids = $creditUserDao->where($experiencemap)->field('uid,`experience`')->order('`experience` desc,uid')->limit(100)->findAll();
			$iexperience = 0;
			foreach ( $experienceuids as &$eu ){
				$iexperience++;
				$eu['rank'] = $iexperience;
				$eu['uid'] = model( 'User' )->getUserInfo( $eu['uid'] );
				$eu['val'] = $eu['experience'];
			}
			
			//财富排行
			$golduids = $creditUserDao->where($goldmap)->field('uid,`gold`')->order('`gold` desc,uid')->limit(100)->findAll();
			$igold = 0;
			foreach ( $golduids as &$gu ){
				$igold++;
				$gu['rank'] = $igold;
				$gu['uid'] = model( 'User' )->getUserInfo( $gu['uid'] );
				$gu['val'] = $gu['gold'];
			}
			
			//勋章排行
			$medaluids = D('medal_user')->where($medalmap)->field('uid,count(medal_id) as mcount')->group('uid')->order('mcount desc,uid')->limit(100)->findAll();
			$imedal = 0;
			foreach ( $medaluids as &$mu ){
				$imedal++;
				$mu['rank'] = $imedal;
				$mu['uid'] = model( 'User' )->getUserInfo( $mu['uid'] );
				$mu['val'] = $mu['mcount'];
			}
			
			//连续签到排行
			$checkmap['key'] = 'check_connum';
			$checkconuids = $userDataDao->where($checkmap)->field('uid,`value`')->order('`value`+0 desc,uid')->limit(100)->findAll();
			$icheckcon = 0;
			foreach ( $checkconuids as &$ccu ){
				$icheckcon++;
				$ccu['rank'] = $icheckcon ;
				$ccu['uid'] = model( 'User' )->getUserInfo( $ccu['uid'] );
				$ccu['val'] = $ccu['value'];
			}
	
			//累计签到排行
			$checktmap['key'] = 'check_totalnum';
			$checktotaluids = $userDataDao->where($checktmap)->field('uid,`value`')->order('`value`+0 desc,uid')->limit(100)->findAll();
			$ichecktotal = 0;
			foreach ( $checktotaluids as &$ctu ){
				$ichecktotal++;
				$ctu['rank'] = $ichecktotal;
				$ctu['uid'] = model( 'User' )->getUserInfo( $ctu['uid'] );
				$ctu['val'] = $ctu['value'];
			}
			
			$res = array(
					'followeruids' => $followeruids ,
					'experienceuids' => $experienceuids,
					'golduids' => $golduids,
					'medaluids' => $medaluids,
					'checkconuids' => $checkconuids,
					'checktotaluids' => $checktotaluids);
			model( 'Cache' )->set( $ranklistkey , $res , 600 );
		}
		
		
		$rankres = model( 'Cache' )->get('user_rank_'.$type.'_'.$this->mid);
		if ( !$rankres ){
			$userData = $userDataDao->where('uid='.$this->mid)->findAll();
			$userKeyData = array();
			foreach ( $userData as $u){
				$userKeyData[$u['key']] = $u['value'];
			}
			$userCredit = $creditUserDao->where('uid='.$this->mid)->field('`experience`,`gold`')->find();
			$userMedalCount = D('medal_user')->where('uid='.$this->mid)->getField('count(medal_id) as mcount');
			$userCheckCon = $userDataDao->where("`key`='check_connum' and uid=".$this->mid)->getField('`value`');
			$userCheckTotal = $userDataDao->where("`key`='check_totalnum' and uid=".$this->mid)->getField('`value`');
			
			$userCheckCon = intval( $userCheckCon );
			$userCheckTotal = intval( $userCheckTotal );
			
			$followermap['_string'] = " `value`+0>".intval( $userKeyData['follower_count'] );
			$followerrank = $userDataDao->where($followermap)->count();
			$followerrank += 1;
			
			$experiencemap['experience']  = array( 'gt' , $userCredit['experience'] );
			$experiencerank = $creditUserDao->where($experiencemap)->count();
			$experiencerank += 1;
			
			$goldmap['gold'] = array( 'gt' , $userCredit['gold'] );
			$goldrank = $creditUserDao->where($goldmap)->count();
			$goldrank += 1;
			
			$checktmap['_string'] = '`value`+0>'.$userCheckTotal;
			$checktotalrank = $userDataDao->where($checktmap)->count();
			$checktotalrank += 1;
			
			$medalrank = D('medal_user')->where($medalmap)->field('uid,count(medal_id) as mcount')->group('uid having mcount>'.$userMedalCount)->findAll();
			$medalrank = $medalrank ? count ( getSubByKey( $medalrank , 'uid' ) ) : 0;
			$medalrank += 1;
			
			$checkmap['_string'] = '`value`+0>'. $userCheckCon;
			$checkconrank = $userDataDao->where($checkmap)->count();
			$checkconrank += 1;
			
			$rankres = array( 
					'followerrank' => $followerrank,
					'experiencerank' => $experiencerank,
					'goldrank' => $goldrank,
					'medalrank' => $medalrank,
					'checkconrank' => $checkconrank,
					'checktotalrank' => $checktotalrank);
			model( 'Cache' )->set( 'user_rank_'.$type.'_'.$this->mid , $rankres , 600 );
		}
		
		$reslist = array_merge( $res , $rankres );
		
		return $reslist;
		
	}
	
	public function _parseRankList( $list ){
		$resList = array();
		/*******粉丝*********/
		$follower['userrank'] = $list['followerrank'];
		$followlist = array();
		foreach ( $list['followeruids'] as $k=>$v){
			if ( $k < 10 ){
				$followlist[1][] = $v;
			} else {
				//舍去法取整
				$fnum = floor ( $k / 10 );
				$followlist[$fnum+1][] = $v;
			}
		}
		//进一法取整
		$follower['ranknum'] = ceil ( count( $list['followeruids'] ) / 10 );
		$follower['list'] = $followlist;
		$resList['follower'] = $follower;
		/*******经验*********/
		$experience['userrank'] = $list['experiencerank'];
		$experiencelist = array();
		foreach ( $list['experienceuids'] as $ek=>$ev){
			if ( $ek < 10 ){
				$experiencelist[1][] = $ev;
			} else {
				//舍去法取整
				$fnum = floor ( $ek / 10 );
				$experiencelist[$fnum+1][] = $ev;
			}
		}
		//进一法取整
		$experience['ranknum'] = ceil ( count( $list['experienceuids'] ) / 10 );
		$experience['list'] = $experiencelist;
		
		$resList['experience'] = $experience;
		
		/*******金币*********/
		$gold['userrank'] = $list['goldrank'];
		$goldlist = array();
		foreach ( $list['golduids'] as $gk=>$gv){
			if ( $gk < 10 ){
				$goldlist[1][] = $gv;
			} else {
				//舍去法取整
				$fnum = floor ( $gk / 10 );
				$goldlist[$fnum+1][] = $gv;
			}
		}
		//进一法取整
		$gold['ranknum'] = ceil ( count( $list['golduids'] ) / 10 );
		$gold['list'] = $goldlist;
		
		$resList['gold'] = $gold;
		
		/******勋章*****/
		$medal['userrank'] = $list['medalrank'];
		$medallist = array();
		foreach ( $list['medaluids'] as $mk=>$mv){
			if ( $mk < 10 ){
				$medallist[1][] = $mv;
			} else {
				//舍去法取整
				$fnum = floor ( $mk / 10 );
				$medallist[$fnum+1][] = $mv;
			}
		}
		//进一法取整
		$medal['ranknum'] = ceil ( count( $list['medaluids'] ) / 10 );
		$medal['list'] = $medallist;
		
		$resList['medal'] = $medal;
		
		/******连续签到*****/
		$checkcon['userrank'] = $list['checkconrank'];
		$checkconlist = array();
		foreach ( $list['checkconuids'] as $cck=>$ccv){
			if ( $cck < 10 ){
				$checkconlist[1][] = $ccv;
			} else {
				//舍去法取整
				$fnum = floor ( $cck / 10 );
				$checkconlist[$fnum+1][] = $ccv;
			}
		}
		//进一法取整
		$checkcon['ranknum'] = ceil ( count( $list['checkconuids'] ) / 10 );
		$checkcon['list'] = $checkconlist;
		
		$resList['checkcon'] = $checkcon;
		
		/******累计签到*****/
		$checktotal['userrank'] = $list['checktotalrank'];
		$checktotallist = array();
		foreach ( $list['checktotaluids'] as $ctk=>$ctv){
			if ( $ctk < 10 ){
				$checktotallist[1][] = $ctv;
			} else {
				//舍去法取整
				$fnum = floor ( $ctk / 10 );
				$checktotallist[$fnum+1][] = $ctv;
			}
		}
		//进一法取整
		$checktotal['ranknum'] = ceil ( count( $list['checktotaluids'] ) / 10 );
		$checktotal['list'] = $checktotallist;
		
		$resList['checktotal'] = $checktotal;
		
		return $resList;
	}
	/**
	 * 发布排行榜到我的微博
	 */
	public function postRank(){
		$type = $_POST['type'] ? intval ( $_POST['type'] ) : 1;
		$ranList = model( 'Cache' )->get( 'ranklist_'.$this->mid.'_'.$type );
		if ( !$ranList ){
			//1为好友排行
			if ( $type == 1 ){
				$followGroupList = model('Follow')->getFriendsData($this->mid);
				$fids = getSubByKey($followGroupList, 'fid');
				$fids[] = $this->mid;
			}
			$ranList = $this->_getRankList( $type , $fids);
		}
		//我的好友排行榜
		if ( $type == 1 ){
			$str = '我的好友排行榜：我在好友粉丝排行榜中排名第'
					.$ranList['followerrank'].'；在好友经验排行榜中排名第'
					.$ranList['experiencerank'].'；在好友财富排行榜中排名第'
					.$ranList['goldrank'].'；在好友勋章排行榜中排名第'
					.$ranList['medalrank'].'；在好友连续签到排行榜中排名第'
					.$ranList['checkconrank'].'；在好友累计签到排行榜中排名第'
					.$ranList['checktotalrank'].'；快来查看你的排名吧。'.U('public/Rank/index','type=1');
		} else {
			$str = '我的全站排行榜：我在全站粉丝排行榜中排名第'
					.$ranList['followerrank'].'；在全站经验排行榜中排名第'
					.$ranList['experiencerank'].'；在全站财富排行榜中排名第'
					.$ranList['goldrank'].'；在全站勋章排行榜中排名第'
					.$ranList['medalrank'].'；在全站连续签到排行榜中排名第'
					.$ranList['checkconrank'].'；在全站累计签到排行榜中排名第'
					.$ranList['checktotalrank'].'；快来查看你的排名吧。'.U('public/Rank/index','type=2');
		}
		$data['body'] = $str;
		model( 'Feed' )->put( $this->mid , 'public' , 'post' , $data );
		echo 1;
	}
}
?>