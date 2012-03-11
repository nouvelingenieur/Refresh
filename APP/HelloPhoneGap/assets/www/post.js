Ext.setup({
	icon: 'icon.png',
	tabletStartupScreen: 'tablet_startup.png',
	phoneStartupScreen: 'phone_startup.png',
	glossOnIcon: false,
	onReady: function() {
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
				postPanel.getDockedComponent(1).getComponent('categoryList').setOptions(categoriesList);
			}
		});
		
		var idea_items = [{
			xtype: 'textfield',
			id:'title',
			name : 'title',
			 //label: 'Search',
			placeHolder: ' Idea',
			options: [
			]
		},{ xtype: 'spacer' },
		{
			xtype: 'selectfield',
			name: 'Category',
			id: 'categoryList',
			placeHolder: 'Category of the idea',
			options: [
			]
		},
		{
			xtype: 'textfield',
			name: 'Text',
			id: 'Text',
			placeHolder: 'Description',
			options: [
			]
		}]
		var toolbar_objects = [
		{	xtype :'button',
			text: 'Search',
			ui :'round',
			handler: function() {
			window.location = 'http://refresh.nouvelingenieur.fr/api/index.html'
			}
		},  
		{ xtype: 'spacer' },
		{ 	xtype :'button',
			text: 'Post',
			ui: 'round',
			// search button handler
			handler: function() {
				Ext.util.JSONP.request({
					url: 'http://refresh.nouvelingenieur.fr/api/post.php',
					callbackKey: 'callback',
					params: {
						IDEA_TITLE: postPanel.getDockedComponent(1).getComponent('title').getValue(),
						IDEA_TEXT: postPanel.getDockedComponent(1).getComponent('Text').getValue(),
						IDEA_CATEOGRY_ID: postPanel.getDockedComponent(1).getComponent('categoryList').getValue()
					},
					callback: function() {
					}
				});
			}
			
		}]

   
			var postPanel = new Ext.Panel({
			fullscreen: true,
			id:'searchPanel',
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				title: 'Post an idea',
				items: [toolbar_objects]
			}, 
			{
				xtype: 'panel',
				layout: {
					type :'vbox',
					align: 'center',
					pack:'center'
					},
				items: idea_items
			}]
			});
	}
});

