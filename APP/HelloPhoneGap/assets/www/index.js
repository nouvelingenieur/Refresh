
Ext.setup({
	icon: 'icon.png',
	tabletStartupScreen: 'tablet_startup.png',
	phoneStartupScreen: 'phone_startup.png',
	glossOnIcon: false,
	onReady: function() {
		
		
		// login panel
		Ext.regModel('User', {
			fields: [
				{name: 'name', type: 'string'},
				{name: 'password', type: 'password'},
				{name: 'server', type: 'string'}
			]
		});
		
		Ext.regModel('servers', {
			fields: [
				{name: 'server', type: 'string'},
				{name: 'title', type: 'string'}
			]
		});
		
		var serverStore = new Ext.data.JsonStore({
			data : [
				{ server : 'master',  title : 'Master'},
				{ server : 'padawan', title : 'Student'},
				{ server : 'teacher', title : 'Instructor'},
				{ server : 'aid', title : 'Assistant'}
			],
			model : 'servers',
			autoLoad : true,
			autoDestroy : true
		});
		
		var formBase = {
			scroll: 'vertical',
			url   : 'http://refresh.nouvelingenieur.fr/api/login.php',
			standardSubmit : false,
			items: [
				{
					xtype: 'fieldset',
					title: 'Login',
					instructions: '',
					defaults: {
						required: true,
						labelAlign: 'left',
						labelWidth: '40%'
					},
					items: [
						{
							xtype: 'textfield',
							name : 'email',
							label: 'E-mail',
							useClearIcon: true,
							autoCapitalize : false
						}, {
							xtype: 'passwordfield',
							name : 'password',
							label: 'Password',
							useClearIcon: false
						}, {
							xtype: 'selectfield',
							name : 'server',
							label: 'Server',
							valueField : 'server',
							displayField : 'title',
							store : serverStore
						}
					]
				}
			],
			listeners : {
				submit : function(form, result){
					console.log('success', Ext.toArray(arguments));
					Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'left'});
				},
				exception : function(form, result){
					console.log('failure', Ext.toArray(arguments));
					Ext.Msg.alert('Error', 'We were unable to connect to the server. Please, review the information entered.', Ext.emptyFn);
				}
			},
			dockedItems: [
				{
					xtype: 'toolbar',
					dock: 'bottom',
					items: [
						{xtype: 'spacer'},
						{
							text: 'Reset',
							handler: function() {
								form.reset();
							}
						},
						{
							text: 'Login',
							ui: 'confirm',
							handler: function() {
								if(formBase.user){
									form.updateRecord(formBase.user, true);
								}
								form.submit({
									waitMsg : {message:'Submitting', cls : 'demos-loading'}
								});
							}
						}
					]
				}
			]
		};
		
		form = new Ext.form.FormPanel(formBase);
		form.show();
		
		
		// idea panel
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
		
		// top search toolbar
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
		},
		{
			text: 'Back',
			ui: 'round',
			hidden: true,
			// search button handler
			handler: function() {
				this.setVisible(true);
				Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'right'});
			}
		}]
		
		// top idea toolbar
		var topIdeaToolbar = [{
			text: 'Back',
			ui: 'round',
			// search button handler
			handler: function() {
				this.setVisible(true);
				Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'right'});
			}
		}]
		
		// idea data type
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
					Ext.getCmp('thePanel').setActiveItem(2,{type:'slide',direction:'left'});
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
		
		// idea panel
		var ideaPanel = new Ext.Panel({
			fullscreen: true,
			id:'ideaPanel',
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: topIdeaToolbar
			}],
			scroll:'vertical',
			tpl:'<div class="containerBox"><h1 id="ideaTitle">{ideaName}</h1> by {ideaAuthor}, {ideaDate}</h1><div>{ideaText}</div></div>'
		});
		
		var panel =  new Ext.Panel({
			fullscreen: true,
			id:'thePanel',
			layout: 'card',
			cardSwitchAnimation:'slide',
			scroll:'vertical',
			items:[form, searchPanel, ideaPanel]
		});
	}
});
