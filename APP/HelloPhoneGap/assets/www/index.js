var form;
var currentPanel;

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
				{ server : 'http://refresh.nouvelingenieur.fr',  title : 'Default'}
			],
			model : 'servers',
			autoLoad : true,
			autoDestroy : true
		});
		
		var formBase = {
			scroll: 'vertical',
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
							xtype: 'emailfield',
							name : 'EMAIL',
							label: 'E-mail',
							useClearIcon: false,
							autoCapitalize : false
						}, {
							xtype: 'passwordfield',
							name : 'PASSWORD',
							label: 'Password',
							useClearIcon: false
						}, {
							xtype: 'selectfield',
							name : 'SERVER_URL',
							label: 'Server',
							valueField : 'server',
							displayField : 'title',
							store : serverStore
						}
					]
				}
			],
			dockedItems: [
				{
					xtype: 'toolbar',
					dock: 'bottom',
					items: [
						{
							text: 'Reset',
							handler: function() {
								form.reset();
							}
						},
						{xtype: 'spacer'},
						{
							text: 'Login',
							ui: 'action',
							handler: function() {
								Ext.util.JSONP.request({
									url: form.getValues().SERVER_URL+'/api/login.php',
									callbackKey: 'callback',
									params: {
										EMAIL: SHA1(form.getValues().EMAIL),
										PASSWORD: SHA1(form.getValues().PASSWORD)
									},
									callback: function(result) {
										if (result.data.SUCCESS=='True') {
											Ext.util.JSONP.request({
												url: form.getValues().SERVER_URL+'/api/categories.php',
												callbackKey: 'callback',
												params: {
													EMAIL: SHA1(form.getValues().EMAIL),
													PASSWORD: SHA1(form.getValues().PASSWORD)
												},
												callback: function(result) {
													var categoriesList = Array();
													categoriesList.push({text: 'All categories',  value: 0});
													for(i=0;i<result.data.length ;i++){
														categoriesList.push({text: html_entity_decode(result.data[i].CATEGORY_NAME),  value: result.data[i].CATEOGRY_ID});
													}
													searchPanel.getDockedComponent(0).getComponent('categoryList').setOptions(categoriesList);
													(formPost.items.get(1)).setOptions(categoriesList);
												}
											});
											Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'left'});
											currentPanel = 1;
										} else {
											Ext.Msg.alert('Error', 'We were unable to connect to the server. Please, review the information entered.', Ext.emptyFn);
										}
									}
								});
							}
						}
					]
				}
			]
		};
		
		form = new Ext.form.FormPanel(formBase);
		form.show();
		
		// bottom bar
		var buttonsSpecBottom = [
			{ ui: 'normal', text: 'Search' },
			{ ui: 'normal', text: 'Post'}
		]
		
		var tapHandler = function (btn, evt) {
			switch(btn.text) {
				case 'Search':
					Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'right'});
				break;
				case 'Post':
					Ext.getCmp('thePanel').setActiveItem(3,{type:'slide',direction:'left'});
				break;
			}
		}
		
		var bottomBar = {
			xtype: 'toolbar',
			ui: 'dark',
			dock: 'bottom',
			layout: {
				pack: 'justify',
				align: 'center' // align center is the default
			},
			items: buttonsSpecBottom,
			defaults: { handler: tapHandler }
		}
		
		// idea panel
		var searchedString = '';
		
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
					url: form.getValues().SERVER_URL+'/api/ideas.php',
					callbackKey: 'callback',
					params: {
						q: searchPanel.getDockedComponent(0).getComponent('q').getValue(),
						c: searchPanel.getDockedComponent(0).getComponent('categoryList').getValue(),
						EMAIL: SHA1(form.getValues().EMAIL),
						PASSWORD: SHA1(form.getValues().PASSWORD)
					},
					callback: function(result) {
						searchedString = searchPanel.getDockedComponent(0).getComponent('q').getValue();
						groupingBase.store.removeAll();
						for(i=0;i<result.data.length ;i++){
							groupingBase.store.add([{ideaId:result.data[i].IDEA_ID, ideaCategoryId:result.data[i].IDEA_CATEGORY_ID, ideaName: result.data[i].IDEA_TITLE, ideaText: result.data[i].IDEA_TEXT, ideaAuthor: result.data[i].IDEA_AUTHOR, ideaDate: result.data[i].IDEA_DATE, ideaLikes: result.data[i].IDEA_POSITIVE_VOTES, ideaDislikes: result.data[i].IDEA_NEGATIVE_VOTES}]);
						}
					}
				});
			}
		},
		{
			text: 'Back',
			ui: 'back',
			hidden: true,
			// search button handler
			handler: function() {
				this.setVisible(true);
				Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'right'});
				currentPanel = 1;
			}
		}]
		
		// top idea toolbar
		var topIdeaToolbar = [{
			text: 'Back',
			ui: 'back',
			// search button handler
			handler: function() {
				this.setVisible(true);
				Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'right'});
				currentPanel = 1;
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
					Ext.getCmp('ideaPanel').update(record.data);
					Ext.getCmp('thePanel').setActiveItem(2,{type:'slide',direction:'left'});
					currentPanel = 2;
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
			}, bottomBar]
		});
		
		// idea panel
		var like = function() {
			console.log('like');
			Ext.util.JSONP.request({
				url: 'http://free.worldweatheronline.com/feed/weather.ashx',
				callbackKey: 'callback',
				params: {
					key: '23f6a0ab24185952101705',
				},
				callback: function(result) {
					// update ratings
				}
			});
		};
		
		var dislike = function() {
			console.log('dislike');
			Ext.util.JSONP.request({
				url: 'http://free.worldweatheronline.com/feed/weather.ashx',
				callbackKey: 'callback',
				params: {
					key: '23f6a0ab24185952101705',
				},
				callback: function(result) {
					// update ratings
				}
			});
		};
		
		var goToCommentPanel = function() {
			// slide
			console.log('goToCommentPanel');
		};
		
		var toolbar_icons = {
			xtype: 'toolbar',
			dock: 'bottom',
			scroll: 'horizontal',
			items: [
				{ iconMask: true, iconAlign: 'left', ui: 'round', text: 'Comment', iconCls: 'compose', handler: goToCommentPanel},
				{xtype: 'spacer'},
				{ iconMask: true, iconAlign: 'left', ui: 'action-round', text: 'Like', iconCls: 'add', handler: like},
				{ iconMask: true, iconAlign: 'left', ui: 'action-round', text: 'Dislike', iconCls: 'delete', handler: dislike},
			]
		}
		
		var ideaPanel = new Ext.Panel({
			id:'ideaPanel',
			dockedItems: [toolbar_icons],
			scroll: 'both',
			tpl:'<div class="containerBox"><h1 id="ideaTitle">{ideaName}</h1> by {ideaAuthor} on {ideaDate}</h1><div>{ideaText}</div><div><ul><li>Likes: {ideaLikes}</li><li>Dislikes: {ideaDislikes}</li></ul></div></div>',
		});
		
		var ideaPanelAndComments = new Ext.Panel({
			fullscreen: true,
			id:'ideaPanelAndComments',
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: topIdeaToolbar
			},ideaPanel],
			scroll:'vertical'
		});
		
		// post panel
		// bottom bar
		var postButtonsSpecBottom = [
			{ ui: 'normal', text: 'Search' },
			{ ui: 'action', text: 'Post' }
		]
		
		var postTapHandler = function (btn, evt) {
			switch(btn.text) {
				case 'Search':
					Ext.getCmp('thePanel').setActiveItem(1,{type:'slide',direction:'right'});
					currentPanel = 1;
				break;
				case 'Post':
					Ext.util.JSONP.request({
						url: form.getValues().SERVER_URL+'/api/post.php',
						callbackKey: 'callback',
						params: {
							IDEA_TITLE: (formPost.items.get(0)).getValue(),
							IDEA_TEXT: (formPost.items.get(2)).getValue(),
							IDEA_CATEOGRY_ID: (formPost.items.get(1)).getValue(),
							EMAIL: SHA1(form.getValues().EMAIL),
							PASSWORD: SHA1(form.getValues().PASSWORD)
						},
						callback: function() {
						}
					});
				break;
			}
		}
		
		var postBottomBar = {
			xtype: 'toolbar',
			ui: 'dark',
			dock: 'bottom',
			layout: {
				pack: 'justify',
				align: 'center' // align center is the default
			},
			items: postButtonsSpecBottom,
			defaults: { handler: postTapHandler }
		}
		
		//Form Panel
		var formPost = new Ext.form.FormPanel({
		id: 'formPost',
		scroll: 'vertical',
		items: [{
			xtype: 'textfield',
			id:'title',
			name : 'title',
			label: ' Idea', 
			required: true,
			options: [
			]
		},
		{
			xtype: 'selectfield',
			name: 'Category',
			id: 'categoryList2',
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
		}]
		});
		
		var postPanel =  new Ext.Panel({
			id:'postPanel',
			fullscreen: true,
			items: [formPost],
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: {
					text: 'Back',
					ui: 'back',
					// search button handler
					handler: function() {
						Ext.getCmp('thePanel').setActiveItem(currentPanel,{type:'slide',direction:'right'});
					}
				}
			}, postBottomBar]
		});
		
		// global panel
		var panel =  new Ext.Panel({
			fullscreen: true,
			id:'thePanel',
			layout: 'card',
			cardSwitchAnimation:'slide',
			scroll:'vertical',
			items:[form, searchPanel, ideaPanelAndComments, postPanel]
		});
	}
});
