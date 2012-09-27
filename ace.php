<?php
/**
 * @copyright	Copyright (C) 2012 Sven Bluege, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later
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
	 * Method to handle the onInitEditor event.
	 *  - Initialises the Editor
	 *
	 * @return	string	JavaScript Initialization string
	 * @since 1.5
	 */
	public function onInit()
	{
		$txt =	'


			<style type="text/css" media="screen">
			    .adminform .editor { 
			        position: relative;
			    }
			     .ace_editor.fullScreen {
			        height: auto;
			        width: auto;
			        border: 0;
			        margin: 0;
			        position: fixed !important;
			        top: 0;
			        bottom: 0;
			        left: 0;
			        right: 0;
			        z-index: 100;
			        background: white;
			    }

			    .fullScreen {
			        overflow: hidden;
			    }
			</style>
			<script src="../plugins/editors/ace/ace/src-min/ace.js" type="text/javascript" charset="utf-8"></script>

			<script type="text/javascript">
			
			var dom = require("ace/lib/dom");

			require("ace/commands/default_commands").commands.push(
				{
				    name: "Toggle Fullscreen",
				    bindKey: "F11",
				    exec: function(editor) {
				        dom.toggleCssClass(document.body, "fullScreen");
				        dom.toggleCssClass(editor.container, "fullScreen");
				        editor.resize();
				        console.log("got it fullscreen");
				    }
				}
			)
			</script>

			';

		return $txt;
	}

	/**
	 * Copy editor content to form field.
	 *
	 *
	 * @return	string JavaScript save function
	 */
	function onSave()
	{
		return '
			$$("textarea").each(function(item) {
				if (item.id && item.id.indexOf("textarea_") == 0) {
					textarea_id = item.id;
					$(textarea_id).value = aceEditor.getValue();

				}
			});
		';
	}

	/**
	 * Get the editor content.
	 *
	 * @param	string	$id		The id of the editor field.
	 *
	 * @return	string
	 */
	function onGetContent($id)
	{
		return "aceEditor.getValue();\n";
	}

	/**
	 * Set the editor content.
	 *
	 * @param	string	$id		The id of the editor field.
	 * @param	string	$html	The content to set.
	 *
	 * @return	string
	 */
	function onSetContent($id, $html)
	{
		return "aceEditor.setValue($html);\n";
	}

	/**
	 * @param	string	$id
	 *
	 * @return	string
	 */
	function onGetInsertMethod($id)
	{
		static $done = false;

		// Do this only once.
		if (!$done) {
			$doc = JFactory::getDocument();
			$js = "\tfunction jInsertEditorText(text, editor) {
				aceEditor.insert(text);
			}";
			$doc->addScriptDeclaration($js);
		}

		return true;
	}

	/**
	 * Display the editor area.
	 *
	 * @param	string	$name		The control name.
	 * @param	string	$html		The contents of the text area.
	 * @param	string	$width		The width of the text area (px or %).
	 * @param	string	$height		The height of the text area (px or %).
	 * @param	int		$col		The number of columns for the textarea.
	 * @param	int		$row		The number of rows for the textarea.
	 * @param	boolean	$buttons	True and the editor buttons will be displayed.
	 * @param	string	$id			An optional ID for the textarea (note: since 1.6). If not supplied the name is used.
	 * @param	string	$asset
	 * @param	object	$author
	 * @param	array	$params		Associative array of editor parameters.
	 *
	 * @return	string
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
		$editor  = "<textarea id=\"textarea_$id\" name=\"$name\" class=\"\" style=\"display: none;\">$content</textarea>";
		$editor  .= "<div id=\"resize_$id\" style=\"height: 500px;\">
						<div id=\"$id\" class=\"editor\" style=\"width: $width; height: 100%;\">$content</div>
					</div>
					press F11 for fullscreen
					<div style=\"cursor: n-resize;float: right; text-align: right;\" id=\"resizeController_$id\">resize</div>" . $buttons;
		$editor .= '
			<script>
			    var aceEditor = ace.edit("'.$id.'");
			    aceEditor.setTheme("ace/theme/monokai");
			    aceEditor.getSession().setMode("ace/mode/html");
			    aceEditor.getSession().setUseWrapMode(true);


			    $("resize_'.$id.'").makeResizable({
			    	handle: $("resizeController_'.$id.'"),
			    	modifiers: {x: false, y: "height"},
					grid: 10,
					onComplete: function(){
						aceEditor.resize(true);
					}
				});


			</script>';


		return $editor;
	}

	function _displayButtons($name, $buttons, $asset, $author)
	{
		// Load modal popup behavior
		JHtml::_('behavior.modal', 'a.modal-button');

		$args['name'] = $name;
		$args['event'] = 'onGetInsertMethod';

		$return = '';
		$results[] = $this->update($args);

		foreach ($results as $result)
		{
			if (is_string($result) && trim($result)) {
				$return .= $result;
			}
		}

		if (is_array($buttons) || (is_bool($buttons) && $buttons)) {
			$results = $this->_subject->getButtons($name, $buttons, $asset, $author);

			// This will allow plugins to attach buttons or change the behavior on the fly using AJAX
			$return .= "\n<div id=\"editor-xtd-buttons\">\n";

			foreach ($results as $button)
			{
				// Results should be an object
				if ($button->get('name')) {
					$modal		= ($button->get('modal')) ? 'class="modal-button"' : null;
					$href		= ($button->get('link')) ? 'href="'.JURI::base().$button->get('link').'"' : null;
					$onclick	= ($button->get('onclick')) ? 'onclick="'.$button->get('onclick').'"' : null;
					$title      = ($button->get('title')) ? $button->get('title') : $button->get('text');
					$return .= "<div class=\"button2-left\"><div class=\"".$button->get('name')."\"><a ".$modal." title=\"".$title."\" ".$href." ".$onclick." rel=\"".$button->get('options')."\">".$button->get('text')."</a></div></div>\n";
				}
			}

			$return .= "</div>\n";
		}

		return $return;
	}
}
