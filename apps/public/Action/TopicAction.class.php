<?php 
class TopicAction extends Action{

	// 专题页
    public function index()
    {
        //echo $_GET['domain'];exit;
        if($_GET['domain']){
            $map['domain'] = t($_GET['domain']);
            //echo $domain;exit;
            $data['search_key'] = model('FeedTopic')->where($map)->getField('topic_name');
        }else{
            $data['search_key'] = $this->__getSearchKey();
        }
        // 专题信息
        if (false == $data['topics'] = model('FeedTopic')->getTopic($data['search_key'])) {          
            if (null == $data['search_key']) {
                $this->error('此话题不存在');
            }
            $data['topics']['name'] = t($data['search_key']);
        }
        if($data['topics']['lock']==1){
            $this->error('该话题已被屏蔽');
        }
        if($data['topics']['pic']){
            $pic = D('attach')->where('attach_id='.$data['topics']['pic'])->find();
            //$data['topics']['pic'] = UPLOAD_URL.'/'.$pic['save_path'].$pic['save_name'];
            $pic_url = $pic['save_path'].$pic['save_name'];
            $data['topics']['pic'] = getImageUrl($pic_url); 
        }
        $data['topic'] = $data['search_key'] ? $data['search_key'] : html_entity_decode($data['topics']['name'], ENT_QUOTES);
        $data['topic_id'] = $data['topics']['topic_id'] ? $data['topics']['topic_id'] : model('FeedTopic')->getTopicId($data['search_key']);
        $initHtml = '#'.$data['search_key'].'#';
        $this->assign('initHtml' , $initHtml);
        $this->assign ( $data );
        $this->display();
    }

    private function __getSearchKey()
    {
        $key = '';
        // 为使搜索条件在分页时也有效，将搜索条件记录到SESSION中
        if(isset($_REQUEST ['k']) && !empty($_REQUEST['k'])) {
            if(t($_GET ['k'])) {
                $key = html_entity_decode(urldecode($_GET['k']), ENT_QUOTES);
            } else if(t($_POST['k'])) {
                $key = t($_POST['k']);
            }
            // 关键字不能超过200个字符
            if (mb_strlen ( $key, 'UTF8' ) > 200)
                $key = mb_substr ( $key, 0, 200, 'UTF8' );
            $_SESSION ['home_user_search_key'] = serialize ( $key );

        } else if (is_numeric ( $_GET [C ( 'VAR_PAGE' )] )) {
            $key = unserialize ( $_SESSION ['home_user_search_key'] );

        } else {
            //unset($_SESSION['home_user_search_key']);
        }
		$key = str_replace(array('%','\'','"','<','>'),'',$key);
        return trim ( $key );
    }
	
}