<feed app='public' type='repost' info='转发微博'>
	<title> 
		<![CDATA[{$actor}]]>
	</title>
	<body>
		<![CDATA[
		<eq name='body' value=''> 微博分享 </eq> 
		{$body|t|replaceUrl}
		<dl class="comment">
			<dt class="arrow bgcolor_arrow"><em class="arrline">◆</em><span class="downline">◆</span></dt>
			<php>if($sourceInfo['is_del'] == 0):</php>
			<dd class="name">
				@{$sourceInfo.source_user_info.uname}
				<volist name="sourceInfo['groupData'][$sourceInfo['source_user_info']['uid']]" id="v2">
					<img style="width:auto;height:auto;display:inline;cursor:pointer" src="{$v2['user_group_icon_url']}" title="{$v2['user_group_name']}" /> 
				</volist>
			</dd>
			<dd>
				{* 转发原文 *}
				{$sourceInfo.source_content|t|replaceUrl}
				<php>if(!empty($sourceInfo['attach'])):</php>
				{* 附件微博 *}
				<eq name='sourceInfo.feedType' value='postfile'>
				<ul class="feed_file_list">
					<volist name='sourceInfo.attach' id='vo'>
					<li>
						<a href="{:U('widget/Upload/down',array('attach_id'=>$vo['attach_id']))}" class="current right" target="_blank"><i class="ico-down"></i></a>
						<i class="ico-{$vo.extension}-small"></i>
						<a href="{:U('widget/Upload/down',array('attach_id'=>$vo['attach_id']))}">{$vo.attach_name}</a>
						<span class="tips">({$vo.size|byte_format})</span>
					</li>
					</volist>			
				</ul>		
				</eq>
				{* 图片微博 *}
				<eq name='sourceInfo.feedType' value='postimage'>
				<div class="feed_img_lists" rel='small' >
					<ul class="small">
						<volist name='sourceInfo.attach' id='vo'>
						<li><a href="javascript:void(0)" event-node='img_small'><img class="imgicon" src='{$vo.attach_small}' title='点击放大' width="100" height="100"></a></li>
						</volist>
					</ul>
				</div>
				<div class="feed_img_lists" rel='big' style='display:none'>
					<ul class="feed_img_list big">
						<p class='tools'><a href="javascript:void(0);" event-node='img_big' class="ico-pack-up">收起</a></p>
						<volist name='sourceInfo.attach' id='vo'>
						<li title='{$vo.attach_url}'>
							<i class="check-big"><a href='{$vo.attach_url}' target="_blank" class="ico-show-big" title="查看大图" ></a></i>
							<a href="javascript:void(0)" event-node='img_big'><img class="imgsmall" src='{$vo.attach_middle}' title='点击缩小' /></a>
						</li>
						</volist>
					</ul>
				</div>
				</eq>	
				<php>endif;</php>	
			</dd>
			<p class="info">
				<span class="right">
					<a href="{:U('public/Profile/feed',array('uid'=>$sourceInfo['uid'],'feed_id'=>$sourceInfo['feed_id']))}">原文转发<neq name="sourceInfo.repost_count" value="0">({$sourceInfo.repost_count})</neq></a><i class="vline">|</i>
					<a href="{:U('public/Profile/feed',array('uid'=>$sourceInfo['uid'],'feed_id'=>$sourceInfo['feed_id']))}">原文评论<neq name="sourceInfo.comment_count" value="0">({$sourceInfo.comment_count})</neq></a>
				</span>
				<span><a href="{:U('public/Profile/feed',array('uid'=>$sourceInfo['uid'],'feed_id'=>$sourceInfo['feed_id']))}" class="date">{$sourceInfo['ctime']|friendlyDate}</a><span>来自网站</span></span>
			</p>
			<php>else:</php>
			<dd class="name">内容已被删除</dd>
			<php>endif;</php>
		</dl>
		]]>
	</body>
	<feedAttr comment="true" repost="true" like="false" favor="true" delete="true" />
</feed>