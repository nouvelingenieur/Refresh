Ext.setup({
	onReady: function() {
	
		new Ext.Application({
			launch: function() {
				new Ext.Panel({
					fullscreen: true,
					html: 'Hello World!'
				});
			}
		});
		
		
		var makeJSONPRequest = function() {
			Ext.getBody().mask('Loading...', 'x-mask-loading', false);
			Ext.util.JSONP.request({
				url: 'http://refresh.nouvelingenieur.fr/api/info.php',
				callbackKey: 'callback',
				params: {                    
					key: '23f6a0ab24185952101705'
					},
				callback: function(result) {
					alert(result.data.LANG);
					/*var weather = result.data.weather;
					if (weather) {
						//var html = tpl.applyTemplate(weather);
						//Ext.getCmp('content').update(html);                        
					}
					else {
						alert('There was an error retrieving the weather.');
					}
					Ext.getCmp('status').setTitle('Palo Alto, CA Weather');
					Ext.getBody().unmask();*/
				}
			});
		};
		
		new Ext.Panel({
			fullscreen: true,
			id: 'content',
			scroll: 'vertical',
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: [{
					text: 'JSONP',
					handler: makeJSONPRequest
				}]
			},{
				id: 'status',
				xtype: 'toolbar',
				dock: 'bottom',
				title: "Tap a button above."
			}]
			});
	}
});
