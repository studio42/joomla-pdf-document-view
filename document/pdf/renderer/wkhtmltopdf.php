<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;


if(!file_exists(JPATH_LIBRARIES.'/wkhtmltopdf/wkhtmltopdf.php')){
	JError::raiseError(500, JText::sprintf('JLIB_APPLICATION_ERROR_APPLICATION_LOAD','wkhtmltopdf'));
} else {
	if(!class_exists('WkHtmlToPdf'))	require_once(JPATH_LIBRARIES.'/wkhtmltopdf/wkhtmltopdf.php');
}
/**
 * JDocumentRenderer_Atom is a feed that implements the atom specification
 *
 * Please note that just by using this class you won't automatically
 * produce valid atom files. For example, you have to specify either an editor
 * for the feed or an author for every single feed item.
 * options : https://github.com/blueheadpublishing/bookshop/wiki/wkhtmltopdf-options
 * or	http://madalgo.au.dk/~jakobt/wkhtmltoxdoc/wkhtmltopdf-0.9.9-doc.html
 * @package     Joomla.Platform
 * @subpackage  Document
 * @see         http://www.atomenabled.org/developers/syndication/atom-format-spec.php
 * @since       11.1
 */
class JDocumentRendererWkhtmltopdf extends wkhtmltopdf
{
	/**
	 * Document mime type
	 *
	 * @var    string
	 * @since  11.1
	 */
		//set mime type
	protected $_mime = 'application/pdf';
	public $_doc ;
	/**
	 * Class constructor
	 *
	 * @param   JDocument  &$doc  A reference to the JDocument object that instantiated the renderer
	 *
	 * @since   11.1
	 */
	public function __construct(&$doc)
	{
		$this->jdoc = &$doc;
		// set default values
		$orientation = isset($doc->orientation) ? $doc->orientation	: 'P';
		$unit		 = isset($doc->unit) 		? $doc->unit 		: 'mm';
		$format 	 = isset($doc->format) 		? $doc->format 		: 'A4';
		$unicode	 = isset($doc->unicode) 	? $doc->unicode		: true;
		$pdfa		 = isset($doc->pdfa) 		? $doc->pdfa		: false;
		// $doc->params->->get('logo_height',48);
		$options = array(
			'no-outline',         // Make Chrome not complain
			'margin-top'    => 0,
			'margin-right'  => 0,
			'margin-bottom' => 0,
			'margin-left'   => 0,
			'bin' => $doc->params->get('wkhtmltopdf_path','/usr/bin/wkhtmltopdf')
		);
		// PDFA true include more datas but render same in all computers. For a web site this can be very longer setted to true.
		// $encoding always utf8
		$diskcache=false; // true : compatible with joomla ?
		// parent::__construct($orientation, $unit, $format, $unicode, 'UTF-8', $diskcache, $pdfa); 
		parent::__construct($options); 

	}

	/**
	 * Render the feed.
	 *
	 * @param   string  $name     The name of the element to render
	 * @param   array   $params   Array of values
	 * @param   string  $content  Override the output of the renderer
	 *
	 * @return  string  The output of the script
	 *
	 * @see JDocumentRenderer::render()
	 * @since   11.1
	 */
	public function render($name = '', $destination = 'I', $content = null)
	{
		$app = JFactory::getApplication();
	// parse variable from JDocument to header
		// if (isset($this->jdoc->header_logo)) {
			// $file = $this->jdoc->header_logo;
			// if (file_exists($file)) {
				// $finfo = finfo_open(FILEINFO_MIME_TYPE); 
				// $mime = finfo_file($finfo, $file);
				// if ($mime ==="image/gif" || $mime ==="image/jpeg") {
					// $this->header_logo = $file ;
				// }
			// }
		// }
		// if (isset($this->jdoc->header_logo_width))  $this->header_logo_width =  (int)$this->jdoc->header_logo_width ;
		// if (isset($this->jdoc->header_title))  $this->header_title =  $this->jdoc->header_title ;
		// if (isset($this->jdoc->header_string))  $this->header_string =  $this->jdoc->header_string ;
		// if (isset($this->jdoc->header_text_color))  $this->header_text_color =  $this->jdoc->header_text_color ;
		// if (isset($this->jdoc->header_line_color))  $this->header_line_color =  $this->jdoc->header_line_color ;
		// if (empty($this->header_title)) $this->header_title = $this->jdoc->getTitle();


		$this->setPageOptions(array(
			'disable-smart-shrinking'
		) );
		$content = $this->fullPaths($content);
		// echo $content; jexit();
		if (JRequest::getInt('print', null) == 2) return $content;
		$this->addPage($content);
		// Save the PDF
		if ($destination === "F") {
			if(!$send = $this->saveAs($name))
				throw new Exception('Could not save PDF: '.$this->getError());
		} elseif(!$send =$this->send())
			throw new Exception('Could not create PDF: '.$this->getError());
		return $send ;
	}
	private function fullPaths($data)
	{
		$this->Creator .= ' '.$this->jdoc->engineName;
		$header = $footer = '';
		if (!strpos($data, 'html>')) {
			$full = juri::root(); 
			$short = juri::root(true).'/';
		
			$langTag = JFactory::getLanguage()->getTag();
			// missing header create it
			$header = '
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'. $langTag .'" lang="'. $langTag .'" dir="'.$this->directionality .'">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<title>'.$this->Title.'</title>
				<meta name="title" content="'.$this->Title.'" />
				<meta name="generator" content="'.$this->Creator.'" />
				<meta name="description" content="'.$this->Subject.'" />
				<meta name="keywords" content="'.$this->Keywords.'" />';
			// add scripts
			foreach ($this->jdoc->_scripts as $src => $script) {
				if ($src[1] === "/" ) $src = str_replace($short, $full, $src);
				$header.'<script src="'.$src.'" type="'.$script['mime'].'"></script>';
				
			}
			// add css
			foreach ($this->jdoc->_styleSheets as $src => $css) {
				if ($src[1] === "/" ) $src = str_replace($short, $full, $src);
				
				$header.'<link rel="stylesheet"  href="'.$src.'" type="'.$css['mime'].'">';
			}
			$header .= '</head>';
			$footer = '</html>';
				//public 'HTMLHeader' => string '<h1>Amazon Kindle Fire HD 8,9</h1>' (length=34)
			if (!strpos($data, 'body>')) {
				// add renderengine class to body
				$header .= '<body class="'.$this->jdoc->engineName.'">';
				$footer = '</body>'.$footer;
			}
		}
		// var_dump($this);jexit();
		if (!empty($this->HTMLHeader)) {
			$logo_height = $this->jdoc->params->get('logo_height',48);
			$header .='
		  <style>
			#HTMLHeader { position: fixed; padding-left:'.$this->jdoc->_margin_left.'mm; padding-right:'.$this->jdoc->_margin_right.'mm; padding-top:'.$this->jdoc->_margin_header.'mm; left: 0px; top: 0px; right: 0px; height: 30px; text-align: right;}
			#HTMLHeader h1{margin:0px}
			#HTMLHeader img{ position: fixed; padding-left:'.$this->jdoc->_margin_left.'mm; padding-right:'.$this->jdoc->_margin_right.'mm; padding-top:'.$this->jdoc->_margin_header.'mm; left: 0px; top: 0px; right: 0px; height: '.$logo_height.'px;}
		  </style>
		  ';
			$header .= '<div id="HTMLHeader">'.$this->HTMLHeader.'</div>' ;
		}
		
		if (!empty($this->HTMLfooter)) {
		//@page { margin: 180px 50px; }
			$header .= '<div id="HTMLfooter">'.$this->HTMLfooter.'</div>' ;
		} else {
			$siteUrl = JURI::getInstance()->toString();
			$siteUrl = str_replace("format=pdf", "", $siteUrl);
			$app = JFactory::getApplication();
			$title = $app->getCfg('sitename').' - '.$this->Title;
			
			$date =& JFactory::getDate();
			$jDate = JHTML::_('date',$date,JText::_('DATE_FORMAT_LC3'));
			$header .='
			  <style>
				#HTMLfooter,#HTMLDate { position: fixed; padding-left:'.$this->jdoc->_margin_left.'mm; padding-right:'.$this->jdoc->_margin_right.'mm; padding-top:'.$this->jdoc->_margin_footer.'mm;  bottom: -10px; height: 40px; text-align: left;}
			  </style>
			  ';
			$header .= '<div id="HTMLfooter" style="left:'.$this->jdoc->_margin_left.'mm"><p class="wkhtmltopdf" ><a href="'.$siteUrl.'">'.$title.'</a></p></div><div id="HTMLDate" style="right:'.$this->jdoc->_margin_right.'mm">'.$jDate.'</div>' ;
		
		}
		// $options= array();
		// $option['header-right']= '""Page [page] of [toPage]""';
		// $option['footer-right']= '""[date]""';
		// $option[1]= 'grayscale';
		// $this->setOptions($option);
		// $break = '<div class="page-breaker"></div>';
		$data = $header.$data.$footer;
		// make absolute links
		$full = '"'.juri::root(); 
		$short = '"'.juri::root(true).'/';
// echo $full.' '.$short; jexit();
		$data = str_replace($short, $full, $data);
		$full = "'".juri::root(); 
		$short = "'".juri::root(true).'/';
		$data = str_replace($short, $full, $data);

		return $data;
	}
	public function setRTL($isRtl = false) {
		$this->directionality = $isRtl ? 'rtl' : 'ltr';
	}
}
