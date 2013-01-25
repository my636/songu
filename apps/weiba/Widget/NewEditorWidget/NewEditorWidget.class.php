<?php
/**
  * 微吧帖子编辑器
  * @example W('Comment',array('tpl'=>'detail','row_id'=>72,'order'=>'DESC','app_uid'=>'14983','cancomment'=>1,'cancomment_old'=>0,'showlist'=>1,'canrepost'=>1))                                  
  * @author jason <yangjs17@yeah.net> 
  * @version TS3.0
  */
class NewEditorWidget extends Widget{
	
	private static $rand = 1;

    /**
     * @param 
     */
	public function render($data){
		$var = array();    
    $var['contentName'] = 'content';  
    !empty($data) && $var = array_merge($var,$data);
    $content = $this->renderFile(dirname(__FILE__).'/default.html',$var);
    unset($var,$data);
    // 输出数据
    return $content;
  }

}