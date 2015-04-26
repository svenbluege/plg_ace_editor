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
  
  /**
   * Affects constructor behavior. If true, language files will be loaded automatically.
   *
   * @var    boolean
   * @since  12.3
   */
  protected $autoloadLanguage = true;

  /**
   * Method to handle the onInitEditor event.
   *  - Initialises the Editor
   *
   * @return  string  JavaScript Initialization string
   * @since 1.5
   */
  public function onInit()
  {
    $this->document = JFactory::getDocument();
    $basePath = JURI::root() . 'plugins/editors/ace';
    
    $this->document->addStyleSheet("$basePath/assets/css/style.css");
    $this->document->addScript("//cdnjs.cloudflare.com/ajax/libs/ace/1.1.9/ace.js");
    $this->document->addScript("$basePath/assets/js/cookie.js");
    $this->document->addCustomTag("<script src='$basePath/assets/js/init.js'></script>");

    return '';
  }
  /**
   * Copy editor content to form field.
   *
   *
   * @return  string JavaScript save function
   */
  function onSave($id)
  {
    return "document.getElementById('$id').value = Joomla.editors.instances['$id'].editor.getValue();\n";
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
    return "Joomla.editors.instances['$id'].editor.getValue();\n";
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

    return "Joomla.editors.instances['$id'].editor.setValue($html);\n";
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
          Joomla.editors.instances['$id'].editor.insert(text);
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
    $id = empty($id) ? $name : $id;

    $options = new stdClass;

    $options->width   = is_numeric($width) ? $width.'px'  : $width;
    $options->height = is_numeric($height) ? $height.'px' : $height;

    $options->editorid  = $id;
    
    $options->theme = 'ace/theme/' . $this->params->get('theme', 'chrome');
    
    $options->mode = 'ace/mode/' . $this->params->get('syntax', 'html');
    
    $options->showPrintMargin = $this->params->get('showPrintMargin', 'false');
    
    $options->useWrapMode = true;
    
    $options->wrapModeBtnId = 'softwrap_' . $id;
    
    $options->enableEmmet = $this->params->get('enable_emmet', 'false');
    if($options->enableEmmet) {
      $this->document->addScript("//cdnjs.cloudflare.com/ajax/libs/ace/1.1.9/ext-emmet.js");
      $this->document->addScript("//cloud9ide.github.io/emmet-core/emmet.js");
    }

    $buttons = $this->_displayButtons($id, $buttons, $asset, $author);

    $html   = [];
    $html[] = '<textarea name="' . $name . '" id="' . $id . '" style="display: none;">' . $content . '</textarea>';
    
    $html[] = '<div class="buttons-wrap">';
    //$html[] = '   <a href="#" id="softwrap_' . $id . '">' . JText::_("PLG_EDITOR_ACE_SOFTWRAP") . '</a>';
    $html[] = '   <span class="fullscreen-text">' . JText::_("PLG_EDITOR_ACE_FULLSCREEN") . '</span>';
    $html[] = '</div>';

    $html[] = '<div style="clear:both"></div>' . $buttons;

    $html[] = '<script type="text/javascript">';
    $html[] = '(function (id, options) {';
    $html[] = '   Plugin = jQuery("#" + id).ace(options)[0]';
    $html[] = '   Joomla.editors.instances[id] = jQuery.data( Plugin, "plugin_ace" );';
    $html[] = '}(' . json_encode($id) . ', ' . json_encode($options) . '));';
    $html[] = '</script>';


    return implode("\n", $html);
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
