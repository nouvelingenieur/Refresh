
Ext.setup({
	icon: 'icon.png',
	tabletStartupScreen: 'tablet_startup.png',
	phoneStartupScreen: 'phone_startup.png',
	glossOnIcon: false,
	onReady: function() {
		var searchedString = '';
		
		// top toolbar
		var search_items = [{
			xtype: 'searchfield',
			id:'q',
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
			// search button handler
			handler: function() {
				Ext.util.JSONP.request({
					url: 'http://refresh.nouvelingenieur.fr/api/ideas.php',
					callbackKey: 'callback',
					params: {
						q: viewport.getDockedComponent(0).getComponent('q').getValue(),
						n: '10'
					},
					callback: function(result) {
						searchedString = viewport.getDockedComponent(0).getComponent('q').getValue();
						groupingBase.store.removeAll();
						for(i=0;i<result.data.length ;i++){
							groupingBase.store.add([{ideaName: result.data[i].IDEA_TITLE}]);
						}
					}
				});
			}
		}]
		
		Ext.regModel('Idea', {
			fields: ['ideaName']
		});

		var groupingBase = {
			itemTpl: '<div class="ideas"><strong>{ideaName}</strong></div>',
			selModel: {
				mode: 'SINGLE',
				allowDeselect: true
			},
			onItemDisclosure: {
				scope: 'test',
				handler: function(record, btn, index) {
					alert('Disclose more info for ' + record.get('ideaName'));
				}
			},
			store: new Ext.data.JsonStore({
				model: 'Idea',
				data: [],
				proxy: {
					type: 'ajax',
					url: 'http://refresh.nouvelingenieur.fr/api/ideas.php',
					callbackKey: 'callback',
					params: {
						n: '10',
						q: searchedString
					},
					callback: function(result) {
						groupingBase.store.removeAll();
						for(i=0;i<result.data.length ;i++){
							groupingBase.store.add([{ideaName: result.data[i].IDEA_TITLE}]);
						}
					}
				}
			})
		};
		
		paging = new Ext.plugins.ListPagingPlugin({});
		
		var searchResultList = new Ext.List(Ext.apply(groupingBase, {
			fullscreen: true,
			plugins:[paging]
		}));
		
		var viewport = new Ext.Panel({
			fullscreen: true,
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: search_items
			}, {
			title: 'test2',
				html: '<p></p>',
				dockedItems: searchResultList
			}]
		});
	}
});
