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
		
		// top toolbar
		var idea_items = [{
			xtype: 'textfield',
			id:'title',
			name : 'title',
			 //label: 'Search',
			placeHolder: ' Idea',
			options: [
			]
		},
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
		var confirm = [
		{	
			xtype :'button',
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
				type:'vbox',
				pack:'center',
				dock: 'top',
				html: '<br><center> Post an idea <center><br>'	
				
			},
			{
				xtype: 'panel',
				layout: {
					type :'vbox',
					pack :'stretch',
					align: 'center'
					},
				items: idea_items
			},
			{
				xtype: 'panel',
				layout: {
					type: 'vbox',
					pack:'center',
					align: 'start'
					},
				dock:'bottom',
				
				items:confirm
			}]
		});
	}
});

