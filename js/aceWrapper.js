// SpellChecker is based on https://github.com/swenson/ace_spell_check_js

var AceWrapper = new Class({
	Implements: [Options],
	
	aceEditor: null,
	
		
	options: {
		editorid: "editor",
		theme: "ace/theme/monokai",
		mode: "ace/mode/html",
		showPrintMargin: false,
		useWrapMode: true,
		wrapModeButtonId: 'softwrap_',
		wrapmode: 'off',
	},
	
	// do the initialization
	initialize: function(options) {
		
	    this.setOptions(options);	    
	    this.setOptions(this.loadFromCookie());
	    
	    this.aceEditor = ace.edit(this.options.editorid);	    
	    
	    
	    this.aceEditor.setTheme(this.options.theme);
	    this.aceEditor.setShowPrintMargin(this.options.showPrintMargin);			    
	    this.aceEditor.getSession().setMode(this.options.mode);
	    this.aceEditor.getSession().setUseWrapMode(this.options.useWrapMode);
	    
	    $(this.options.wrapModeButtonId).addEvent('click',this.wrapModeClick.bind(this));
	    $(this.options.wrapModeButtonId).set('data-wrap',this.options.wrapmode);
	    console.log(this.options.wrapmode);
	    this.wrapModeClick();

	    
	    
	},
	
	wrapModeClick: function(e) {
		var button = $(this.options.wrapModeButtonId);
		var value = button.get('data-wrap');
		var session = this.aceEditor.session;
    	var renderer = this.aceEditor.renderer;
	    switch (value) {
	        case "off":
	            session.setUseWrapMode(false);
	            renderer.setPrintMarginColumn(80);
	            button.set('data-wrap','free');
	            this.options.wrapmode='off';
	            break;
	        case "free":
	            session.setUseWrapMode(true);
	            session.setWrapLimitRange(null, null);
	            renderer.setPrintMarginColumn(80);
	            button.set('data-wrap','100');
	            this.options.wrapmode='free';
	            break;
	        default:
	            session.setUseWrapMode(true);
	            var col = parseInt(value, 10);
	            session.setWrapLimitRange(col, col);
	            renderer.setPrintMarginColumn(col);
	            button.set('data-wrap','off');
	            this.options.wrapmode='100';
	    }
	    this.saveToCookie();
	    
	},
	
	saveToCookie: function() {
		var options = {
			wrapmode: this.options.wrapmode,			
		}
		var myCookie = Cookie.write('plg_editors_ace_wrapper', JSON.encode(options));
	},
		
	loadFromCookie: function() {
		var myCookie = Cookie.read('plg_editors_ace_wrapper');
		if (myCookie) {
			try{
		   		return JSON.decode(myCookie);
		   	} catch(e) {}
		}
		return {};
	},
	
});


