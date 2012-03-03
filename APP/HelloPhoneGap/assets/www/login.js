Ext.setup({
	onReady: function() {
	
	
	/*
// Usage (for example in your application controller):

// Initialize
app.cookie = new Ext.util.LocalStorageCookie();

// Initialize with config
app.cookie = new Ext.util.LocalStorageCookie({
	proxyId: 'com.mydomain.cookies',
});

// Set a value
app.cookie.set('some_setting', 'some_value');

// Get a value
app.cookie.get('some_setting');

*/
Ext.util.LocalStorageCookie = Ext.extend(Object, {
  
  proxyId: 'com.domain.cookies',
  
  constructor: function(config) {
    
    this.config = Ext.apply(this, config);
    
    // Create the cookie model
    Ext.regModel('LocalStorageCookie', {
      fields: [
        'id',
        'key',
        'value',
      ],
      proxy: {
        type: 'localstorage',
        id: this.proxyId,
      }
    });
    // Create the cookie store 
    this.store = new Ext.data.Store({
      model: "LocalStorageCookie",
    });    
    this.store.load();
  },
  
  // Get function
  get: function(key) {
    var indexOfRecord = this.store.find('key', key);
    if (indexOfRecord == -1) {
      return indexOfRecord;
    }
    else {
      var record = this.store.getAt(indexOfRecord);
      return record.get('value');
    }
  },
  
  // Set function
  set: function(key, value) {
    var indexOfRecord = this.store.find('key', key);
    if (indexOfRecord == -1) {
      var record = Ext.ModelMgr.create({key:key, value:value}, 'LocalStorageCookie');
    }
    else {
      var record = this.store.getAt(indexOfRecord);
      record.set('value', value);
    }  
    return record.save();
  },  
});

		new Ext.Application({
			launch: function() {
				app.cookie = new Ext.util.LocalStorageCookie();

				Ext.state.Manager.setProvider(cp);
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
					alert('hello');
					app.cookie.set('some_setting', 'some_value');
					app.cookie.get('some_setting');
					alert('olleh');
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
