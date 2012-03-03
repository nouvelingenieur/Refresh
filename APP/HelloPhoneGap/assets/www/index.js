
Ext.setup({
	icon: 'icon.png',
	tabletStartupScreen: 'tablet_startup.png',
	phoneStartupScreen: 'phone_startup.png',
	glossOnIcon: false,
	onReady: function() {
		var searchedString = '';
		
		Ext.util.JSONP.request({
			url: 'http://refresh.nouvelingenieur.fr/api/categories.php',
			callbackKey: 'callback',
			params: {
			},
			callback: function(result) {
				var categoriesList = Array();
				categoriesList.push({text: 'All categories',  value: 0});
				for(i=0;i<result.data.length ;i++){
					categoriesList.push({text: result.data[i].CATEGORY_NAME,  value: result.data[i].CATEOGRY_ID});
				}
				searchPanel.getDockedComponent(0).getComponent('categoryList').setOptions(categoriesList);
			}
		});
		
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
			id: 'categoryList',
			placeHolder: 'Categories',
			options: [
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
						q: searchPanel.getDockedComponent(0).getComponent('q').getValue(),
						c: searchPanel.getDockedComponent(0).getComponent('categoryList').getValue()
					},
					callback: function(result) {
						searchedString = searchPanel.getDockedComponent(0).getComponent('q').getValue();
						groupingBase.store.removeAll();
						for(i=0;i<result.data.length ;i++){
							groupingBase.store.add([{ideaId:result.data[i].IDEA_ID, ideaCategoryId:result.data[i].IDEA_CATEGORY_ID, ideaName: result.data[i].IDEA_TITLE, ideaText: result.data[i].IDEA_TEXT, ideaAuthor: result.data[i].IDEA_AUTHOR, ideaDate: result.data[i].IDEA_DATE}]);
						}
					}
				});
			}
		}]
		
		Ext.regModel('Idea', {
			fields: ['ideaId', 'ideaCategoryId', 'ideaName', 'ideaText', 'ideaAuthor', 'ideaDate']
		});
		
		var ideaStore = new Ext.data.Store({
			model: 'Idea',
			data: [],
			pageSize: 5,
			clearOnPageLoad: false
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
					//alert('Disclose more info for ' + record.get('ideaName'));
					Ext.getCmp('ideaPanel').update(record.data);
					Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'left'});
				}
			},
			store: ideaStore
		};
		
		var searchResultList = new Ext.List(
			Ext.apply(groupingBase, {fullscreen: true})
		);
		
		var searchPanel = new Ext.Panel({
			fullscreen: true,
			id:'searchPanel',
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
		
		var ideaPanel = new Ext.Panel({
			fullscreen: true,
			id:'ideaPanel',
			scroll:'vertical',
			tpl:'<h1>{ideaName} by {ideaAuthor}, {ideaDate}</h1><div>{ideaText}</div>'
		});
		
		var panel =  new Ext.Panel({
			fullscreen: true,
			id:'thePanel',
			layout: 'card',
			cardSwitchAnimation:'slide',
			scroll:'vertical',
			 items:[searchPanel, ideaPanel]
		});
	}
});
