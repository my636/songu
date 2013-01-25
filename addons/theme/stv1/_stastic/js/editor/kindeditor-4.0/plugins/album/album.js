/*******************************************************************************
* KindEditor - WYSIWYG HTML Editor for Internet
* Copyright (C) 2006-2011 kindsoft.net
*
* @author Roddy <luolonghao@gmail.com>
* @site http://www.kindsoft.net/
* @licence http://www.kindsoft.net/license.php
*******************************************************************************/

KindEditor.plugin('album', function(K) {
	var self = this, name = 'album', undefined;
	self.clickToolbar(name, function() {
		var lang = self.lang(name + '.'),
			html = '<div>' +
				'<iframe class="ke-textarea" name="aiframe" id="aiframe" frameborder="0" src="'+self.pluginsPath+'album/album.html" style="width:690px;height:440px;"></iframe>' +
				'</div>',
			dialog = self.createDialog({
				name : name,
				width : 700,
				title : self.lang(name),
				body : html,
				yesBtn : {
					name : self.lang('yes'),
					click : function(e) {
						var selectId = document.getElementById('aiframe').contentWindow.document.getElementById("selectId").value
						var imgSrc	;
						var attachid
						var html = '';
						selectIdArr	=	selectId.split(',');
						$.each(selectIdArr,function(i,n){
							if(n>0){
								attachid	=	document.getElementById('aiframe').contentWindow.document.getElementById('select_img_'+n).attributes.getNamedItem('attachid').value;
								html += '[attach photoid="'+n+'" attachid="'+attachid+'"][/attach]';
							}
						});
						if( html.length > 1 ){
							self.insertHtml( html );
						}
						self.hideDialog().focus();
					}
				}
			})
	});
});
