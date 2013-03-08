// You also need to load in typo.js and jquery.js

// You should configure these classes.


// Make red underline for gutter and words.
jQuery("<style type='text/css'>.ace_marker-layer .misspelled { position: absolute; z-index: -2; border-bottom: 1px solid red; margin-bottom: -1px; }</style>").appendTo("head");
jQuery("<style type='text/css'>.misspelled { border-bottom: 1px solid red; margin-bottom: -1px; }</style>").appendTo("head");

// Load the dictionary.
// We have to load the dictionary files sequentially to ensure 
var currentDictionary = null;

function initialize_ACESpellCheck(editor, lang) {

	var dicPath = "../plugins/editors/ace/dict/"+lang+"/"+lang+".dic";
	var affPath = "../plugins/editors/ace/dict/"+lang+"/"+lang+".aff";

	jQuery.get(dicPath, function(data) {
		dicData = data;
	}).done(function() {
		jQuery.get(affPath, function(data) {
			affData = data;
		}).done(function() {
			console.log("Dictionary for "+lang+" loaded");
			currentDictionary = new Typo(lang, affData, dicData);
			enable_spellcheck(editor);
			spell_check(editor);
		});
	});
}

// Check the spelling of a line, and return [start, end]-pairs for misspelled words.
function misspelled(line) {
	var words = line.split(' ');
	var i = 0;
	var bads = [];
	for (word in words) {
	  var x = words[word] + "";
	  var checkWord = x.replace(/[^a-zA-Z']/g, '');
	  if (!currentDictionary.check(checkWord)) {
	    bads[bads.length] = [i, i + words[word].length];
	  }
	  i += words[word].length + 1;
  }
  return bads;
}

var contents_modified = true;

var currently_spellchecking = false;

var markers_present = [];

// Spell check the Ace editor contents.
function spell_check(editor) {
	console.log(editor)
  // Wait for the dictionary to be loaded.
	if (currentDictionary == null) {
	return;
	}

	if (currently_spellchecking) {
		return;
	}

	if (!contents_modified) {
		return;
	}
	currently_spellchecking = true;
	var session = ace.edit(editor).getSession();

	// Clear the markers.
	for (var i in markers_present) {
	session.removeMarker(markers_present[i]);
	}
	markers_present = [];

	try {
		 
		var Range = ace.require('ace/range').Range
		var lines = session.getDocument().getAllLines();

		for (i=0; i<lines.length; i++) {
			// Clear the gutter.
			//session.removeGutterDecoration(i, "misspelled");
			// Check spelling of this line.
			var misspellings = misspelled(lines[i]);

			// Add markers and gutter markings.
			//if (misspellings.length > 0) {
			  //session.addGutterDecoration(i, "misspelled");
			//}
			for (var j in misspellings) {
			  var range = new Range(i, misspellings[j][0], i, misspellings[j][1]);
			  markers_present[markers_present.length] = session.addMarker(range, "misspelled", "typo", true);
			}
		}
	} finally {
		currently_spellchecking = false;
		contents_modified = false;
	}
}

function enable_spellcheck(editor) {
	ace.edit(editor).getSession().on('change', function(e) {
		contents_modified = true;
	});
	setInterval(function() {spell_check(editor)}, 500);
}