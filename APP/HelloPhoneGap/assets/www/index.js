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
    }
});