<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 * @plugin  pdf
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of PDF engines from the know Joomla PDF library
 * To add a new render engine, add the prosy render class in the document/pdf/renderer/pdfclassname.php
 *  where pdfclassname is the main folder of the called class.
 *  THis class can be an extend of the master class of the main library or a bridge if you prefer
 *  The main library must be in the joomla libraies path :
 *   JPATH_LIBRARIES.DS.pdfclassname
 *  or you class is not recognized and can't be selected in this list.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormFieldEngines extends JFormFieldList
{

	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'engines';

	/**
	 * Method to get the list of files for the field options.
	 * Specify the target directory with a directory attribute
	 * Attributes allow an exclude mask and stripping of extensions from file name.
	 * Default attribute may optionally be set to null (no file) or -1 (use a default).
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   11.1
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		$renderers = JFolder::files(JPATH_LIBRARIES . '/joomla/document/pdf/renderer/' , '\.php$');
		// Build the options list from the list of files.
		if (is_array($renderers))
		{
			foreach ($renderers as $renderer) {
				$lib = substr($renderer, 0, -4) ;
				if (file_exists(JPATH_LIBRARIES.DS.$lib) ) {
					$options[] = JHtml::_('select.option', $lib, $lib);
				}
			}
		}
		return $options;
	}
}
