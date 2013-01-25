/**
 * 频道操作核心Js对象
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
var channel = {};
/**
 * 频道Js配置参数
 */
channel.setting = {
	loadCount: 0,		// 加载次数
	canLoading: true,	// 是否能加载
	loadLimit: 10, 		// 加载数目
	loadId: 1
};
/**
 * 页面底部加载数据
 * @return void
 */
channel.bindScroll = function()
{
	var _this = this;
	$(window).bind('scroll resize', function() {
		// 加载3次后，将不能自动加载微博
		if(_this.setting.loadCount >= 3 || _this.setting.canLoading == false) {
			return false;
		}
		var bodyTop = document.documentElement.scrollTop + document.body.scrollTop;
		var bodyHeight = $(document.body).height();
		if(bodyTop + $(window).height() > bodyHeight - 250) {
			_this.setting.loadCount += 1;
			if($('#container').length > 0) {
				$('#container').after("<div class='loading' id='loadMore'>" + L('PUBLIC_LOADING') + "<img src='" + THEME_URL + "/image/load.gif' class='load'></div>");
				channel.loadMoreData();
			}
		}
	});
};
/**
 * 加载更多频道数据
 * @return void
 */
channel.loadMoreData = function()
{
	var _this = this;
	_this.setting.canLoading = false;
	// 获取相关微博数据
	$.post(U('channel/Index/loadMoreData'), {loadId:_this.setting.loadId, limit:_this.setting.loadLimit, loadCount:_this.setting.loadCount,cid:_this.setting.cid}, function(res) {
		// 加载失败
		if(res.status == 0 || res.status == -1) {
			$('#loadMore').remove();
		} else {
			channel.setDiv(res.html, false);
			_this.setting.canLoading = true;
			_this.setting.loadId = res.loadId;
			if(_this.setting.loadCount >= 3) {
				$('#container').after('<div id="page" class="page" style="display:none;">' + res.pageHtml + '</div>');
				if($('#page').find('a').size() > 2) {
					var href = false;
					$('#page').find('a').each(function() {
						href = $(this).attr('href');
					});
					// 重组分页结构
					$('#page').html(res.pageHtml).show();
					$('#page').find('a').each(function() {
						var href = $(this).attr('href');
						if(href) {
							$(this).attr('href', 'javascript:;');
							$(this).click(function() {
								$('.boxy-modal-blackout-channel').remove();
								channel.loadMoreByPage(href+'&cid='+_this.setting.cid);
							});
						}
					});
				}
				// channel.completion();
			} else {
				channel.bindScroll();
			}
		}
	}, 'json');
	return false;
};
/**
 * 分页加载更多数据
 * @return void
 */
channel.loadMoreByPage = function(href)
{
	var _this = this;
	_this.canLoading = false;
	$('#container').html("<div class='loading' id='loadMore'>"+L('PUBLIC_LOADING')+"<img src='"+THEME_URL+"/image/load.gif' class='load'></div>");
	scrolltotop.scrollup();
	$.post(href, {}, function(res) {
		if(res.status == 0 || res.status == -1) {
			$('#container').after("<div class='load' id='loadMore'>" + L('PUBLIC_ISNULL') + "</div>");
		} else {
			channel.setDiv(res.html, true);
			$('#page').html(res.pageHtml);
			$('#page').find('a').each(function() {
				var href = $(this).attr('href');
				if(href) {
					$(this).attr('href', 'javascript:;');
					$(this).click(function() {
						$('.boxy-modal-blackout-channel').remove();
						channel.loadMoreByPage(href);
					});
				}
			});
			// channel.completion();
		}
	}, 'json');
	return false;
};
/**
 * 动态插入数据
 * @param string html 新加入数据
 * @return void
 */
channel.setDiv = function(html, page)
{
	if(page) {
		$('#container').html(html).masonry('reload');
	} else {
		var domDiv = $('<div></div>').append(html);
		var box = [];
		if(this.setting.loadCount >= 4) {
			domDiv.find('div').filter('.box').slice(30).each(function() {
				box.push(this);
			});
		} else {
			domDiv.find('div').filter('.box').each(function() {
				box.push(this);
			});
		}
		$boxes = $(box);
		$('#container').append($boxes).masonry('appended', $boxes);
	}
	$('#loadMore').remove();
	M(document.getElementById('container'))
};
/**
 * 页面补全，动态补充
 * @return void
 */
channel.completion = function()
{
	var cols = 4;
	var allDivs = [];
	var lastDivs = [];
	var divStyle = [];
	$('#container').find('div').each(function() {
		if($(this).hasClass('box')) {
			allDivs.push(this);
		}
	});
	lastDivs = allDivs.slice(-cols);
	for(var i in lastDivs) {
		$lDiv = $(lastDivs[i]);
		var arr = [];
		arr.push(parseInt($lDiv.css('top')) + parseInt($lDiv.height()) + 50);
		arr.push(parseInt($lDiv.css('left')));
		divStyle.push(arr);
	}
	var divHeight = [];
	for(var i in divStyle) {
		divHeight.push(divStyle[i][0]);
	}
	var max = Math.max.apply(null, divHeight);
	for(var i in divHeight) {
		if(divHeight[i] != max) {
			var top = divStyle[i][0];
			var left = divStyle[i][1];
			var height = max - top - 20;
			$('#container').append('<div class="channel-blank" style="position:absolute;top:'+top+'px;left:'+left+'px;width:225px;height:'+height+'px;"></div>');
		}
	}
};
/**
 * 遮盖功能
 * @param integer feedId 微博ID
 * @return void
 */
channel.coverBlack = function(feedId)
{
	var $feedDiv = $('#feed_'+feedId);
	var $div = $('<div></div>');
	$div.addClass('boxy-modal-blackout-channel');
	var cssStyle = {};
	cssStyle.position = 'absolute';
	cssStyle.top = $feedDiv.offset().top;
	cssStyle.left = $feedDiv.offset().left;
	cssStyle.width = $feedDiv.outerWidth();
	cssStyle.height = $feedDiv.outerHeight() + 1;
	$div.css(cssStyle);
	$('body').append($div);
};
// 事件绑定
M.addEventFns({
	/**
	 * 管理弹窗显示 - 显示与隐藏微博操作弹窗
	 * @type {Object}
	 */
	show_admin: {
		load: function() {
			var args = M.getEventArgs(this);
			if(args.feed_del == 1 || args.channel_recommend == 1) {
				$(this).css('display', 'block');
			}
		},
		click: function() {
			var _this = this;
			// 删除管理弹窗
			$('#weibo_admin_box').remove();
			// 获取相关参数
			var args = M.getEventArgs(this);
			// 添加hover样式类
			$(M.getEvents('show_admin')).addClass('hover');
			// 移除当前的hover样式类
			$(this).removeClass('hover');
			// 定位
			var offset = $(this).offset();
			var html = '<div id="weibo_admin_box" class="layer-list" style="display:none;z-index:1;"><ul>';
			if(args.channel_recommend == 1) {
				html += '<li><a href="javascript:;" onclick="getAdminBox('+args.feed_id+');">推荐到频道</a></li>';
			}
			html += '</ul></div>';
			$('body').append(html);
			$('#weibo_admin_box').css({position:'absolute', top:offset.top + 20, left:offset.left - 50});
			$('#weibo_admin_box').show();
			$('body').bind('click', function(event) {
				if($(event.target).attr('event-node') != 'show_admin') {
					$('#weibo_admin_box').remove();
					$(_this).addClass('hover');
				}
			});
			M(document.getElementById('weibo_admin_box'));
		}
	},
	/**
	 * 重写删除微博
	 * @type {Object}
	 */
	delFeed: {
		click: function() {
			var args = M.getEventArgs(this);
			// 删除微博
			var delFeed = function() {
				$.post(U('public/Feed/removeFeed'), {feed_id:args.feed_id}, function(res) {
					if(res.status == 1) {
						channel.coverBlack(args.feed_id);
					} else {
						ui.error(L('PUBLIC_DELETE_ERROR'));
						return false;
					}
				}, 'json');
				return false;
			};
			// 提示框
			ui.confirm(this, L('PUBLIC_DELETE_THISNEWS'), delFeed);
		}
	}
});
/**
 * 管理弹窗显示
 * @param integer feedId 微博ID
 * @param integer channelId 频道分类ID
 * @return void
 */
var getAdminBox = function(feedId, channelId)
{
	ui.box.load(U('channel/Manage/getAdminBox')+'&feed_id='+feedId+'&channel_id='+channelId, '推荐到频道');
};