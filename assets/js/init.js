;(function ( $, window, document, undefined ) {
  "use strict";

  // BROWSER SNIFFING

  // Kludges for bugs and behavior differences that can't be feature
  // detected are enabled based on userAgent etc sniffing.

  var gecko = /gecko\/\d/i.test(navigator.userAgent);
  var ie_upto10 = /MSIE \d/.test(navigator.userAgent);
  var ie_11up = /Trident\/(?:[7-9]|\d{2,})\..*rv:(\d+)/.exec(navigator.userAgent);
  var ie = ie_upto10 || ie_11up;
  var ie_version = ie && (ie_upto10 ? document.documentMode || 6 : ie_11up[1]);
  var webkit = /WebKit\//.test(navigator.userAgent);
  var qtwebkit = webkit && /Qt\/\d+\.\d+/.test(navigator.userAgent);
  var chrome = /Chrome\//.test(navigator.userAgent);
  var presto = /Opera\//.test(navigator.userAgent);
  var safari = /Apple Computer/.test(navigator.vendor);
  var mac_geMountainLion = /Mac OS X 1\d\D([8-9]|\d\d)\D/.test(navigator.userAgent);
  var phantom = /PhantomJS/.test(navigator.userAgent);

  var ios = /AppleWebKit/.test(navigator.userAgent) && /Mobile\/\w+/.test(navigator.userAgent);
  // This is woefully incomplete. Suggestions for alternative methods welcome.
  var mobile = ios || /Android|webOS|BlackBerry|Opera Mini|Opera Mobi|IEMobile/i.test(navigator.userAgent);
  var mac = ios || /Mac/.test(navigator.platform);
  var windows = /win/i.test(navigator.platform);

  var presto_version = presto && navigator.userAgent.match(/Version\/(\d*\.\d*)/);
  if (presto_version) presto_version = Number(presto_version[1]);
  if (presto_version && presto_version >= 15) { presto = false; webkit = true; }
  // Some browsers use the wrong event properties to signal cmd/ctrl on OS X
  var flipCtrlCmd = mac && (qtwebkit || presto && (presto_version == null || presto_version < 12.11));
  var captureRightClick = gecko || (ie && ie_version >= 9);

  // Ace keybinding
  ace.require("ace/commands/default_commands").commands.push(
    {
      name: "Toggle Fullscreen",
      bindKey: "F11",
      exec: function(editor) {
        var fullScreen = $(document.body).toggleClass("fullScreen");
        $(editor.container).toggleClass("fullScreen", fullScreen);
        editor.setAutoScrollEditorIntoView(!fullScreen);
        editor.resize(true);
      }
    }, 
    {
      name: "Joomla Apply",
      bindKey: {win: "Ctrl-S", mac: "Command-S"},
      exec: function(editor) {
        $('[onclick*="apply"').click();
      }
    }
  );

  // Create the defaults once
  var defaults = {
        editorid: "editor",
        theme: "ace/theme/textmate",
        mode: "ace/mode/html",
        showPrintMargin: true,
        useWrapMode: true,
        wrapModeBtnId: 'softwrap_',
        wrapmode: 'off',
      };

  // The actual plugin constructor
  function Ace ( textarea, options ) {
    this.textarea = textarea;
    this.$textarea = $(textarea);
    // jQuery has an extend method which merges the contents of two or
    // more objects, storing the result in the first object. The first object
    // is generally empty as we don't want to alter the default options for
    // future instances of the plugin
    this.options = $.extend( {}, defaults, options );
    this._defaults = defaults;
    this.init();
  }

  // Avoid Plugin.prototype conflicts
  $.extend(Ace.prototype, {
    init: function () {
      var aceObj  = this,
          id      = aceObj.options.editorid;

      aceObj.$textarea.after(
        $('<div>', {
          id: 'resize_' + id,
          class: 'ace-wrapper',
          style: 'width: '+ aceObj.options.width + ';height: ' + aceObj.options.height,
        }).append(
          $('<div>', {
            id: 'ace_' + id,
          }).html(aceObj.$textarea.html())
        )
      );

      aceObj.editor = ace.edit('ace_' + id);
      
      aceObj.editor.setTheme(aceObj.options.theme);
      aceObj.editor.setShowPrintMargin(aceObj.options.showPrintMargin);          
      aceObj.editor.getSession().setMode(aceObj.options.mode);
      aceObj.editor.getSession().setUseWrapMode(aceObj.options.useWrapMode);
      aceObj.editor.resize(true);
      aceObj.editor.$blockScrolling = Infinity;
      
      $('#'+aceObj.options.wrapModeBtnId)
        .on('click', aceObj.wrapModeClick.bind(this))
        .attr('data-wrap',aceObj.options.wrapmode);

      aceObj.wrapModeClick();
      function save() {
        aceObj.textarea.value = aceObj.editor.getValue();
      }
      var form = aceObj.textarea.form;
      if (form) {
        $(form).submit(save);
        // Deplorable hack to make the submit method do the right thing.
        if (!aceObj.options.leaveSubmitMethodAlone) {
          var realSubmit = form.submit;
          try {
            var wrappedSubmit = form.submit = function() {
              save();
              form.submit = realSubmit;
              form.submit();
              form.submit = wrappedSubmit;
            };
          } catch(e) {console.log(e)}
        }
      }
    },
    wrapModeClick: function(e) {
      //e.preventDefault();
      var button   = $('#'+this.options.wrapModeBtnId),
          value    = button.attr('data-wrap'),
          session  = this.editor.session,
          renderer = this.editor.renderer;
      switch (value) {
        case "off":
          session.setUseWrapMode(false);
          renderer.setPrintMarginColumn(80);
          button.attr('data-wrap','free');
          this.options.wrapmode='off';
          break;
        case "free":
          session.setUseWrapMode(true);
          session.setWrapLimitRange(null, null);
          renderer.setPrintMarginColumn(80);
          button.attr('data-wrap','100');
          this.options.wrapmode='free';
          break;
        default:
          session.setUseWrapMode(true);
          var col = parseInt(value, 10);
          session.setWrapLimitRange(col, col);
          renderer.setPrintMarginColumn(col);
          button.attr('data-wrap','off');
          this.options.wrapmode='100';
          break;
      }
      this.saveToCookie();
    },
    saveToCookie: function() {
      var options = {
        wrapmode: this.options.wrapmode
      }
      var myCookie = $.cookie('plg_editors_ace_wrapper', JSON.stringify(options));
    },
      
    loadFromCookie: function() {
      var myCookie = $.cookie('plg_editors_ace_wrapper');
      if (myCookie) {
        try{
            return JSON.decode(myCookie);
          } catch(e) {}
      }
      return {};
    }
  });

  // A really lightweight plugin wrapper around the constructor,
  // preventing against multiple instantiations
  $.fn.ace = function ( options ) {
    return this.each(function() {
      if ( !$.data( this, "plugin_ace" ) ) {
        $.data( this, "plugin_ace", new Ace( this, options ) );
      }
    });
  };

  //Get active element (Not used for now)
  function activeElt() { return document.activeElement; }
  // Older versions of IE throws unspecified error when touching
  // document.activeElement in some cases (during loading, in iframe)
  if (ie && ie_version < 11) activeElt = function() {
    try { return document.activeElement; }
    catch(e) { return document.body; }
  };

})( jQuery, window, document );