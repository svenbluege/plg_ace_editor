<?php
/**
 * @copyright Copyright (C) 2012 Sven Bluege, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later
 */

// no direct access
defined('_JEXEC') or die;

/**
 * ACE Textarea Editor Plugin
 *
 */
class plgEditorAce extends JPlugin
{

  private $use_spellchecker = false;
  private $spellchecker_language = 'en_US';

  /**
   * Constructor
   *
   * @param  object  $subject  The object to observe
   * @param  array   $config   An array that holds the plugin configuration
   *
   * @since       1.5
   */
  public function __construct(&$subject, $config)
  {
    
    parent::__construct($subject, $config);
    $this->use_spellchecker       = $config['params']->get('use_spellchecker', false);
    $this->spellchecker_language  = $config['params']->get('language', 'en_US');
    $this->syntax                 = $config['params']->get('syntax', 'html');
    $this->theme                  = $config['params']->get('theme', 'textmate');
    $this->showPrintMargin        = $config['params']->get('showPrintMargin', 'false');
    $this->enable_emmet           = $config['params']->get('enable_emmet', 'false');
    
    $this->ace_path               = JURI::root().'plugins/editors/ace';
    $this->document               = JFactory::getDocument();

    $this->loadLanguage();
  }

  /**
   * Method to handle the onInitEditor event.
   *  - Initialises the Editor
   *
   * @return  string  JavaScript Initialization string
   * @since 1.5
   */
  public function onInit()
  {
    $ap  = $this->ace_path;
    $doc = $this->document;
    
    $doc->addStyleSheet("$ap/assets/css/style.css");
    $doc->addScript("$ap/ace/src-min/ace.js");
    $doc->addScript("$ap/assets/js/init.js");
    
    if ($this->use_spellchecker) {
      $doc->addScript("$ap/ace/src-min/ext-spellcheck.js");
      $doc->addScriptDeclaration('ace.require("ace/ext/spellcheck");');
      $doc->addScript("$ap/js/typo/typo.js");
      $doc->addScript("$ap/plugins/editors/ace/js/spellcheck_ace.js");
    }

    if($this->enable_emmet) {
      $doc->addScript("$ap/ace/src-min/ext-emmet.js");
      $doc->addScript("$ap/ace/src-min/ext-emmet-core.js");
    }
  }

  /**
   * Copy editor content to form field.
   *
   *
   * @return  string JavaScript save function
   */
  function onSave($id)
  {
        return "document.getElementById('$id').value = aceWrapper['$id'].aceEditor.getValue();\n";
  }

  /**
   * Get the editor content.
   *
   * @param string  $id   The id of the editor field.
   *
   * @return  string
   */
  function onGetContent($id)
  {
    return "aceWrapper['$id'].aceEditor.getValue();\n";
  }

  /**
   * Set the editor content.
   *
   * @param string  $id   The id of the editor field.
   * @param string  $html The content to set.
   *
   * @return  string
   */
  function onSetContent($id, $html)
  {

    return "aceWrapper['$id'].aceEditor.setValue($html);\n";
  }

  /**
   * @param string  $id
   *
   * @return  string
   */
  function onGetInsertMethod($id)
  {
    static $done = false;

    // Do this only once.
    if (!$done) {
      $done = true;
      $this->document->addScriptDeclaration("
        function jInsertEditorText(text, editor) {
          aceWrapper[editor].aceEditor.insert(text);
        }
      ");
    }

    return true;
  }

  /**
   * Display the editor area.
   *
   * @param string  $name   The control name.
   * @param string  $html   The contents of the text area.
   * @param string  $width    The width of the text area (px or %).
   * @param string  $height   The height of the text area (px or %).
   * @param int   $col    The number of columns for the textarea.
   * @param int   $row    The number of rows for the textarea.
   * @param boolean $buttons  True and the editor buttons will be displayed.
   * @param string  $id     An optional ID for the textarea (note: since 1.6). If not supplied the name is used.
   * @param string  $asset
   * @param object  $author
   * @param array $params   Associative array of editor parameters.
   *
   * @return  string
   */
  function onDisplay($name, $content, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array())
  {
    if (empty($id)) {
      $id = $name;
    }

    // Only add "px" to width and height if they are not given as a percentage
    if (is_numeric($width)) {
      $width .= 'px';
    }

    if (is_numeric($height)) {
      $height .= 'px';
    }   

    $buttons = $this->_displayButtons($id, $buttons, $asset, $author);
    $editor  = "<textarea id='textarea_$id' name='$name' class='' style='display: none;'>$content</textarea>";
    $editor .= "
      <div id='resize_$id' style='height: 500px;'>
        <div id='$id' class='editor' style='width: $width; height: 100%;'>$content</div>
      </div>
      
      <div style='cursor: n-resize;float: right; padding-left: 20px; text-align: right;' id='resizeController_$id'>".JText::_('PLG_EDITOR_ACE_RESIZE')."</div>
      <div style='cursor: pointer; float: right; text-align: right;' id='softwrap_$id'>".JText::_('PLG_EDITOR_ACE_SOFTWRAP')."</div>
      <div style='padding-right: 20px; float:left'>".JText::_('PLG_EDITOR_ACE_FULLSCREEN')."</div>
    ";
    if ($this->use_spellchecker) {      
      $editor .= "      
        <div class='ace-buttons' id='buttons_$id'>
          <div style='cursor: pointer; padding-right: 20px; float:left' id='${id}_enable'>
            ".JText::_('PLG_EDITOR_ACE_SPELLCHECK_ENABLE')."
          </div>
          <div style='padding-right: 20px; float:left'>
              <span style='cursor: pointer; padding-left: 10px;' id='".$id."_disable'>".JText::_('PLG_EDITOR_ACE_SPELLCHECK_DISABLE')."</span>
            <span class='lang' style='cursor: pointer; padding-left: 10px;' id='${id}_lang_de'>De</span>
            <span class='lang' style='cursor: pointer; padding-left: 10px;' id='${id}_lang_en'>En</span>
          </div>
        </div>
        
          ";
    }
    
    $editor .= "<div style='clear:both'></div> $buttons";
    $editor .= "
      <script>
        aceWrapper['$id'] = new AceWrapper({
          editorid: '$id',
          theme: 'ace/theme/$this->theme',
          mode: 'ace/mode/$this->syntax',
          showPrintMargin: $this->showPrintMargin,
          useWrapMode: true,
          wrapModeButtonId: 'softwrap_$id',
          enableEmmet: $this->enable_emmet,
        });
      ";
    
    if ($this->use_spellchecker) {
    $editor .= "
      spellchecker['$id'] = new SpellChecker({
        path: '$this->ace_path',
        container: $('buttons_$id'),
        lang: '$this->spellchecker_language',
        editor: '$id',
        buttonid_enable: '${id}_enable',
        buttonid_disable: '${id}_disable'
      });               
    ";
    }   
          
    $editor .="
      $('resize_$id').makeResizable({
          handle: $('resizeController_$id'),
          modifiers: {x: false, y: 'height'},
        grid: 10,
        onComplete: function(){
          aceWrapper['$id'].aceEditor.resize(true);
        }
      });
    ";

    $editor .= "
      $('textarea_$id').getParent('form').addEvent('submit', function() {
        $('textarea_$id').value = aceWrapper['$id'].aceEditor.getValue();
      });
    ";

    $editor .=' </script>';


    return $editor;
  }

    /**
     * Displays the editor buttons.
     *
     * @param   string  $name     The editor name
     * @param   mixed   $buttons  [array with button objects | boolean true to display buttons]
     * @param   string  $asset    The object asset
     * @param   object  $author   The author.
     *
     * @return  string HTML
     */
    protected function _displayButtons($name, $buttons, $asset, $author)
    {
        $return = '';

        $args = array(
            'name'  => $name,
            'event' => 'onGetInsertMethod'
        );

        $results = (array) $this->update($args);

        if ($results)
        {
            foreach ($results as $result)
            {
                if (is_string($result) && trim($result))
                {
                    $return .= $result;
                }
            }
        }

        if (is_array($buttons) || (is_bool($buttons) && $buttons))
        {
            $buttons = $this->_subject->getButtons($name, $buttons, $asset, $author);

            $return .= JLayoutHelper::render('joomla.editors.buttons', $buttons);
        }

        return $return;
    }

}
