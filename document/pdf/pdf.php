<?php
/**
* @version		$Id: pdf.php 14401 2013-06-09 14:10:00Z Patrick K $
* @package		Joomla.Framework
* @subpackage	Document
* @copyright	Copyright (C) 2004 - 2013 Studio 42. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * DocumentPDF class, provides an easy interface to parse and display a pdf document
 * 
 * Includes getter and setter function to simplify acces to mpdf
 * simply use syntax as in joomla get function models , but you can use 5 arguments!
 * eg.
	// call to mpdf::SetMargins
	$document = JFactory::getDocument();
	$document->set('margins', 10,10,5);
	
 *
 * @package		Joomla.Framework
 * @subpackage	Document
 * @since		1.5
 */
class JDocumentPDF extends JDocument
{
	var $_engine	= null;

	var $_name		= 'joomla';

	var $_header	= null;
	var $_headerContent = null;
	var $_header_font = 'courier';
	var $_footer_font = 'courier';

	var $_margin_header	= 5;
	var $_margin_footer	= 5;
	var $_margin_top	= 20;
	var $_margin_bottom	= 10;
	var $_margin_left	= 5;
	var $_margin_right	= 5;
	// Destination where to send the document.
	var $_destination = "S";
	
	// file path to save the PDF.
	var $_pdfFilepath ='';
	// Scale ratio for images [number of points in user unit]
	var $_image_scale	= 4;

	// header values
	var $header_logo = '';
	var $header_title = '';
	var $header_string = '';
	var $header_text_color = '';
	var $header_line_color = '';
	
	var $orientation = 'P';
	var $unit = 'mm';
	

	/**
	 * Array of Header <link> tags
	 *
	 * @var    array
	 * @since  11.1
	 */
	public $_links = array();

	/**
	 * Array of custom tags
	 *
	 * @var    array
	 * @since  11.1
	 */
	public $_custom = array();

	/**
	 * Name of the template
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $template = null;

	/**
	 * Base url
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $baseurl = null;

	/**
	 * Array of template parameters
	 *
	 * @var    array
	 * @since  11.1
	 */
	public $params = null;

	/**
	 * File name
	 *
	 * @var    array
	 * @since  11.1
	 */
	public $_file = null;

	/**
	 * String holding parsed template
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $_template = '';

	/**
	 * Array of parsed template JDoc tags
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected $_template_tags = array();

	/**
	 * Integer with caching setting
	 *
	 * @var    integer
	 * @since  11.1
	 */
	protected $_caching = null;
	/**
	 * Class constructore
	 *
	 * @access protected
	 * @param	array	$options Associative array of options
	 */
	function __construct($options = array())
	{
		parent::__construct($options);

		//set mime type
		$this->_mime = 'application/pdf';

		//set document type
		$this->_type = 'pdf';
		$type = JRequest::getCmd('type', null);// to test engines
		
		// load plugin parameter or set empty params.
		$plugin = JPluginHelper::getPlugin('document', 'pdf');
		if (empty ($plugin) ) $params = '';
		else $params = $plugin->params;
		$this->params = new JRegistry();
		$this->params->loadString($params);

		// verify and load the PDF class and assign by ref the jdocument
		if ($type === null) $type = $this->params->get('engine','mpdf');
		if(!file_exists(JPATH_LIBRARIES.DS.$type)) {
			// reset type & fallback to installed PDF classes
			$type = null;
			$renderers = JFolder::files(dirname(__FILE__) . '/renderer/' , '\.php$');
			foreach ($renderers as $renderer) {
				$lib = substr($renderer, 0, -4) ;
				if (file_exists(JPATH_LIBRARIES.DS.$lib) ) {
					$type = $lib ;
					break;
				}
			}
		}

		// Benchmark render engine
		// $this->rendertime = microtime(true); // Gets microseconds
		$this->engineName = $type ;
		$this->_engine = $this->loadRenderer($type);

		// hock for missing PDF view in component
		// set the type to HTML to fake component.
		// Note : this can give bad result if you display a front view called from administrator and want use view.pdf.php
		$viewPath = JPATH_ROOT.DS;
		$input = JFactory::getApplication()->input;
		$option = $input->get('option','','word');
		$view = $input->get('view',substr($option, 4),'word');
		$app = JFactory::getApplication();
		if (!$app->isSite()) $viewPath .= 'administrator'.DS;
		$viewPath .= 'components'.DS.$option .DS.'views'.DS.$view.DS.'view.pdf.php';
		if(!file_exists($viewPath)) {
			$input->set('format','html');
			$this->_type = 'html';
			// jrequest::setVar('format','html');
		}
		// var_dump(debug_backtrace());
		
		
	}

	 /**
	 * Sets the document name
	 *
	 * @param   string   $name	Document name
	 * @access  public
	 * @return  void
	 */
	function setName($name = 'joomla') {
		$this->_name = $name;
	}

	/**
	 * Returns the document name
	 *
	 * @access public
	 * @return string
	 */
	function getName() {
		return $this->_name;
	}

	 /**
	 * Sets the document header string
	 *
	 * @param   string   $text	Document header string
	 * @access  public
	 * @return  void
	 */
	function setHeader($text) {
		$this->_header = $text;
	}

	/**
	 * Returns the document header string
	 *
	 * @access public
	 * @return string
	 */
	function getHeader() {
		return $this->_header;
	}

	 /**
	 * Sets the document Destination letter
	 * I: send the file inline to the browser. The plug-in is used if available. 
	 * The name given by filename is used when one selects the "Save as" option on the link generating the PDF
	 * D: send to the browser and force a file download with the name given by filename.
	 * F: save to a local file with the name given by filename (may include a path).
	 * S: return the document as a string. filename is ignored.
	 * TODO : Implement Encrypting for PDF Mail attachment.
	 * Bad settings has no fallback, this must be implemented in the renderers
	 * @param   string   $text	Document Destination letter
	 * @access  public
	 * @return  void
	 */
	function setDestination ($dest='S'){
		$this->_destination = $dest;
	}
	 /**
	 * Sets the document path
	 * TODO : add some controls ?
	 * @param   string   $text	Document header string
	 * @access  public
	 * @return  void
	 */
	function setPath ($path){
		$this->_pdfFilepath = $path;
	}
	/**
	 * Get the right document path
	 * TODO : verify if folder/file is writable
	 * @param   string   $text	Document header string
	 * @access  public
	 * @return  void
	 */
	function getPath (){
		if (empty($this->_pdfFilepath)) {
			$params = JComponentHelper::getParams('com_media'); 
			$path = $params->get('file_path','images');
			$this->_pdfFilepath = JPATH_ROOT.DS.$path.DS.$this->getName().'.pdf';
		}
		return $this->_pdfFilepath ;
	}
	/**
	 * Render the document.
	 *
	 * @access public
	 * @param boolean 	$cache		If true, cache the output
	 * @param array		$params		Associative array of attributes
	 * @return 	The rendered data
	 */
	function render( $cache = false, $params = array())
	{
		$pdf = &$this->_engine;
		$config =& JFactory::getConfig();
		$site = $config->getValue( 'config.sitename' );
		$lang = JFactory::getLanguage();

		// parse PDF document Metadata
		// most pdf use same function.
		// if function not exist add it to the renderer engine file to set yourself the basic functions if needed.
		$this->Set('Creator',$this->getGenerator());
		$this->Set('Title',$this->getTitle());
		$this->Set('Subject',$this->getDescription());
		$this->Set('Keywords',$this->getMetaData('keywords'));
		$this->Set('RTL',$lang->isRTL());
		// echo $this->getPath().' '.$this->_pdfFilepath; jexit();
		// Benchmark render engine
		// $this->rendertime = microtime(true); // Gets microseconds
		// $this->_destination = "F";
		$data = $pdf->render($this->getPath(),$this->_destination,$this->getBuffer() );
		// test mode to get real html
		// echo filesize($this->getPath()) . ' bytes</br>';
		// echo "Time Elapsed: ".(microtime(true) - $this->rendertime)."s Engine : ".$this->engineName." ";
		if ( JRequest::getInt('print', null) == 2) return $data;

		// case of no browser render, work is done ;
		if($this->_destination ==='F') return $this->_pdfFilepath;
		// Set document type headers
		parent::render();

		//JResponse::setHeader('Content-Length', strlen($data), true);
		JResponse::setHeader('Content-type', 'application/pdf', true);
		JResponse::setHeader('Content-disposition', 'inline; filename="'.$this->getName().'.pdf"', true);

		//Close and output PDF document
		return $data;
	}

	function fixLinks()
	{

	}
	/**
	 * Method to get data from a method or property of the engine
	 *
	 * @param   string  $property  The name of the method to call
	 * @param   string  $default   default value [optional]
	 *
	 * @return  mixed  The return value of the method
	 *
	 * @since   11.1
	 */
	public function get($property, $default = null)
	{

	
			// Model exists, let's build the method name
			$method = 'Get' . ucfirst($property);

			// Does the method exist?
			if (method_exists($this->_engine, $method) && is_callable(array($this->_engine, $method)) )
			{
				// The method exists, let's call it and return what we get
				return $this->_engine->$method();
			} elseif ( isset($this->_engine, $property) )
			{
				// The property exists, return it
				return $this->_engine->$property;
			}
		
		return $default;
	}
	/**
	 * Method to set data from a method or property of the engine
	 * THe method check if the function is supported
	 * @param   string  $property  The name of the method to call on the model or the property to set
	 * @param   string  $value   The first value
	 * @param   string  $arg2   arg 2 value [optional]
	 * @param   string  $arg3   arg 3 if needed [optional]
	 * @param   string  $arg4   arg 4 if needed [optional]
	 *
	 * @return  mixed  The return value of the method
	 *
	 * @since   11.1
	 */
	public function set($property, $value = null, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null)
	{

		// let's build the method name MPDF use Upercase first(Set+'method')
		$method = 'Set' . ucfirst($property);
		// Does the method exist?
		if (method_exists($this->_engine, $method) && is_callable(array($this->_engine, $method)) )
		{
			$args = array();
			$args[] = $value;
			if ($arg2 !== null) {
				$args[] = $arg2;
				if ($arg3 !== null) {
					$args[] = $arg3;
					if ($arg4 !== null) {
						$args[] = $arg4;
						if ($arg5 !== null) {
							$args[] = $arg5;
						}
					}
				}
			}

			// The method exists, let's call it and return what we get
			return call_user_func_array( array($this->_engine,$method), $args);
		} else {
			// only public property
			if(property_exists($this->engineName, $property) ) {
				$engine = new ReflectionClass($this->engineName);
				$toCheck = $engine->getProperty($property);
				if (!$toCheck->isPublic()) return null;
			}
			// or new (perhaps new properties get removed in final release and only existing properties in engine are 'settable')
			$this->_engine->$property = $value;
		}
		return null;
	}
	/**
	 * @return  array  The document head data in array form
	 *
	 * @since   11.1
	 */
	public function getHeadData()
	{
		$data = array();
		$data['title']       = $this->title;
		$data['description'] = $this->description;
		$data['link']        = $this->link;
		$data['metaTags']    = $this->_metaTags;
		$data['links']       = $this->_links;
		$data['styleSheets'] = $this->_styleSheets;
		$data['style']       = $this->_style;
		$data['scripts']     = $this->_scripts;
		$data['script']      = $this->_script;
		$data['custom']      = $this->_custom;
		return $data;
	}

	/**
	 * Set the HTML document head data
	 *
	 * @param   array  $data  The document head data in array form
	 *
	 * @return  JDocumentHTML instance of $this to allow chaining
	 *
	 * @since   11.1
	 */
	public function setHeadData($data)
	{
		if (empty($data) || !is_array($data))
		{
			return;
		}

		$this->title = (isset($data['title']) && !empty($data['title'])) ? $data['title'] : $this->title;
		$this->description = (isset($data['description']) && !empty($data['description'])) ? $data['description'] : $this->description;
		$this->link = (isset($data['link']) && !empty($data['link'])) ? $data['link'] : $this->link;
		$this->_metaTags = (isset($data['metaTags']) && !empty($data['metaTags'])) ? $data['metaTags'] : $this->_metaTags;
		$this->_links = (isset($data['links']) && !empty($data['links'])) ? $data['links'] : $this->_links;
		$this->_styleSheets = (isset($data['styleSheets']) && !empty($data['styleSheets'])) ? $data['styleSheets'] : $this->_styleSheets;
		$this->_style = (isset($data['style']) && !empty($data['style'])) ? $data['style'] : $this->_style;
		$this->_scripts = (isset($data['scripts']) && !empty($data['scripts'])) ? $data['scripts'] : $this->_scripts;
		$this->_script = (isset($data['script']) && !empty($data['script'])) ? $data['script'] : $this->_script;
		$this->_custom = (isset($data['custom']) && !empty($data['custom'])) ? $data['custom'] : $this->_custom;

		return $this;
	}
	/**
	 * Adds a custom HTML string to the head block
	 *
	 * @param   string  $html  The HTML to add to the head
	 *
	 * @return  JDocumentHTML instance of $this to allow chaining
	 *
	 * @since   11.1
	 */
	public function addCustomTag($html)
	{
		$this->_custom[] = trim($html);

		return $this;
	}
	// not used functions but needed for VIEW.HTML Hook
	public function addHeadLink($html){}
}