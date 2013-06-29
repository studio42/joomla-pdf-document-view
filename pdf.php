<?php
/**
 * @copyright	Copyright (C) 2013 Studio42 France, All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Joomla! dummy document Pdf Plugin(only to have settings for JDocumentPDF)
 *
 * @package		JDocumentPDF.Plugin
 * @subpackage	Document.Pdf
 */
class plgDocumentPdf extends JPlugin
{

	var $_Pdf = null;

	/**
	 * Constructor
	 *
	 * @access	protected
	 * @param	object	$subject The object to observe
	 * @param	array	$config  An array that holds the plugin configuration
	 * @since	1.0
	 */
	function __construct(& $subject, $config)
	{
	}

}
