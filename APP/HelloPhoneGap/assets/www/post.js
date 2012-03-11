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
				(formPost.items.get(1)).setOptions(categoriesList);
			}
		});
		
		
		
		var toolbar_objects = [
		{	xtype :'button',
			text: 'Search',
			ui :'round',
			handler: function() {
			window.location = 'http://refresh.nouvelingenieur.fr/app/index.html'
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
						IDEA_TITLE: (formPost.items.get(0)).getValue(),
						IDEA_TEXT: (formPost.items.get(2)).getValue(),
						IDEA_CATEOGRY_ID: (formPost.items.get(1)).getValue()
					},
					callback: function() {
					}
				});
			}
			
		}]
		
		var toolbar = new Ext.Toolbar({
			title :'Post an idea',
			id:'toolbar',
			items:  [toolbar_objects]
			});
		//Form Panel
		var formPost = new Ext.form.FormPanel({
		id: 'formPost',
		items: [{
			xtype: 'textfield',
			id:'title',
			name : 'title',
			 //label: 'Search',
			label: ' Idea', 
			required: true,
			options: [
			]
		},
		{
			xtype: 'selectfield',
			name: 'Category',
			id: 'categoryList',
			label: 'Category of the idea',
			required: true,
			options: [
			]
		},
		{
			xtype: 'textareafield',
			name: 'Text',
			id: 'Text',
			label: 'Description', 
			required: true,
			options: [
			]
		}],
		dockedItems : [toolbar]
		});
				
		var panel =  new Ext.Panel({
			fullscreen: true,
			id:'thePanel',
			layout: 'card',
			cardAnimation: 'slide',
	    	items: [formPost]
			
		});
	}
})
   