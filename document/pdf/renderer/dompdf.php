<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

if (!defined('DOMPDF_ENABLE_REMOTE')) define('DOMPDF_ENABLE_REMOTE', true);

if (!defined('DOMPDF_FONT_CACHE'))
{
	$config = JFactory::getConfig();
	define('DOMPDF_FONT_CACHE', $config->get('tmp_path'));
}
if(!file_exists(JPATH_LIBRARIES.DS.'dompdf'.DS.'dompdf_config.inc.php')){
	JError::raiseError(500, JText::sprintf('JLIB_APPLICATION_ERROR_APPLICATION_LOAD','DOMPDF'));
} else {
	if(!class_exists('DOMPDF'))	require_once(JPATH_LIBRARIES.DS.'dompdf'.DS.'dompdf_config.inc.php');
}

/**
 * JDocumentRenderer_Atom is a feed that implements the atom specification
 *
 * Please note that just by using this class you won't automatically
 * produce valid atom files. For example, you have to specify either an editor
 * for the feed or an author for every single feed item.
 *
 * @package     Joomla.Platform
 * @subpackage  Document
 * @see         http://www.atomenabled.org/developers/syndication/atom-format-spec.php
 * @since       11.1
 */
class JDocumentRendererDompdf extends DOMPDF
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

		// PDFA true include more datas but render same in all computers. For a web site this can be very longer setted to true.
		// $encoding always utf8
		// $diskcache=false; // true : compatible with joomla ?
		// Default settings are a portrait layout with an A4 configuration using millimeters as units

		parent::__construct();
		//'',$format,0,'',$doc->_margin_left,$doc->_margin_right,$doc->_margin_top,$doc->_margin_bottom,$doc->_margin_header,$doc->_margin_footer,$orientation);
		// set default header/footer
		// $app = JFactory::getApplication();
		// if ($app->getCfg('sitename_pagetitles', 0) == 1)
		// {
			// $title = $app->getCfg('sitename');
		// }
		// elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		// {
			// $title = JText::sprintf('JPAGETITLE', $doc->title, $app->getCfg('sitename'));
		// }
		// else
		// {
			// $title = $doc->title;
		// }
		// $this->SetFooter( $title.'|'.JURI::current().'|{PAGENO}/{nb}');

	}  

	//Standard autoset Page header TODO more flexible.
 /*
<html>
<head>
  <style>
    @page { margin: 180px 50px; }
    #header { position: fixed; left: 0px; top: -180px; right: 0px; height: 150px; background-color: orange; text-align: center; }
    #footer { position: fixed; left: 0px; bottom: -180px; right: 0px; height: 150px; background-color: lightblue; }
    #footer .page:after { content: counter(page, upper-roman); }
  </style>
<body>
  <div id="header">
    <h1>ibmphp.blogspot.com</h1>
  </div>
  <div id="footer">
    <p class="page"><a href="ibmphp.blogspot.com"></a></p>
  </div>
	} */

	/**
	 * Render the feed.
	 *
	 * @param   string  $name     The file name of the pdf to render
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
		// var_dump($this->jdoc);jexit();
		// $app = JFactory::getApplication();
                // $pdf = $this->engine;
                // $data = parent::render();
				$content = $this->fullPaths($content);
				// html debug output 
				if (JRequest::getInt('print', null) == 2) return $content;

                //echo $data;exit;
                $this->load_html($content);
                parent::render();
				// "I" send to browser with save as possibility
				if ($destination === "I") $this->stream($name) ;
				else if ($destination === "F") {
					// save to file and return filename
					$pdf = $this->output();
					file_put_contents($name, $pdf); 
					return $name ;
				} else return $this->output(); // return the full PDF content
                return '';
		// Gets and sets timezone offset from site configuration
		// $tz = new DateTimeZone($app->getCfg('offset'));
		// $now = JFactory::getDate();
		// $now->setTimeZone($tz);
		// if ($app->getCfg('sitename_pagetitles', 0) == 1)
		// {
			// $title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $data->title);
		// }
		// elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		// {
			// $title = JText::sprintf('JPAGETITLE', $data->title, $app->getCfg('sitename'));
		// }
		// else
		// {
			// $title = $data->title;
		// }

		// $feed_title = htmlspecialchars($title, ENT_COMPAT, 'UTF-8');
		// $data->getBase()
		// $data->getGenerator() 
		// $this->SetFooter( JURI::current().'||{PAGENO}/{nb}');
		// return $html;
	}
	public function setRTL($isRtl = false) {
		$this->directionality = $isRtl ? 'rtl' : 'ltr';
	}
        /**
         * parse relative images a hrefs and style sheets to full paths
         * @param       string  &$data
         */
 
	private function fullPaths($data)
	{
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
			if (!strpos($data, 'body>')) {
				$header .= '<body class="'.$this->jdoc->engineName.'">';
				$footer = '</body>'.$footer;
			}
		}
		// var_dump($this);jexit();
		if (!empty($this->HTMLHeader)) {
		//@page { margin: 180px 50px; }
			$logo_height = $this->jdoc->params->get('logo_height',48);
			$header .='
		  <style>
			@page { margin: 0px 0px; padding: 0px 0px; }
			#HTMLHeader { position: fixed; padding-left:'.$this->jdoc->_margin_left.'mm; padding-right:'.$this->jdoc->_margin_right.'mm; padding-top:'.$this->jdoc->_margin_header.'mm; left: 0px; top: 0px; right: 0px; height: 30px; text-align: right;}
			#HTMLHeader h1{margin:0px}
			#HTMLHeader img{ position: fixed; padding-left:'.$this->jdoc->_margin_left.'mm; padding-right:'.$this->jdoc->_margin_right.'mm; padding-top:'.$this->jdoc->_margin_header.'mm; left: 0px; top: 0px; right: 0px; height: '.$logo_height.'px;}
		  </style>
		  ';
			$header .= '<div id="HTMLHeader">'.$this->HTMLHeader.'</div>' ;
		}
		// echo($data) ;jexit();
		if (!empty($this->HTMLfooter)) {
		//@page { margin: 180px 50px; }
			$header .= '<div id="HTMLfooter">'.$this->HTMLfooter.'</div>' ;
		} else {
			$siteUrl = JURI::getInstance()->toString();
			$siteUrl = str_replace("format=pdf", "", $siteUrl);
			$app = JFactory::getApplication();
			$title = $app->getCfg('sitename').' - '.$this->Title;
			$header .='
			  <style>
				#HTMLfooter { position: fixed; padding-left:'.$this->jdoc->_margin_left.'mm; padding-right:'.$this->jdoc->_margin_right.'mm; padding-top:'.$this->jdoc->_margin_footer.'mm;  bottom: -10px; height: 40px; text-align: left;}
				#pageCounter{  position: fixed; padding-right:25px; bottom: -10px; height: 40px;  text-align: right; }
				#pageCounter span:after { content: counter(page); }
			  </style>
			  ';
			$header .= '<div id="HTMLfooter"><p class="page"><a href="'.$siteUrl.'">'.$title.'</p></div><div id="pageCounter"><span class="number">Page </span></div>' ;
		
		}
		$data = $header.$data.$footer;
		// make absolute links
		$full = '"'.juri::root(); 
		$short = '"'.juri::root(true).'/';
		$data = str_replace($short, $full, $data);
		$full = "'".juri::root(); 
		$short = "'".juri::root(true).'/';
		$data = str_replace($short, $full, $data);

		return $data;
	}

}
