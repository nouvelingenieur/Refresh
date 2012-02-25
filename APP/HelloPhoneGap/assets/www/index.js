/*Ext.setup({
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
					page: '0'
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
					Ext.getBody().unmask();
				}
			});
		};
		
		/*new Ext.Panel({
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
});*/

Ext.setup({
	icon: 'icon.png',
	tabletStartupScreen: 'tablet_startup.png',
	phoneStartupScreen: 'phone_startup.png',
	glossOnIcon: false,
	onReady: function() {
		var search_items = [{
					xtype: 'searchfield',
					name : 'q',
					 //label: 'Search',
					placeHolder: ' Search ideas'
				},
				{
					xtype: 'selectfield',
					name: 'Category',
					options: [
						{text: 'Any category',  value: '-1'},
						{text: 'Category 1', value: '1'}
					]
				},
				{
					text: 'Search',
					ui: 'round',
					handler: function() {
							q = 'test';
							//console.log(viewport.dockedItems.items[0].items.items[0].fieldEl.dom.value);
							Ext.util.JSONP.request({
								url: 'http://refresh.nouvelingenieur.fr/api/ideas.php',
								callbackKey: 'callback',
								params: {
									q: viewport.dockedItems.items[0].items.items[0].fieldEl.dom.value,
									n: '10'
								},
								callback: function(result) {
									//console.log(result);
									//groupingBase.store.add([{ideaName: 'id_combo1'}]);
									console.log(result.data);
									for(i=0;i<result.data.length ;i++){
										groupingBase.store.add([{ideaName: result.data[i].IDEA_TITLE}]);
									}
									
									
								}
							});
					}
				}]
		var viewport = new Ext.Panel({
			fullscreen: true,
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: search_items}]
		})
		
		Ext.regModel('Idea', {
            fields: ['ideaName']
        });

        var groupingBase = {
            itemTpl: '<div class="ideas"><strong>{ideaName}</strong></div>',
            selModel: {
                mode: 'SINGLE',
                allowDeselect: true
            },
            grouped: true,
            indexBar: false,

            onItemDisclosure: {
                scope: 'test',
                handler: function(record, btn, index) {
                    alert('Disclose more info for ' + record.get('idea'));
                }
            },

            store: new Ext.data.Store({
                model: 'Idea',
                sorters: 'ideaName',

                getGroupString : function(record) {
                    return record.get('ideaName')[0];
                },

                data: [
                ]
                
                
            })
        };

            new Ext.List(Ext.apply(groupingBase, {
                fullscreen: true
            }));

	}
});
