<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;


if(!file_exists(JPATH_LIBRARIES.'/tcpdf/tcpdf.php')){
	JError::raiseError(500, JText::sprintf('JLIB_APPLICATION_ERROR_APPLICATION_LOAD','TCPDF'));
} else {
	if(!class_exists('TCPDF'))	require(JPATH_LIBRARIES.'/tcpdf/tcpdf.php');
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
class JDocumentRendererTcpdf extends TCPDF
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
		$diskcache=false; // true : compatible with joomla ?
		parent::__construct($orientation, $unit, $format, $unicode, 'UTF-8', $diskcache, $pdfa); 

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
		if (isset($this->jdoc->header_logo)) {
			$file = $this->jdoc->header_logo;
			if (file_exists($file)) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE); 
				$mime = finfo_file($finfo, $file);
				if ($mime ==="image/gif" || $mime ==="image/jpeg") {
					$this->header_logo = $file ;
				}
			}
		}
		if (isset($this->jdoc->header_logo_width))  $this->header_logo_width =  (int)$this->jdoc->header_logo_width ;
		if (isset($this->jdoc->header_title))  $this->header_title =  $this->jdoc->header_title ;
		if (isset($this->jdoc->header_string))  $this->header_string =  $this->jdoc->header_string ;
		if (isset($this->jdoc->header_text_color))  $this->header_text_color =  $this->jdoc->header_text_color ;
		if (isset($this->jdoc->header_line_color))  $this->header_line_color =  $this->jdoc->header_line_color ;
		if (empty($this->header_title)) $this->header_title = $this->jdoc->getTitle();
		// add css
		$header = '';
		foreach ($this->jdoc->_styleSheets as $src => $css) {
			// if ($src[1] === "/" ) $src = str_replace($short, $full, $src);
			
			$header.'<link rel="stylesheet"  href="'.$src.'" type="'.$css['mime'].'">';
		}
		// html debug output 
		if (JRequest::getInt('print', null) == 2) return $content;
		// set header and footer fonts
		$this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		$this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->SetFooterMargin(PDF_MARGIN_FOOTER);
		// set default monospaced font
		$this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$this->AddPage();
		$this->writeHTML($header.$content, true, false, true, false, '');
		// $this->WriteHTML($content);
		$this->Output($name, $destination ) ;
		if ($destination ==="F") return $name;
		else return $this->Output($name, $destination ) ;
	}
	private function fullPaths($data)
	{
	
	}
	/**
	 * This method is used to render the page footer.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
	 * @public
	 */
	public function Footer() {
		$cur_y = $this->y;
		$this->SetTextColorArray($this->footer_text_color);
		//set style for cell border
		$line_width = (0.85 / $this->k);
		$this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color));
		//print document barcode
		$barcode = $this->getBarcode();
		if (!empty($barcode)) {
			$this->Ln($line_width);
			$barcode_width = round(($this->w - $this->original_lMargin - $this->original_rMargin) / 3);
			$style = array(
				'position' => $this->rtl?'R':'L',
				'align' => $this->rtl?'R':'L',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => false,
				'padding' => 0,
				'fgcolor' => array(0,0,0),
				'bgcolor' => false,
				'text' => false
			);
			$this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width, '', (($this->footer_margin / 3) - $line_width), 0.3, $style, '');
		}
		$footer ='';
		if (!empty($this->HTMLfooter)) {
		//@page { margin: 180px 50px; }
			$footer .= '<div id="HTMLfooter">'.$this->HTMLfooter.'</div>' ;
		} else {
			$siteUrl = JURI::getInstance()->toString();
			$siteUrl = str_replace("format=pdf", "", $siteUrl);
			$app = JFactory::getApplication();
			$title = $app->getCfg('sitename').' - '.$this->title;
			$footer .= '<div id="HTMLfooter"><p class="page"><a href="'.$siteUrl.'">'.$title.'</p></div>' ;
		
		}
		$w_page = isset($this->l['w_page']) ? $this->l['w_page'].' ' : '';
		if (empty($this->pagegroups)) {
			$pagenumtxt = $w_page.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
		} else {
			$pagenumtxt = $w_page.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
		}
		$this->SetY($cur_y-$this->jdoc->_margin_footer);
		$this->writeHTMLCell($w=0, $h=0, $x='', $y='', $footer, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
		//Print page number
		$this->SetY($cur_y);
		if ($this->getRTL()) {
			$this->SetX($this->original_rMargin);
			$this->Cell(0, 0, $pagenumtxt, 'T', 0, 'L');
		} else {
			$this->SetX($this->original_lMargin);
			$this->Cell(0, 0, $this->getAliasRightShift().$pagenumtxt, 'T', 0, 'R');
		}
	} 
}
