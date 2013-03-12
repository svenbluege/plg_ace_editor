var SpellChecker = new Class({
	Implements: [Options],
	
	contents_modified: true,
	currently_spellchecking: false,
	markers_present: [],
	interval : null,
	options: {
		lang: 'de',
		path: '../plugins/editors/ace/SpellGoogle.php',
		editor: 'editor',
		useGutter: false,
		buttonid_enable: "_enable",
		buttonid_disable: "_disable",
		selectid_lang: "_lang",
		spellcheckEnabled: true,
	},
	
	// do the initialization
	initialize: function(options) {
		
	    this.setOptions(options);
	    this.setOptions(this.loadFromCookie());
	    
	    // set select box to the right value 
	    for(i=0; 0<$(this.options.selectid_lang).options.length; i++) {
	    	if ($(this.options.selectid_lang).options[i].value==this.options.lang) {
	    		$(this.options.selectid_lang).selectedIndex = i;
	    		break;
	    	}
	    }
	    
	    $(this.options.buttonid_enable).addEvent('click',function(e){ 
	    	this.enable();
	    }.bind(this));
	    
	    $(this.options.buttonid_disable).addEvent('click',function(e){ 
	    	this.disable();
	    }.bind(this));
	        
	    $(this.options.selectid_lang).addEvent('change', function(e) {
	    	this.languageSelectionChanged();
	    }.bind(this));

		if (this.options.spellcheckEnabled) {    	
		 	this.enable();
	    } else {
	    	this.disable();
	    }	    
	},
		
	languageSelectionChanged: function() {
		this.options.lang = $(this.options.selectid_lang).options[$(this.options.selectid_lang).selectedIndex].value;
		this.onContentChange();
		this.saveToCookie();
	},
		
	// perform the check
    check: function( text, callback) {
    	var myJSONRemote = new Request.JSON({
    		url: this.options.path, 
    		onSuccess: function(result){    			
			    callback(result);
			},
			data: {
				lang:   this.options.lang,
				text: text
			},
			method: 'POST',
		});
		myJSONRemote.send();    	
    },
    
    // Check the spelling of a line, and return [start, end]-pairs for misspelled words.
	misspelled: function(linenumber, line) {
		try {
			this.check(line, function(badWords) {
				this.markTypos(linenumber, line, badWords);	
			}.bind(this));
		} catch (e) {
			if (console) {
				console.log(e);
			}
		}
	},
		
	// Spell check the Ace editor contents.
	spell_check: function() {
	
		if (this.currently_spellchecking) {
			return;
		}

		if (!this.contents_modified) {
			return;
		}
		
		this.currently_spellchecking = true;
		var session = ace.edit(this.options.editor).getSession();

		// Clear the markers.
		this.clearMarkers();

		try {
			 
			var Range = ace.require('ace/range').Range
			var lines = session.getDocument().getAllLines();

			for (i=0; i<lines.length; i++) {
				// Clear the gutter.
				if (this.options.useGutter) {
					session.removeGutterDecoration(i, "misspelled");
				}
				
				// Check spelling of this line.
				this.misspelled(i, lines[i]);
			}
		} finally {
			this.currently_spellchecking = false;
			this.contents_modified = false;
		}
	},

	// Clear the markers.	
	clearMarkers: function() {

		var session = ace.edit(this.options.editor).getSession();
		for (var i in this.markers_present) {
			session.removeMarker(this.markers_present[i]);
		}
		this.markers_present = [];
	},
	
	// marks the found errors in a line		
	markTypos: function(linenumber, line, badWords) {
		var session = ace.edit(this.options.editor).getSession();
		var words = line.split(/[^a-zA-Z\']/g);
		var i = 0;
		var misspellings = [];
		for (word in words) {
		  var x = words[word] + "";
		  var checkWord = x.replace(/[^a-zA-Z']/g, '');
		  
		  if (badWords.contains(checkWord)) {
		       misspellings[misspellings.length] = [i, i + words[word].length];
		  }
		  i += words[word].length + 1;
		}  

		var Range = ace.require('ace/range').Range;
		for (var j in misspellings) {
			var range = new Range(linenumber, misspellings[j][0], linenumber, misspellings[j][1]);
			this.markers_present[this.markers_present.length] = session.addMarker(range, "misspelled", "typo", true);
		}
		
		// Add markers and gutter markings.
		if (this.options.useGutter && badWords.length > 0) {
			session.addGutterDecoration(i, "misspelled");
		}
	},

	onContentChange: function(event) {
		this.contents_modified = true;				
	},

	// register to the change event of the editor and run a spellcheck in a given interval.
	enable: function () {
		$(this.options.buttonid_enable).hide();
    	$(this.options.buttonid_disable).show();
		this.onContentChange();
		this.spell_check();
		ace.edit(this.options.editor).getSession().on('change', this.onContentChange.bind(this));
		this.interval = setInterval(this.spell_check.bind(this) , 1000);
		this.options.spellcheckEnabled = true;
		this.saveToCookie();
	},
	
	disable: function() {
		$(this.options.buttonid_disable).hide();
    	$(this.options.buttonid_enable).show();
		// Clear the markers.
		this.clearMarkers();
		clearInterval(this.interval);	
		this.options.spellcheckEnabled = false;
		this.saveToCookie();
	},
	
	saveToCookie: function() {
		var options = {
			lang: this.options.lang,
			spellcheckEnabled: this.options.spellcheckEnabled
		}
		var myCookie = Cookie.write('plg_editors_ace_lang', JSON.encode(options));
	},
		
	loadFromCookie: function() {
		var myCookie = Cookie.read('plg_editors_ace_lang');
		if (myCookie) {
			try{
		   		return JSON.decode(myCookie);
		   	} catch(e) {}
		}
		return {};
	},
    
});

document.write("<style type='text/css'>.ace_marker-layer .misspelled { position: absolute; z-index: -2; border-bottom: 1px solid red !important; margin-bottom: -1px; }</style>");
document.write("<style type='text/css'>.misspelled { border-bottom: 1px solid red !important; margin-bottom: -1px; }</style>");

