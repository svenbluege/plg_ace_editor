var SpellChecker = new Class({
	Implements: [Options],
	
	contents_modified: true,
	currently_spellchecking: false,
	markers_present: [],
	
	options: {
		lang: 'de',
		path: '../plugins/editors/ace/SpellGoogle.php',
		editor: 'editor',
	},
	
	// do the initialization
	initialize: function(options) {
	    this.setOptions(options);
		this.enable_spellcheck(this.options.editor);	
		this.spell_check(this.options.editor);
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
			console.log(e);
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
		for (var i in this.markers_present) {
			session.removeMarker(this.markers_present[i]);
		}
		this.markers_present = [];

		try {
			 
			var Range = ace.require('ace/range').Range
			var lines = session.getDocument().getAllLines();

			for (i=0; i<lines.length; i++) {
				// Clear the gutter.
				//session.removeGutterDecoration(i, "misspelled");
				
				// Check spelling of this line.
				this.misspelled(i, lines[i]);

				// Add markers and gutter markings.
				//if (misspellings.length > 0) {
				  //session.addGutterDecoration(i, "misspelled");
				//}
				
			}
		} finally {
			this.currently_spellchecking = false;
			this.contents_modified = false;
		}
	},
	
	// marks the found errors in a line		
	markTypos: function(linenumber, line, badWords) {
		var session = ace.edit(this.options.editor).getSession();
		var words = line.split(/[^a-zA-Z\']/g);
		var i = 0;
		var misspellings = [];
		console.log(linenumber);
		console.log(badWords);
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
	},

	// register to the change event of the editor and run a spellcheck in a given interval.
	enable_spellcheck: function () {
		ace.edit(this.options.editor).getSession().on('change', function(e) {
			this.contents_modified = true;				
		}.bind(this));
		setInterval(function() {this.spell_check(this.options.editor)}.bind(this), 1000);
	}
    
});

document.write("<style type='text/css'>.ace_marker-layer .misspelled { position: absolute; z-index: -2; border-bottom: 1px solid red !important; margin-bottom: -1px; }</style>");
document.write("<style type='text/css'>.misspelled { border-bottom: 1px solid red !important; margin-bottom: -1px; }</style>");



